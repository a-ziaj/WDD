<?php
require_once 'includes/db_connect.php';

if (!isset($_GET['job_id'])) {
    die("No job ID provided.");
}

$job_id = $_GET['job_id'];
$stmt = $pdo->prepare("SELECT * FROM user_jobs WHERE job_id = ?");
$stmt->execute([$job_id]);
$row = $stmt->fetch();

if (!$row) {
    echo "No job found.";
    exit;
}

$FASTA_FILE = "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_sequences.fasta";
$RESULTS_DIR = "tmp/{$job_id}_results";

// Create results directory if it doesn't exist
if (!is_dir($RESULTS_DIR)) {
    mkdir($RESULTS_DIR, 0777, true);
}

// Run the motif scanning script
$output = shell_exec("/bin/bash run_motifs.sh '$FASTA_FILE' '$RESULTS_DIR'");

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
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
            background-color: #f9f9f9;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .summary-section {
            background: #eef7ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 5px solid #3498db;
        }
        .summary-row {
            display: flex;
            margin-bottom: 10px;
        }
        .summary-label {
            font-weight: bold;
            min-width: 150px;
            color: #2c3e50;
        }
        .insights {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 3px solid #7f8c8d;
        }
        .insights h3 {
            color: #34495e;
            margin-top: 0;
        }
        .sequence-block {
            margin-bottom: 40px;
            padding-bottom: 20px;
        }
        .motif-block {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #3498db;
        }
        .motif-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .motif-title {
            font-size: 1.2em;
            color: #2980b9;
            font-weight: 600;
        }
        .motif-details {
            color: #7f8c8d;
            font-size: 0.95em;
        }
        .motif-visualization {
            font-family: 'Courier New', monospace;
            line-height: 1.5;
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .sequence-part {
            color: #2c3e50;
            font-weight: bold;
            white-space: pre;
        }
        .visual-guide {
            color: #e74c3c;
            white-space: pre;
        }
        .download-btn {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1em;
        }
        .download-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .no-results {
            background: #fdecea;
            color: #e74c3c;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Motif Search Results for Job: <?= htmlspecialchars($row['job_id']) ?></h1>
        
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
                
                <div class="summary-row">
                    <div class="summary-label">Scan Type:</div>
                    <div>Full: <?= htmlspecialchars(trim($summary_matches[5] ?? 'N/A')) ?>, Prune: <?= htmlspecialchars(trim($summary_matches[6] ?? 'N/A')) ?></div>
                </div>
                
                <div class="summary-row">
                    <div class="summary-label">Database Used:</div>
                    <div><?= htmlspecialchars($summary_matches[7] ?? 'N/A') ?></div>
                </div>
                
                <div class="insights">
                    <h3>What This Means</h3>
                    <p><strong>Sequence Range</strong> shows the portion of the protein that was analyzed. The <strong>Total Motifs Found</strong> indicates how many functional patterns were detected in this sequence.</p>
                    <p><strong>Full Scan</strong> means the entire PROSITE database was searched, while <strong>Prune</strong> indicates whether overlapping motifs were filtered. The <strong>Database Used</strong> shows which version of PROSITE was searched for these protein patterns.</p>
                    <p>Each motif below represents a conserved functional or structural element that may be important for the protein's biological activity.</p>
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
                $sequence_info = $pdo->prepare("
                    SELECT * FROM motif_results 
                    WHERE job_id = ? AND sequence_id = ?
                    LIMIT 1
                ");
                $sequence_info->execute([$job_id, $seq['sequence_id']]);
                $seq_data = $sequence_info->fetch();
                
                $motifs = $pdo->prepare("
                    SELECT * FROM motif_results 
                    WHERE job_id = ? AND sequence_id = ?
                    ORDER BY start_pos
                ");
                $motifs->execute([$job_id, $seq['sequence_id']]);
                $motifs = $motifs->fetchAll();
            ?>
                <div class="sequence-block">
                    <h2>Detailed Motif Results for Sequence: <?= htmlspecialchars($seq_data['sequence_id']) ?></h2>
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
            
            <a href="<?= "$RESULTS_DIR/patmatmotifs_results.txt" ?>" download class="download-btn">Download Full Results</a>
        <?php else: ?>
            <div class="no-results">
                No motif results were found for this job. This could mean:
                <ul>
                    <li>The protein sequence doesn't contain any known PROSITE motifs</li>
                    <li>The search parameters were too restrictive</li>
                    <li>There was an error during motif scanning</li>
                </ul>
                Please try adjusting your search parameters or check the input sequence.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
