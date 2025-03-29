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

if (!file_exists($FASTA_FILE)) {
    echo "FASTA file not found. Please make sure the sequences were retrieved successfully.";
    exit;
}

$fa_content = file_get_contents($FASTA_FILE);
$num_sequences = substr_count($fa_content, ">");
?>

<h1>Results for Job: <?= htmlspecialchars($row['job_id']) ?></h1>
<p><strong>Protein:</strong> <?= htmlspecialchars($row['protein_family']) ?></p>
<p><strong>Taxonomic Group:</strong> <?= htmlspecialchars($row['taxonomic_group']) ?></p>
<p><strong>Sequences Retrieved:</strong> <?= $num_sequences ?></p>

<h2>🧬 Sequence Results</h2>

<h3>FASTA Sequences:</h3>
<pre style="max-height: 400px; overflow-y: scroll; background: #f1f1f1; padding: 10px;">
<?= htmlspecialchars($fa_content) ?>
</pre>

<div style="display: flex; gap: 20px; margin: 20px 0;">
    <div>
        <h3>Run Conservation Analysis</h3>
        <form action="conservation.php" method="get">
            <input type="hidden" name="job_id" value="<?= htmlspecialchars($row['job_id']) ?>">
            <label>Window Size: <input type="number" name="window_size" min="1" max="20" value="4"></label>
            <input type="submit" value="Generate Conservation Plot">
        </form>
    </div>

    <div>
        <h3>Scan for PROSITE Motifs</h3>
        <form action="motifs.php" method="get">
            <input type="hidden" name="job_id" value="<?= htmlspecialchars($row['job_id']) ?>">
            <input type="submit" value="Identify Protein Motifs">
        </form>
    </div>
</div>

<div>
    <h3>Draw phylogenetic trees</h3>
    <form action="tree.php" method="get">
        <input type="hidden" name="job_id" value="<?= htmlspecialchars($row['job_id']) ?>">
        <input type="submit" value="Run Foldseek">
    </form>
</div>
