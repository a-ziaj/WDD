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

// Handle FASTA subset request
if (isset($_POST['create_subset'])) {
    $num_sequences = (int)$_POST['num_sequences'];
    $input_file = "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_sequences.fasta";
    $subset_file = "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_subset_{$num_sequences}.fasta";
    
    // Execute cutting script
    exec("./cut_fasta.sh " . escapeshellarg($input_file) . " " . escapeshellarg($subset_file) . " $num_sequences", $output, $return_code);
    
    if ($return_code === 0) {
        $subset_success = true;
        $current_subset = $subset_file;
    } else {
        $subset_error = "Error creating subset: " . implode("\n", $output);
    }
}

// Determine which FASTA file to display
$FASTA_FILE = isset($current_subset) ? $current_subset : "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_sequences.fasta";

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

<!-- Display subset creation status messages -->
<?php if (isset($subset_error)): ?>
    <div style="color: red;"><?= htmlspecialchars($subset_error) ?></div>
<?php elseif (isset($subset_success)): ?>
    <div style="color: green;">Successfully created subset with <?= htmlspecialchars($_POST['num_sequences']) ?> sequences</div>
<?php endif; ?>

<h2>🧬 Sequence Results</h2>

<!-- Subset creation form -->
<div style="margin-bottom: 20px; padding: 10px; background: #f5f5f5;">
    <h3>Create Subset Dataset</h3>
    <form method="post">
        <input type="hidden" name="job_id" value="<?= htmlspecialchars($row['job_id']) ?>">
        <label>Number of sequences: 
            <input type="number" name="num_sequences" min="1" max="<?= $num_sequences ?>" value="<?= min(10, $num_sequences) ?>">
        </label>
        <input type="submit" name="create_subset" value="Create Subset">
        <?php if (isset($current_subset)): ?>
            <a href="?job_id=<?= htmlspecialchars($job_id) ?>" style="margin-left: 10px;">Show Full Dataset</a>
        <?php endif; ?>
    </form>
</div>

<h3>FASTA Sequences:</h3>
<pre style="max-height: 400px; overflow-y: scroll; background: #f1f1f1; padding: 10px;">
<?= htmlspecialchars($fa_content) ?>
</pre>

<!-- Rest of your existing analysis forms... -->
<div style="display: flex; gap: 20px; margin: 20px 0;">
    <div>
        <h3>Run Conservation Analysis</h3>
        <form action="conservation.php" method="get">
            <input type="hidden" name="job_id" value="<?= htmlspecialchars($row['job_id']) ?>">
            <?php if (isset($current_subset)): ?>
                <input type="hidden" name="subset" value="<?= htmlspecialchars($_POST['num_sequences']) ?>">
            <?php endif; ?>
            <label>Window Size: <input type="number" name="window_size" min="1" max="20" value="4"></label>
            <input type="submit" value="Generate Conservation Plot">
        </form>
    </div>

    <div>
        <h3>Scan for PROSITE Motifs</h3>
        <form action="motifs.php" method="get">
            <input type="hidden" name="job_id" value="<?= htmlspecialchars($row['job_id']) ?>">
            <?php if (isset($current_subset)): ?>
                <input type="hidden" name="subset" value="<?= htmlspecialchars($_POST['num_sequences']) ?>">
            <?php endif; ?>
            <input type="submit" value="Identify Protein Motifs">
        </form>
    </div>
</div>

<div>
    <h3>Draw phylogenetic trees</h3>
    <form action="content.php" method="get">
        <input type="hidden" name="job_id" value="<?= htmlspecialchars($row['job_id']) ?>">
        <?php if (isset($current_subset)): ?>
            <input type="hidden" name="subset" value="<?= htmlspecialchars($_POST['num_sequences']) ?>">
        <?php endif; ?>
        <input type="submit" value="Run Foldseek">
    </form>
</div>
