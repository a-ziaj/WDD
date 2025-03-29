<?php
require_once 'includes/db_connect.php';

if (!isset($_GET['job_id'])) {
    die("No job ID provided.");
}

$job_id = $_GET['job_id'];
$subset = isset($_GET['subset']) ? (int)$_GET['subset'] : null;

// Check if we need to generate a report
if (isset($_GET['generate_report'])) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="motif_report_' . $job_id . '.txt"');
    
    $stmt = $pdo->prepare("SELECT * FROM user_jobs WHERE job_id = ?");
    $stmt->execute([$job_id]);
    $row = $stmt->fetch();

    if (!$row) {
        die("No job found.");
    }

    // Determine results directory
    if ($subset) {
        $RESULTS_DIR = "tmp/{$job_id}_results_subset_{$subset}";
    } else {
        $RESULTS_DIR = "tmp/{$job_id}_results";
    }

    // Basic report header
    echo "Motif Analysis Report\n";
    echo "====================\n\n";
    echo "Job ID: $job_id\n";
    echo "Protein Family: " . $row['protein_family'] . "\n";
    echo "Taxonomic Group: " . $row['taxonomic_group'] . "\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n";
    if ($subset) {
        echo "Subset: First $subset sequences\n";
    }
    echo "\n";
    
    if (file_exists("$RESULTS_DIR/patmatmotifs_results.txt")) {
        // Get summary information
        $motif_content = file_get_contents("$RESULTS_DIR/patmatmotifs_results.txt");
        preg_match('/Sequence: (.+?)\s+from: (\d+)\s+to: (\d+).*?HitCount: (\d+).*?Full: (.+?)Prune: (.+?)Data_file: (.+?)$/ms', $motif_content, $summary_matches);
        
        echo "Summary\n";
        echo "-------\n";
        echo "Sequence: " . ($summary_matches[1] ?? 'N/A') . "\n";
        echo "Sequence Range: " . ($summary_matches[2] ?? 'N/A') . " to " . ($summary_matches[3] ?? 'N/A') . "\n";
        echo "Total Motifs Found: " . ($summary_matches[4] ?? '0') . "\n\n";
        
        // Detailed results
        echo "Detailed Motif Results\n";
        echo "----------------------\n\n";
        
        // Get all unique sequences with motifs for this job
        $sequences = $pdo->prepare("
            SELECT DISTINCT sequence_id
            FROM motif_results
            WHERE job_id = ?
            ORDER BY sequence_id
        ");
        $sequences->execute([$job_id]);
        $sequences = $sequences->fetchAll();

        foreach ($sequences as $seq) {
            $motifs = $pdo->prepare("
                SELECT * FROM motif_results
                WHERE job_id = ? AND sequence_id = ?
                ORDER BY start_pos
            ");
            $motifs->execute([$job_id, $seq['sequence_id']]);
            $motifs = $motifs->fetchAll();
            
            echo "Sequence: " . $seq['sequence_id'] . "\n";
            echo "Motifs found: " . count($motifs) . "\n";
            
            foreach ($motifs as $motif) {
                echo "\nMotif: " . $motif['motif_name'] . "\n";
                echo "Length: " . $motif['length'] . " aa\n";
                echo "Positions: " . $motif['start_pos'] . "-" . $motif['end_pos'] . "\n";
                echo "Sequence: " . $motif['sequence_part'] . "\n";
                echo "Visualization:\n" . $motif['enhanced_guide'] . "\n";
                echo str_repeat("-", 50) . "\n";
            }
            echo "\n";
        }
    } else {
        echo "No motif results were found for this job.\n";
    }
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM user_jobs WHERE job_id = ?");
$stmt->execute([$job_id]);
$row = $stmt->fetch();

if (!$row) {
    echo "No job found.";
    exit;
}

// Determine which FASTA file to use
if ($subset) {
    $FASTA_FILE = "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_subset_{$subset}.fasta";
    $RESULTS_DIR = "tmp/{$job_id}_results_subset_{$subset}";
} else {
    $FASTA_FILE = "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_sequences.fasta";
    $RESULTS_DIR = "tmp/{$job_id}_results";
}

// Create results directory if it doesn't exist
if (!is_dir($RESULTS_DIR)) {
    mkdir($RESULTS_DIR, 0777, true);
}

// Run the motif scanning script
$output = shell_exec("/bin/bash run_motifs.sh " . escapeshellarg($FASTA_FILE) . " " . escapeshellarg($RESULTS_DIR));

// Parse the motif results and save to database
if (file_exists("$RESULTS_DIR/patmatmotifs_results.txt")) {
    $motif_results = file_get_contents("$RESULTS_DIR/patmatmotifs_results.txt");

    // First clear previous results for this job
    $pdo->prepare("DELETE FROM motif_results WHERE job_id = ?")->execute([$job_id]);

    // Improved regex pattern to capture motif blocks
    $pattern = '/Sequence: (.+?)\s+from: (\d+)\s+to: (\d+).*?HitCount: (\d+).*?Full: (.+?)Prune: (.+?)Data_file: (.+?)(?=Sequence|\Z)/ms';
    preg_match_all($pattern, $motif_results, $sequence_blocks, PREG_SET_ORDER);

    foreach ($sequence_blocks as $block) {
        $sequence_id = $block[1];
        $start_pos = $block[2];
        $end_pos = $block[3];
        $hit_count = $block[4];

        // Now parse individual motifs within this sequence
        $motif_pattern = '/Length = (\d+)\s+Start = position (\d+) of sequence\s+End = position (\d+) of sequence\s+Motif = (.+?)\s+([A-Za-z\s]+)\n\s+([| \n]+)/';
        preg_match_all($motif_pattern, $block[0], $motifs, PREG_SET_ORDER);

        foreach ($motifs as $motif) {
            $length = $motif[1];
            $motif_start = $motif[2];
            $motif_end = $motif[3];
            $motif_name = $motif[4];
            $sequence_part = trim($motif[5]);
            $visual_guide = trim($motif[6]);

            // Generate enhanced visual guide with position numbers
            $enhanced_guide = '';
            $chars = str_split($visual_guide);
            $pos = $motif_start;

            foreach ($chars as $i => $char) {
                if ($char === '|') {
                    // Add position number after the pipe character
                    $enhanced_guide .= '|' . $pos;
                    $pos = $motif_end; // Set to end position for the second pipe
                } else {
                    $enhanced_guide .= $char;
                }
            }

            // Save to database
            $stmt = $pdo->prepare("INSERT INTO motif_results
                (job_id, sequence_id, motif_name, length, start_pos, end_pos, sequence_part, visual_guide, enhanced_guide)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $job_id,
                $sequence_id,
                $motif_name,
                $length,
                $motif_start,
                $motif_end,
                $sequence_part,
                $visual_guide,
                $enhanced_guide
            ]);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Protein Motif Search</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; margin: 0; padding: 20px; color: #333; background-color: #f9f9f9; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .summary-section { background: #eef7ff; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 5px solid #3498db; }
        .summary-row { display: flex; margin-bottom: 10px; }
        .summary-label { font-weight: bold; min-width: 150px; color: #2c3e50; }
        .insights { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 3px solid #7f8c8d; }
        .sequence-block { margin-bottom: 40px; padding-bottom: 20px; }
        .motif-block { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-left: 4px solid #3498db; }
        .motif-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .motif-title { font-size: 1.2em; color: #2980b9; font-weight: 600; }
        .motif-details { color: #7f8c8d; font-size: 0.95em; }
        .motif-visualization { font-family: 'Courier New', monospace; line-height: 1.5; background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .sequence-part { color: #2c3e50; font-weight: bold; white-space: pre; }
        .visual-guide { color: #e74c3c; white-space: pre; }
        .download-btn { display: inline-block; background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 20px; transition: all 0.3s; border: none; cursor: pointer; font-size: 1em; }
        .no-results { background: #fdecea; color: #e74c3c; padding: 15px; border-radius: 5px; text-align: center; }
        .subset-info { background: #e8f5e9; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Motif Search Results for Job: <?= htmlspecialchars($row['job_id']) ?></h1>

        <?php if ($subset): ?>
            <div class="subset-info">
                <strong>Using subset:</strong> First <?= $subset ?> sequences
            </div>
        <?php endif; ?>

        <?php if (file_exists("$RESULTS_DIR/patmatmotifs_results.txt")): ?>
            <?php
            // Get summary information
            $motif_content = file_get_contents("$RESULTS_DIR/patmatmotifs_results.txt");
            preg_match('/Sequence: (.+?)\s+from: (\d+)\s+to: (\d+).*?HitCount: (\d+).*?Full: (.+?)Prune: (.+?)Data_file: (.+?)$/ms', $motif_content, $summary_matches);
            ?>

            <div class="summary-section">
                <h2>Analysis Summary</h2>
                <div class="summary-row">
                    <div class="summary-label">Protein Sequence:</div>
                    <div><?= htmlspecialchars($summary_matches[1] ?? 'N/A') ?></div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Sequence Range:</div>
                    <div><?= htmlspecialchars($summary_matches[2] ?? 'N/A') ?> to <?= htmlspecialchars($summary_matches[3] ?? 'N/A') ?></div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Total Motifs Found:</div>
                    <div><?= htmlspecialchars($summary_matches[4] ?? '0') ?></div>
                </div>
            </div>

            <?php
            // Get all unique sequences with motifs for this job
            $sequences = $pdo->prepare("
                SELECT DISTINCT sequence_id
                FROM motif_results
                WHERE job_id = ?
                ORDER BY sequence_id
            ");
            $sequences->execute([$job_id]);
            $sequences = $sequences->fetchAll();

            foreach ($sequences as $seq):
                $motifs = $pdo->prepare("
                    SELECT * FROM motif_results
                    WHERE job_id = ? AND sequence_id = ?
                    ORDER BY start_pos
                ");
                $motifs->execute([$job_id, $seq['sequence_id']]);
                $motifs = $motifs->fetchAll();
            ?>
                <div class="sequence-block">
                    <h2>Detailed Motif Results for Sequence: <?= htmlspecialchars($seq['sequence_id']) ?></h2>
                    <p>Showing <?= count($motifs) ?> detected motifs:</p>

                    <?php foreach ($motifs as $motif): ?>
                        <div class="motif-block">
                            <div class="motif-header">
                                <span class="motif-title"><?= htmlspecialchars($motif['motif_name']) ?> Motif</span>
                                <span class="motif-details">
                                    Length: <?= $motif['length'] ?> aa |
                                    Positions: <?= $motif['start_pos'] ?>-<?= $motif['end_pos'] ?>
                                </span>
                            </div>
                            <div class="motif-visualization">
                                <div class="sequence-part"><?= htmlspecialchars($motif['sequence_part']) ?></div>
                                <div class="visual-guide"><?= htmlspecialchars($motif['enhanced_guide']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <a href="?job_id=<?= $job_id ?>&subset=<?= $subset ?>&generate_report=1" class="download-btn">
                Generate TXT Report
            </a>
        <?php else: ?>
            <div class="no-results">
                No motif results were found for this job. This could mean:
                <ul>
                    <li>The protein sequence doesn't contain any known PROSITE motifs</li>
                    <li>The search parameters were too restrictive</li>
                    <li>There was an error during motif scanning</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
