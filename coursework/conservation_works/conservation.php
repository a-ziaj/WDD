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

// Ensure tmp directory exists and is writable
if (!is_dir('tmp')) {
    if (!mkdir('tmp', 0777, true)) {
        die("Failed to create tmp directory");
    }
} elseif (!is_writable('tmp')) {
    die("tmp directory is not writable");
}

// Define file paths
$protein_family = $row['protein_family'];
$taxonomic_group = $row['taxonomic_group'];
$FASTA_FILE = "tmp/{$protein_family}_{$taxonomic_group}_sequences.fasta";
$ALIGN_FILE = "tmp/{$job_id}_aligned.aln";
$PLOT_FILE = "tmp/{$job_id}_plot.png";
$ALT_PLOT_FILE = "tmp/{$job_id}_plot.1.png";

// Verify input FASTA exists
if (!file_exists($FASTA_FILE)) {
    die("<p>FASTA file not found at $FASTA_FILE</p>");
}

echo "<h1>Running Clustal Omega and Plotcon Analysis...</h1>";

// Execute the pipeline
$command = "/bin/bash run_conservation.sh '$FASTA_FILE' '$ALIGN_FILE' '$PLOT_FILE' 2>&1";
$output = shell_exec($command);

// Debug output
echo "<h3>Pipeline Output:</h3>";
echo "<pre style='background:#f5f5f5;padding:10px;border:1px solid #ddd;'>" 
     . htmlspecialchars($output ?? 'No output from pipeline') 
     . "</pre>";

// Check results
$success = true;

if (!file_exists($ALIGN_FILE)) {
    echo "<div style='color:red;'><strong>Error:</strong> Alignment file not created at $ALIGN_FILE</div>";
    $success = false;
}

// Check for plot file with either naming convention
if (!file_exists($PLOT_FILE) && !file_exists($ALT_PLOT_FILE)) {
    echo "<div style='color:red;'><strong>Error:</strong> Plot file not created (checked both $PLOT_FILE and $ALT_PLOT_FILE)</div>";
    $success = false;
} elseif (file_exists($ALT_PLOT_FILE) && !file_exists($PLOT_FILE)) {
    echo "<div style='color:orange;'>Found plot with alternate naming ($ALT_PLOT_FILE)</div>";
    echo "<div style='color:green;'>Displaying alternate plot file</div>";
    $PLOT_FILE = $ALT_PLOT_FILE; // Use the alternate file
}

if ($success) {
    echo "<h3 style='color:green;'>Analysis Completed Successfully</h3>";
    
    // Display alignment
    if (file_exists($ALIGN_FILE)) {
        echo "<h4>Multiple Sequence Alignment:</h4>";
        echo "<div style='max-height:500px;overflow:auto;background:#f5f5f5;padding:15px;border:1px solid #ddd;font-family:monospace;'>";
        echo htmlspecialchars(file_get_contents($ALIGN_FILE) ?? 'Empty alignment file');
        echo "</div>";
    }
    
    // Display plot
    if (file_exists($PLOT_FILE)) {
        echo "<h4>Conservation Plot:</h4>";
        echo "<img src='$PLOT_FILE' style='max-width:100%;border:1px solid #ddd;'>";
        echo "<p><a href='$PLOT_FILE' download>Download Plot Image</a></p>";
    }
}

// Display logs
display_log("tmp/{$job_id}_aligned_clustalo.log", "Clustal Omega Log");
display_log("tmp/{$job_id}_plot_plotcon.log", "Plotcon Log");

function display_log($log_path, $title) {
    if (file_exists($log_path)) {
        echo "<h4>$title:</h4>";
        echo "<pre style='background:#f5f5f5;padding:10px;border:1px solid #ddd;max-height:300px;overflow:auto;'>";
        echo htmlspecialchars(file_get_contents($log_path) ?? 'Empty log file');
        echo "</pre>";
    }
}
?>
