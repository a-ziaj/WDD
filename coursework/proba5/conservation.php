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

// Get the relevant sequence file path and other details
$protein_family = $row['protein_family'];
$taxonomic_group = $row['taxonomic_group'];
//$FASTA_FILE = "{$protein_family}_{$taxonomic_group}_sequences.fasta";
$FASTA_FILE = "/tmp/{$row['protein_family']}_{$row['taxonomic_group']}_sequences.fasta";

$ALIGN_FILE = "/tmp/{$job_id}_aligned.aln";
$PLOT_FILE = "/tmp/{$job_id}_plot.png";

// Ensure the FASTA file exists before proceeding
if (!file_exists($FASTA_FILE)) {
    echo "<p>FASTA file not found for this job. Please make sure the sequences were retrieved successfully.</p>";
    exit;
}

// Execute the pipeline for Clustal Omega and Plotcon analysis
echo "<h1>Running Clustal Omega and Plotcon Analysis...</h1>";

// Check if the required files exist
if (!isset($FASTA_FILE) || !file_exists($FASTA_FILE)) {
    echo "<p><strong>Error:</strong> FASTA file not found or not set. Please provide a valid FASTA file.</p>";
    exit; // Stop execution if FASTA file is missing
}

echo "files: $FASTA_FILE $ALIGN_FILE $PLOT_FILE";

$command = "/bin/bash run_conservation.sh $FASTA_FILE $ALIGN_FILE $PLOT_FILE";
$output = shell_exec($command);


// check aligment anf plot files
if (!isset($ALIGN_FILE) || !file_exists($ALIGN_FILE)) {
    echo "<p><strong>Error:</strong> Alignment file not found or not set. Please provide a valid alignment file.</p>";
    //exit; // Stop execution if ALIGN file is missing
}

if (!isset($PLOT_FILE) || !file_exists($PLOT_FILE)) {
    echo "<p><strong>Error:</strong> Plot file not found or not set. Please provide a valid plot file.</p>";
   // exit; // Stop execution if PLOT file is missing
}





// Check for errors
if ($output === null) {
    echo "<p>Error occurred during analysis. Please check the logs.</p>";
    echo "<p><strong>Error Details:</strong></p>";
    
        // Print Clustal Omega log
    if (file_exists("/tmp/{$job_id}_clustalo.log")) {
        echo "<pre>" . shell_exec("cat /tmp/{$job_id}_clustalo.log") . "</pre>";
    } else {
        echo "<p>No Clustal Omega log found.</p>";
    }
    
    // Print PlotCon log
    if (file_exists("/tmp/{$job_id}_plotcon.log")) {
        echo "<pre>" . shell_exec("cat /tmp/{$job_id}_plotcon.log") . "</pre>";
    } else {
        echo "<p>No PlotCon log found.</p>";
    }
    
    
} else {
    echo "<h3>Analysis Complete</h3>";
    echo "<p><strong>Alignment File:</strong> <a href='{$job_id}_aligned.aln' download>Download Alignment</a></p>";
    echo "<p><strong>Conservation Plot:</strong> <img src='{$job_id}_plot.png' alt='Conservation Plot'></p>";
    echo "<h3>Clustal Omega Output:</h3>";
    echo "<pre>" . shell_exec("cat /tmp/{$job_id}_clustalo.log") . "</pre>";
    echo "<p><strong>Result Logs:</strong><pre>$output</pre>";
}
?>

