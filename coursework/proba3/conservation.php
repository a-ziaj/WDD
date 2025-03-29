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

// Get sequence info and file paths (in /tmp/)
$protein_family = $row['protein_family'];
$taxonomic_group = $row['taxonomic_group'];

$protein_family = str_replace(' ', '_', $protein_family);
$taxonomic_group = str_replace(' ', '_', $taxonomic_group);


$FASTA_FILE = "/tmp/{$protein_family}_{$taxonomic_group}_sequences.fasta";
$ALIGN_FILE = "/tmp/{$job_id}_aligned.aln";
$PLOT_FILE = "/tmp/{$job_id}_plot.png";

// Check if FASTA file exists
if (!file_exists($FASTA_FILE)) {
    echo "<p><strong>Error:</strong> FASTA file not found at $FASTA_FILE</p>";
    exit;
}

echo "<h1>Running Clustal Omega and Plotcon Analysis...</h1>";
echo "<p>Using files:</p><pre>$FASTA_FILE\n$ALIGN_FILE\n$PLOT_FILE</pre>";

// Run the shell script
$command = "/bin/bash run_conservation.sh $FASTA_FILE $ALIGN_FILE $PLOT_FILE";
$output = shell_exec($command);

// Check alignment and plot files
if (!file_exists($ALIGN_FILE)) {
    echo "<p><strong>Error:</strong> Alignment file not created: $ALIGN_FILE</p>";
}
if (!file_exists($PLOT_FILE)) {
    echo "<p><strong>Error:</strong> Plot file not created: $PLOT_FILE</p>";
}

// Show results
echo "<h3>Analysis Complete</h3>";
if (file_exists($ALIGN_FILE)) {
    echo "<p><strong>Alignment File:</strong> <a href='$ALIGN_FILE' download>Download Alignment</a></p>";
}
if (file_exists($PLOT_FILE)) {
    echo "<p><strong>Conservation Plot:</strong><br><img src='$PLOT_FILE' alt='Conservation Plot'></p>";
}

// Show logs (Clustal Omega + Plotcon)
$clustal_log = "{$ALIGN_FILE}_clustalo.log";
$plotcon_log = "{$PLOT_FILE}_plotcon.log";

echo "<h3>Clustal Omega Log:</h3>";
if (file_exists($clustal_log)) {
    echo "<pre>" . file_get_contents($clustal_log) . "</pre>";
} else {
    echo "<p>No Clustal Omega log found.</p>";
}

echo "<h3>Plotcon Log:</h3>";
if (file_exists($plotcon_log)) {
    echo "<pre>" . file_get_contents($plotcon_log) . "</pre>";
} else {
    echo "<p>No Plotcon log found.</p>";
}

echo "<h3>Script Output:</h3><pre>$output</pre>";
?>

