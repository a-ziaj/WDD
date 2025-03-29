<?php
require_once "includes/db.php";

if (!isset($_GET['job_id'])) {
    die("No job specified.");
}

$job_id = $_GET['job_id'];
$stmt = $pdo->prepare("SELECT * FROM user_jobs WHERE job_id = ?");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    die("Job not found.");
}

// Path to the FASTA file
$fasta_path = "/job_outputs/job_{$job_id}.fasta";
$fasta_content = file_exists($fasta_path) ? file_get_contents($fasta_path) : "FASTA file not found.";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Job Results</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>Job #<?php echo htmlspecialchars($job_id); ?> Results</h1>
    <p><strong>Protein Family:</strong> <?php echo htmlspecialchars($job['protein_family']); ?></p>
    <p><strong>Taxonomic Group:</strong> <?php echo htmlspecialchars($job['taxonomic_group']); ?></p>
    <p><strong>Submitted at:</strong> <?php echo $job['created_at']; ?></p>

    <h2>FASTA File Output</h2>
    <pre><?php echo htmlspecialchars($fasta_content); ?></pre>

    <h2>Full Analysis Output</h2>
    <?php if ($job['results']): ?>
        <pre><?php echo htmlspecialchars($job['results']); ?></pre>
    <?php else: ?>
        <p>No analysis results found.</p>
    <?php endif; ?>
</div>
</body>
</html>

