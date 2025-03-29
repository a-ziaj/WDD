<?php
require_once 'includes/db_connect.php';

$job_id = 'job_67e740db3d5c9';
$subset = isset($_GET['subset']) ? (int)$_GET['subset'] : null;

// Get job details
$stmt = $pdo->prepare("SELECT * FROM user_jobs WHERE job_id = ?");
$stmt->execute([$job_id]);
$row = $stmt->fetch();

if (!$row) {
    die("No job found with ID: $job_id");
}

// Determine which FASTA file to use
if ($subset) {
    $FASTA_FILE = "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_subset_{$subset}.fasta";
    $ALIGN_FILE = "tmp/{$job_id}_aligned_subset_{$subset}.aln";
    $RESULTS_DIR = "tmp/{$job_id}_results_subset_{$subset}";
} else {
    $FASTA_FILE = "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_sequences.fasta";
    $ALIGN_FILE = "tmp/{$job_id}_aligned.aln";
    $RESULTS_DIR = "tmp/{$job_id}_results";
}

// Create results directory if needed
if (!is_dir($RESULTS_DIR)) {
    mkdir($RESULTS_DIR, 0777, true);
}

// Run analyses if they haven't been run yet
if (!file_exists("$RESULTS_DIR/entropy.json")) {
    $window_size = 4; // default window size
    shell_exec("/bin/bash run_conservation.sh " . escapeshellarg($FASTA_FILE) . " " . escapeshellarg($ALIGN_FILE) . " " . escapeshellarg($RESULTS_DIR) . " $window_size 2>&1");
}

if (!file_exists("$RESULTS_DIR/patmatmotifs_results.txt")) {
    shell_exec("/bin/bash run_motifs.sh " . escapeshellarg($FASTA_FILE) . " " . escapeshellarg($RESULTS_DIR));
}

// Parse FASTA for amino acid content
$sequences = [];
$current_id = '';
$current_seq = '';
$amino_acids = str_split('ACDEFGHIKLMNPQRSTVWY');

if (file_exists($FASTA_FILE)) {
    $file = fopen($FASTA_FILE, 'r');
    while (($line = fgets($file)) !== false) {
        $line = trim($line);
        if (strpos($line, '>') === 0) {
            if ($current_id !== '') {
                $sequences[$current_id] = $current_seq;
            }
            $current_id = substr($line, 1);
            $current_seq = '';
        } else {
            $current_seq .= strtoupper($line);
        }
    }
    if ($current_id !== '') {
        $sequences[$current_id] = $current_seq;
    }
    fclose($file);
}

// Calculate amino acid percentages
$aa_data = [];
foreach ($sequences as $id => $seq) {
    $total = strlen($seq);
    $counts = array_fill_keys($amino_acids, 0);

    foreach (str_split($seq) as $aa) {
        if (isset($counts[$aa])) {
            $counts[$aa]++;
        }
    }

    $percentages = [];
    foreach ($counts as $aa => $count) {
        $percentages[$aa] = $total > 0 ? ($count / $total) * 100 : 0;
    }

    $aa_data[$id] = $percentages;
}
$aa_data_json = json_encode($aa_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprehensive Analysis | <?= htmlspecialchars($row['protein_family']) ?></title>
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2ecc71;
            --accent: #e74c3c;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --gray: #95a5a6;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Open Sans', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
            padding: 0;
            margin: 0;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, var(--dark), var(--primary));
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        h1, h2, h3, h4 {
            font-family: 'Roboto Slab', serif;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        h1 {
            font-size: 2.2rem;
            color: white;
        }
        
        h2 {
            font-size: 1.8rem;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 8px;
            margin-top: 30px;
        }
        
        h3 {
            font-size: 1.4rem;
            color: var(--primary);
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            flex: 1;
            min-width: 200px;
        }
        
        .meta-item h3 {
            margin-bottom: 10px;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .meta-value {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .subset-controls {
            background: var(--light);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
            color: white;
        }
        
        .btn-success {
            background: var(--success);
        }
        
        .btn-success:hover {
            background: #219653;
        }
        
        .btn-danger {
            background: var(--danger);
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-secondary {
            background: var(--gray);
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .plot-container {
            width: 100%;
            height: 400px;
            margin-bottom: 20px;
        }
        
        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .sequence-viewer {
            max-height: 400px;
            overflow-y: auto;
            font-family: monospace;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
            white-space: pre;
        }
        
        .motif-block {
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }
        
        .motif-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .motif-title {
            font-weight: 600;
            color: var(--primary);
        }
        
        .motif-details {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .motif-visualization {
            font-family: monospace;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .sequence-selector {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 1rem;
        }
        
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray);
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .insights {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
            border-left: 4px solid var(--warning);
        }
        
        .insights h4 {
            color: var(--warning);
            margin-bottom: 10px;
        }
        
        .insights ul {
            padding-left: 20px;
        }
        
        .insights li {
            margin-bottom: 8px;
        }
        
        @media (max-width: 768px) {
            .analysis-grid {
                grid-template-columns: 1fr;
            }
            
            .job-meta {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <h1><?= htmlspecialchars($row['protein_family']) ?> Analysis Dashboard</h1>
            <div>
                <span class="job-id">Job ID: <?= htmlspecialchars($job_id) ?></span>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="job-meta">
            <div class="meta-item">
                <h3>Protein Family</h3>
                <div class="meta-value"><?= htmlspecialchars($row['protein_family']) ?></div>
            </div>
            <div class="meta-item">
                <h3>Taxonomic Group</h3>
                <div class="meta-value"><?= htmlspecialchars($row['taxonomic_group']) ?></div>
            </div>
            <div class="meta-item">
                <h3>Sequences</h3>
                <div class="meta-value"><?= count($sequences) ?></div>
            </div>
            <div class="meta-item">
                <h3>Status</h3>
                <div class="meta-value">Analysis Complete</div>
            </div>
        </div>
        
        <?php if (count($sequences) > 10): ?>
        <div class="subset-controls">
            <form method="get" style="display: flex; align-items: center; gap: 10px;">
                <input type="hidden" name="job_id" value="<?= htmlspecialchars($job_id) ?>">
                <label for="num_sequences">Analyze subset:</label>
                <input type="number" id="num_sequences" name="subset" min="1" max="<?= count($sequences) ?>" 
                       value="<?= $subset ? $subset : min(10, count($sequences)) ?>" style="padding: 8px; width: 80px;">
                <button type="submit" class="btn">Apply</button>
                <?php if ($subset): ?>
                    <a href="aves.php?job_id=<?= htmlspecialchars($job_id) ?>" class="btn btn-secondary">Show Full Dataset</a>
                <?php endif; ?>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="openTab(event, 'conservation')">Conservation</button>
                <button class="tab-btn" onclick="openTab(event, 'motifs')">Motifs</button>
                <button class="tab-btn" onclick="openTab(event, 'composition')">AA Composition</button>
            </div>
            
            <!-- Conservation Tab -->
            <div id="conservation" class="tab-content active">
                <div class="card">
                    <h2>Sequence Conservation Analysis</h2>
                    <p>Explore the evolutionary conservation patterns in your protein family.</p>
                    
                    <?php if (file_exists("$RESULTS_DIR/entropy.json")): ?>
                    <div class="plot-container" id="entropyPlot"></div>
                    <div class="insights">
                        <h4>Interpreting Shannon Entropy</h4>
                        <ul>
                            <li><strong>Low values (near 0):</strong> Highly conserved positions that are likely functionally important</li>
                            <li><strong>High values:</strong> Variable positions that tolerate more evolutionary changes</li>
                            <li>Look for regions with consistently low entropy as potential functional domains</li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        Entropy analysis data not available. Try with a smaller subset of sequences.
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="analysis-grid">
                    <div class="card">
                        <h3>Aligned Sequences</h3>
                        <div class="sequence-viewer">
                            <?php
                            if (file_exists("$RESULTS_DIR/alignment.txt")) {
                                echo htmlspecialchars(file_get_contents("$RESULTS_DIR/alignment.txt"));
                            } elseif (file_exists($ALIGN_FILE)) {
                                echo htmlspecialchars(file_get_contents($ALIGN_FILE));
                            } else {
                                echo "Alignment not available. Try with a smaller subset of sequences.";
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <h3>EMBOSS Plotcon Analysis</h3>
                        <?php if (file_exists("$RESULTS_DIR/plotcon.png")): ?>
                            <img src="<?= "$RESULTS_DIR/plotcon.png" ?>" style="width: 100%; border-radius: 4px;">
                            <div class="insights">
                                <h4>Interpreting Plotcon</h4>
                                <ul>
                                    <li>Shows conservation over sliding windows (default: 4 residues)</li>
                                    <li>Peaks indicate highly conserved regions</li>
                                    <li>Valleys indicate variable regions</li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <p>Plotcon analysis not available. Try with a smaller subset of sequences.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Motifs Tab -->
            <div id="motifs" class="tab-content">
                <div class="card">
                    <h2>PROSITE Motif Detection</h2>
                    <p>Identified functional motifs in your protein sequences.</p>
                    
                    <?php
                    // Get motif summary
                    $motif_summary = $pdo->prepare("
                        SELECT COUNT(*) as total_motifs, COUNT(DISTINCT sequence_id) as sequences_with_motifs
                        FROM motif_results
                        WHERE job_id = ?
                    ");
                    $motif_summary->execute([$job_id]);
                    $summary = $motif_summary->fetch();
                    ?>
                    
                    <div class="job-meta" style="margin-bottom: 20px;">
                        <div class="meta-item">
                            <h3>Total Motifs Found</h3>
                            <div class="meta-value"><?= $summary['total_motifs'] ?></div>
                        </div>
                        <div class="meta-item">
                            <h3>Sequences with Motifs</h3>
                            <div class="meta-value"><?= $summary['sequences_with_motifs'] ?></div>
                        </div>
                    </div>
                    
                    <?php
                    // Get all unique sequences with motifs
                    $motif_sequences = $pdo->prepare("
                        SELECT DISTINCT sequence_id
                        FROM motif_results
                        WHERE job_id = ?
                        ORDER BY sequence_id
                    ");
                    $motif_sequences->execute([$job_id]);
                    $motif_sequences = $motif_sequences->fetchAll();
                    
                    if (count($motif_sequences) > 0): ?>
                        <select id="motifSequenceSelector" class="sequence-selector">
                            <?php foreach ($motif_sequences as $seq): ?>
                                <option value="<?= htmlspecialchars($seq['sequence_id']) ?>">
                                    <?= htmlspecialchars($seq['sequence_id']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div id="motifResultsContainer">
                            <?php
                            // Display motifs for first sequence
                            $first_seq = $motif_sequences[0]['sequence_id'];
                            $motifs = $pdo->prepare("
                                SELECT * FROM motif_results
                                WHERE job_id = ? AND sequence_id = ?
                                ORDER BY start_pos
                            ");
                            $motifs->execute([$job_id, $first_seq]);
                            $motifs = $motifs->fetchAll();
                            
                            foreach ($motifs as $motif): ?>
                                <div class="motif-block">
                                    <div class="motif-header">
                                        <span class="motif-title"><?= htmlspecialchars($motif['motif_name']) ?></span>
                                        <span class="motif-details">
                                            Positions: <?= $motif['start_pos'] ?>-<?= $motif['end_pos'] ?> | 
                                            Length: <?= $motif['length'] ?> aa
                                        </span>
                                    </div>
                                    <div class="motif-visualization">
                                        <div style="font-weight: bold; margin-bottom: 5px;"><?= htmlspecialchars($motif['sequence_part']) ?></div>
                                        <div style="color: var(--accent);"><?= htmlspecialchars($motif['enhanced_guide']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <a href="motifs.php?job_id=<?= $job_id ?>&subset=<?= $subset ?>&generate_report=1" class="btn btn-success">
                            Download Full Motif Report
                        </a>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No PROSITE motifs were detected in these sequences.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- AA Composition Tab -->
            <div id="composition" class="tab-content">
                <div class="card">
                    <h2>Amino Acid Composition Analysis</h2>
                    <p>Detailed breakdown of amino acid percentages in each sequence.</p>
                    
                    <select id="aaSequenceSelector" class="sequence-selector">
                        <?php foreach (array_keys($aa_data) as $id): ?>
                            <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($id) ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="plot-container" id="aaChart"></div>
                    
                    <div class="insights">
                        <h4>Interpretation Tips</h4>
                        <ul>
                            <li>Look for unusual amino acid distributions that might indicate special functions</li>
                            <li>High percentages of hydrophobic residues may indicate transmembrane regions</li>
                            <li>Charged residue clusters may indicate binding sites</li>
                        </ul>
                    </div>
                    
                    <a href="content.php?job_id=<?= $job_id ?>&subset=<?= $subset ?>&generate_report=1" class="btn btn-success">
                        Download Full AA Composition Report
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab functionality
        function openTab(evt, tabName) {
            const tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }
            
            const tabButtons = document.getElementsByClassName("tab-btn");
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove("active");
            }
            
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        
        // Amino Acid Composition Chart
        const aaData = <?= $aa_data_json ?>;
        
        function updateAAChart(selectedId) {
            const data = [{
                x: Object.keys(aaData[selectedId]),
                y: Object.values(aaData[selectedId]),
                type: 'bar',
                marker: { color: '#3498db' }
            }];
            
            const layout = {
                title: `Amino Acid Composition: ${selectedId}`,
                xaxis: { title: 'Amino Acid' },
                yaxis: { title: 'Percentage (%)' },
                hovermode: 'closest',
                margin: { t: 40, l: 50, r: 30, b: 50 }
            };
            
            Plotly.react('aaChart', data, layout);
        }
        
        // Initial chart load
        const initialAAId = Object.keys(aaData)[0];
        updateAAChart(initialAAId);
        
        // Update chart on selection change
        document.getElementById('aaSequenceSelector').addEventListener('change', function(e) {
            updateAAChart(e.target.value);
        });
        
        // Motif sequence selector
        <?php if (count($motif_sequences) > 0): ?>
        document.getElementById('motifSequenceSelector').addEventListener('change', function(e) {
            const sequenceId = e.target.value;
            
            fetch(`get_motifs.php?job_id=<?= $job_id ?>&sequence_id=${sequenceId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('motifResultsContainer').innerHTML = html;
                });
        });
        <?php endif; ?>
        
        // Load entropy plot if available
        <?php if (file_exists("$RESULTS_DIR/entropy.json")): ?>
        Plotly.d3.json("<?= "$RESULTS_DIR/entropy.json" ?>", function(err, fig) {
            if (fig) {
                // Customize the layout
                fig.layout.title = "Shannon Entropy Analysis";
                fig.layout.xaxis.title = "Position in Alignment";
                fig.layout.yaxis.title = "Entropy (bits)";
                fig.layout.margin = { t: 40, l: 50, r: 30, b: 50 };
                fig.layout.hovermode = 'closest';
                
                Plotly.plot('entropyPlot', fig.data, fig.layout);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
