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

?>

<!DOCTYPE html>
<html>
<head>
    <title>Protein Motif Search</title>
    <style>
        .analysis-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .motif-results {
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .motif-results table {
            width: 100%;
            border-collapse: collapse;
        }
        .motif-results th, .motif-results td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .download-btn {
            background-color: #007BFF;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .download-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Motif Search Results for Job: <?= htmlspecialchars($row['job_id']) ?></h1>
    <p><strong>Protein Family:</strong> <?= htmlspecialchars($row['protein_family']) ?></p>
    <p><strong>Taxonomic Group:</strong> <?= htmlspecialchars($row['taxonomic_group']) ?></p>

    <?php if (file_exists("$RESULTS_DIR/patmatmotifs_results.txt")): ?>
        <div class="motif-results">
            <h2>Motif Search Results</h2>
            <pre style="max-height: 400px; overflow-y: scroll; background: #f1f1f1; padding: 10px;">
                <?= htmlspecialchars(file_get_contents("$RESULTS_DIR/patmatmotifs_results.txt")) ?>
            </pre>
            <p>
                <a href="<?= "$RESULTS_DIR/patmatmotifs_results.txt" ?>" download class="download-btn">Download Motif Results</a>
            </p>
        </div>
    <?php else: ?>
        <p style="color:red;">Motif analysis failed to generate results.</p>
    <?php endif; ?>

</body>
</html>

