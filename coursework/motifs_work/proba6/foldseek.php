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
    die("Job not found.");
}

$protein_family = $row['protein_family'];
$tax_group = $row['taxonomic_group'];
$fasta_file = "tmp/{$protein_family}_{$tax_group}_sequences.fasta";

if (!file_exists($fasta_file)) {
    die("FASTA file not found. Run the sequence retrieval step first.");
}

// Run Foldseek analysis
$output_dir = "results/foldseek/{$job_id}";
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0777, true);
}

$command = escapeshellcmd("./run_foldseek.sh $fasta_file $output_dir");
exec($command . " 2>&1", $output, $return_var);

echo "<h1>Foldseek Results for Job: " . htmlspecialchars($job_id) . "</h1>";

if ($return_var !== 0) {
    echo "<p>Foldseek analysis failed. Output:</p><pre>" . implode("\n", $output) . "</pre>";
} else {
    echo "<p>Foldseek analysis completed successfully.</p>";

    $result_file = "$output_dir/results.tsv";
    if (file_exists($result_file)) {
        echo "<h2>Foldseek Output</h2>";
        echo "<pre style='max-height: 400px; overflow-y: scroll; background: #f9f9f9; padding: 10px;'>";
        echo htmlspecialchars(file_get_contents($result_file));
        echo "</pre>";
    } else {
        echo "<p>Results file not found.</p>";
    }
}
?>

