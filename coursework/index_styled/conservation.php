<?php
require_once 'includes/db_connect.php';

if (!isset($_GET['job_id'])) {
    die("No job ID provided.");
}

$job_id = $_GET['job_id'];
$window_size = isset($_GET['window_size']) ? (int)$_GET['window_size'] : 4;
$subset = isset($_GET['subset']) ? (int)$_GET['subset'] : null;

// Validate window size
$window_size = max(1, min(20, $window_size));

$stmt = $pdo->prepare("SELECT * FROM user_jobs WHERE job_id = ?");
$stmt->execute([$job_id]);
$row = $stmt->fetch();

if (!$row) {
    die("No job found.");
}

$protein_family = $row['protein_family'];
$taxonomic_group = $row['taxonomic_group'];

// Determine which FASTA file to use
if ($subset) {
    $FASTA_FILE = "tmp/{$protein_family}_{$taxonomic_group}_subset_{$subset}.fasta";
    $ALIGN_FILE = "tmp/{$job_id}_aligned_subset_{$subset}.aln";
    $RESULTS_DIR = "tmp/{$job_id}_results_subset_{$subset}";
} else {
    $FASTA_FILE = "tmp/{$protein_family}_{$taxonomic_group}_sequences.fasta";
    $ALIGN_FILE = "tmp/{$job_id}_aligned.aln";
    $RESULTS_DIR = "tmp/{$job_id}_results";
}

// Create results directory
if (!is_dir($RESULTS_DIR)) {
    mkdir($RESULTS_DIR, 0777, true);
}

// Run analyses
$output = shell_exec("/bin/bash run_conservation.sh " . escapeshellarg($FASTA_FILE) . " " . escapeshellarg($ALIGN_FILE) . " " . escapeshellarg($RESULTS_DIR) . " $window_size 2>&1");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Conservation Analysis</title>
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <style>
        .analysis-container { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
        .visualization { flex: 1; min-width: 400px; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .sequence-viewer { height: 500px; overflow-y: auto; font-family: monospace; background: #f5f5f5; padding: 10px; white-space: pre; }
        .insights { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #6c757d; margin: 20px 0; }
        .plot-container { height: 400px; }
        .download-buttons { margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap; }
        .download-btn {
            padding: 8px 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
        }
        .download-btn:hover { background: #45a049; }
        .download-btn.secondary {
            background: #2196F3;
        }
        .download-btn.secondary:hover {
            background: #0b7dda;
        }
    </style>
</head>
<body>
    <h1>Conservation Analysis for <?= htmlspecialchars($protein_family) ?></h1>
    <p><strong>Taxonomic Group:</strong> <?= htmlspecialchars($taxonomic_group) ?></p>
    <p><strong>Window Size:</strong> <?= $window_size ?></p>
    <?php if ($subset): ?>
        <p><strong>Using subset:</strong> First <?= $subset ?> sequences</p>
    <?php endif; ?>

    <div class="analysis-container">
        <div class="visualization">
            <h2>Shannon Entropy Analysis</h2>
            <div class="plot-container" id="entropyPlot"></div>
            <?php if (file_exists("$RESULTS_DIR/entropy.json")): ?>
                <div class="download-buttons">
                    <a href="<?= "$RESULTS_DIR/entropy.json" ?>" download="shannon_entropy.json" class="download-btn">
                        Download JSON
                    </a>
                    <?php
                    // Generate CSV version
                    $json_data = json_decode(file_get_contents("$RESULTS_DIR/entropy.json"), true);
                    $csv_file = "$RESULTS_DIR/entropy.csv";
                    $csv_content = "Position,Entropy(bits)\n";
                    foreach ($json_data['data'][0]['y'] as $pos => $entropy) {
                        $csv_content .= ($pos+1) . "," . round($entropy, 3) . "\n";
                    }
                    file_put_contents($csv_file, $csv_content);
                    ?>
                    <a href="<?= $csv_file ?>" download="shannon_entropy.csv" class="download-btn secondary">
                        Download CSV
                    </a>
                    <?php if (file_exists("$RESULTS_DIR/entropy.png")): ?>
                        <a href="<?= "$RESULTS_DIR/entropy.png" ?>" download="shannon_entropy.png" class="download-btn">
                            Download PNG
                        </a>
                    <?php endif; ?>
                    <?php if (file_exists("$RESULTS_DIR/report.txt")): ?>
                        <a href="<?= "$RESULTS_DIR/report.txt" ?>" download class="download-btn secondary">
                            Download Full Report
                        </a>
                    <?php endif; ?>
                </div>
                <script>
                    Plotly.d3.json("<?= "$RESULTS_DIR/entropy.json" ?>", function(err, fig) {
                        Plotly.plot("entropyPlot", fig.data, fig.layout);
                    });
                </script>
            <?php else: ?>
                <p style="color:red;">Entropy analysis failed to generate. Try smaller dataset e.g. 10.</p>
            <?php endif; ?>
        </div>

        <div class="visualization">
            <h2>Aligned Sequences</h2>
            <div class="sequence-viewer">
                <?php
                if (file_exists("$RESULTS_DIR/alignment.txt")) {
                    echo htmlspecialchars(file_get_contents("$RESULTS_DIR/alignment.txt"));
                } elseif (file_exists($ALIGN_FILE)) {
                    echo htmlspecialchars(file_get_contents($ALIGN_FILE));
                } else {
                    echo "Alignment file not found. Try smaller dataset e.g. 10.";
                }
                ?>
            </div>
            <div class="download-buttons">
                <?php if (file_exists($ALIGN_FILE)): ?>
                    <a href="<?= $ALIGN_FILE ?>" download class="download-btn">
                        Download Full Alignment
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="visualization">
        <h2>EMBOSS Plotcon Analysis</h2>
        <?php if (file_exists("$RESULTS_DIR/plotcon.png")): ?>
            <img src="<?= "$RESULTS_DIR/plotcon.png" ?>" style="max-width:100%;">
            <div class="download-buttons">
                <a href="<?= "$RESULTS_DIR/plotcon.png" ?>" download class="download-btn">
                    Download Plot
                </a>
            </div>
        <?php else: ?>
            <p style="color:red;">Plotcon analysis failed to generate. Try smaller dataset e.g. 10.</p>
        <?php endif; ?>
    </div>

    <div class="insights">
        <h3>Interpretation Guide</h3>
        <p><strong>Shannon Entropy:</strong></p>
        <ul>
            <li>0 = Perfectly conserved position</li>
            <li>Higher values = More diversity at that position</li>
            <li>Look for conserved regions (low values) that may indicate functional importance</li>
        </ul>
        <p><strong>Plotcon:</strong></p>
        <ul>
            <li>Shows smoothed conservation over <?= $window_size ?>-residue windows</li>
            <li>High scores = Conserved regions</li>
            <li>Low scores = Variable regions</li>
        </ul>
    </div>
</body>
</html>
