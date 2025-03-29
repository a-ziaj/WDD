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

// Define file paths
$protein_family = $row['protein_family'];
$taxonomic_group = $row['taxonomic_group'];
$ALIGN_FILE = "tmp/{$job_id}_aligned.aln";
$PLOT_FILE = "tmp/{$job_id}_plot.png";
$ALT_PLOT_FILE = "tmp/{$job_id}_plot.1.png";

// Check for plot file with either naming convention
if (!file_exists($PLOT_FILE) && file_exists($ALT_PLOT_FILE)) {
    $PLOT_FILE = $ALT_PLOT_FILE;
}
?>

<h3 style='color:green;'>Analysis Completed Successfully</h3>

<?php if (file_exists($ALIGN_FILE)): ?>
    <h4>Multiple Sequence Alignment:</h4>
    <div style='max-height:500px;overflow:auto;background:#f5f5f5;padding:15px;border:1px solid #ddd;font-family:monospace;'>
        <?= htmlspecialchars(file_get_contents($ALIGN_FILE) ?? 'Empty alignment file' ?>
    </div>
<?php endif; ?>

<?php if (file_exists($PLOT_FILE)): ?>
    <h4>Conservation Plot:</h4>
    <img src='<?= $PLOT_FILE ?>' style='max-width:100%;border:1px solid #ddd;'>
    <p><a href='<?= $PLOT_FILE ?>' download>Download Plot Image</a></p>
<?php endif; ?>
