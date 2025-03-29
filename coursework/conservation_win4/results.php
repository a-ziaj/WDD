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

// Define the path to the FASTA file
$FASTA_FILE = "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_sequences.fasta";

// Check if the FASTA file exists
if (!file_exists($FASTA_FILE)) {
    echo "FASTA file not found at $FASTA_FILE. Please make sure the sequences were retrieved successfully.";
    exit;
}

// Read the FASTA file content
$fa_content = file_get_contents($FASTA_FILE);
$num_sequences = substr_count($fa_content, ">");
?>

<h1>Results for Job: <?= htmlspecialchars($row['job_id']) ?></h1>
<p><strong>Protein:</strong> <?= htmlspecialchars($row['protein_family']) ?></p>
<p><strong>Taxonomic Group:</strong> <?= htmlspecialchars($row['taxonomic_group']) ?></p>

<h2>🧬 Sequence Results</h2>
<p><strong>Total Sequences Found:</strong> <?= $num_sequences ?></p>

<h3>FASTA Sequences:</h3>
<pre style="max-height: 400px; overflow-y: scroll; background: #f1f1f1; padding: 10px;">
<?= htmlspecialchars($fa_content) ?>
</pre>
<!-- Form to move to conservation.php with window size selection -->
<h3>Run Conservation Analysis</h3>
<form action="conservation.php" method="get">
    <input type="hidden" name="job_id" value="<?= htmlspecialchars($row['job_id']) ?>">
    
    <label for="window_size">Window Size (default: 4):</label>
    <input type="number" id="window_size" name="window_size" min="1" max="20" value="4">
    
    <input type="submit" value="Generate Conservation Plot">
</form>
