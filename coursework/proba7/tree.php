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
    die("No job found.");
}

$protein_family = $row['protein_family'];
$taxonomic_group = $row['taxonomic_group'];
$FASTA_FILE = "tmp/{$protein_family}_{$taxonomic_group}_sequences.fasta";
$RESULTS_DIR = "tmp/{$job_id}_tree_results";

// Create results directory
if (!is_dir($RESULTS_DIR)) {
    mkdir($RESULTS_DIR, 0777, true);
}

// Run the tree generation script
$output = shell_exec("/bin/bash run_tree.sh " . escapeshellarg($FASTA_FILE) . " " . escapeshellarg($RESULTS_DIR) . " 2>&1");

// Check if trees were generated
$upgma_png = "$RESULTS_DIR/upgma_tree.png";
$nj_png = "$RESULTS_DIR/nj_tree.png";
$upgma_exists = file_exists($upgma_png);
$nj_exists = file_exists($nj_png);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Phylogenetic Trees</title>
    <style>
        .tree-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 20px 0;
        }
        .tree-card {
            flex: 1;
            min-width: 400px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .tree-image {
            max-width: 100%;
            height: auto;
            margin: 15px 0;
        }
        .download-link {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>Phylogenetic Analysis for <?= htmlspecialchars($protein_family) ?></h1>
    <p><strong>Job ID:</strong> <?= htmlspecialchars($job_id) ?></p>
    
    <?php if (!$upgma_exists || !$nj_exists): ?>
        <div class="error">
            <h3>Error Generating Trees</h3>
            <p>Possible reasons:</p>
            <ul>
                <li>Fewer than 4 sequences in FASTA file</li>
                <li>Alignment failed (check sequence validity)</li>
                <li>Server error in tree generation</li>
            </ul>
            <h4>Debugging Output:</h4>
            <pre><?= htmlspecialchars($output) ?></pre>
        </div>
    <?php else: ?>
        <div class="tree-container">
            <div class="tree-card">
                <h2>UPGMA Tree</h2>
                <img src="<?= $upgma_png ?>" class="tree-image" alt="UPGMA Phylogenetic Tree">
                <a href="<?= $upgma_png ?>" download class="download-link">Download UPGMA Tree</a>
            </div>
            
            <div class="tree-card">
                <h2>Neighbor-Joining Tree</h2>
                <img src="<?= $nj_png ?>" class="tree-image" alt="NJ Phylogenetic Tree">
                <a href="<?= $nj_png ?>" download class="download-link">Download NJ Tree</a>
            </div>
        </div>
        
        <div style="margin-top: 30px; background: #e9f5e9; padding: 15px; border-radius: 4px;">
            <h3>Interpretation Guide</h3>
            <p><strong>UPGMA (Unweighted Pair Group Method with Arithmetic Mean):</strong></p>
            <ul>
                <li>Assumes constant mutation rates (molecular clock hypothesis)</li>
                <li>Produces rooted trees with equal branch lengths</li>
                <li>Good for closely related sequences with similar evolutionary rates</li>
            </ul>
            
            <p><strong>Neighbor-Joining (NJ):</strong></p>
            <ul>
                <li>Doesn't assume constant mutation rates</li>
                <li>Produces unrooted trees with varying branch lengths</li>
                <li>Better for diverse sequences with varying evolutionary rates</li>
            </ul>
            
            <p><strong>Next Steps:</strong></p>
            <ul>
                <li>Compare with <a href="conservation.php?job_id=<?= $job_id ?>" target="_blank">conservation analysis</a></li>
                <li>Validate branches with bootstrap values (requires additional analysis)</li>
                <li>Compare with known taxonomic relationships</li>
            </ul>
        </div>
    <?php endif; ?>
</body>
</html>
