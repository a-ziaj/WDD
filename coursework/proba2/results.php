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
?>

<h1>Results for Job: <?= htmlspecialchars($row['job_id']) ?></h1>
<p><strong>Protein:</strong> <?= htmlspecialchars($row['protein_family']) ?></p>
<p><strong>Taxonomic Group:</strong> <?= htmlspecialchars($row['taxonomic_group']) ?></p>

<h2>🧬 Sequence Results</h2>
<pre style="max-height: 400px; overflow-y: scroll; background: #f1f1f1; padding: 10px;">
<?= htmlspecialchars($row['results']) ?>
</pre>
