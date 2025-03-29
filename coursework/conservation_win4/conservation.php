<?php
require_once 'includes/db_connect.php';

if (!isset($_GET['job_id'])) {
    die("No job ID provided.");
}

$job_id = $_GET['job_id'];
$window_size = isset($_GET['window_size']) ? (int)$_GET['window_size'] : 4;

// Validate window size
if ($window_size < 1 || $window_size > 20) {
    $window_size = 4; // Default if invalid
}

$stmt = $pdo->prepare("SELECT * FROM user_jobs WHERE job_id = ?");
$stmt->execute([$job_id]);
$row = $stmt->fetch();

if (!$row) {
    echo "No job found.";
    exit;
}

$protein_family = $row['protein_family'];
$taxonomic_group = $row['taxonomic_group'];
$FASTA_FILE = "tmp/{$protein_family}_{$taxonomic_group}_sequences.fasta";
$ALIGN_FILE = "tmp/{$job_id}_aligned.aln";
$PLOT_FILE = "tmp/{$job_id}_plot.png";

if (!file_exists($FASTA_FILE)) {
    echo "<p>FASTA file not found for this job.</p>";
    exit;
}

echo "<h1>Running Conservation Analysis with Window Size: $window_size</h1>";

// Execute with window size parameter
$command = "/bin/bash run_conservation.sh '$FASTA_FILE' '$ALIGN_FILE' '$PLOT_FILE' $window_size 2>&1";
$output = shell_exec($command);

if (file_exists($PLOT_FILE)) {
    echo "<h3 style='color:green;'>Analysis Complete</h3>";
    echo "<p><strong>Alignment File:</strong> <a href='$ALIGN_FILE' download>Download Alignment</a></p>";
    echo "<p><strong>Conservation Plot (Window Size: $window_size):</strong></p>";
    echo "<img src='$PLOT_FILE' style='max-width:100%;border:1px solid #ddd;'>";
} else {
    echo "<h3 style='color:red;'>Analysis Failed</h3>";
    echo "<pre>Error: " . htmlspecialchars($output) . "</pre>";
}
?>
