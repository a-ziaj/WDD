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

$FASTA_FILE = "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_sequences.fasta";

if (!file_exists($FASTA_FILE)) {
    die("FASTA file not found.");
}

// Parse FASTA file
$sequences = [];
$current_id = '';
$current_seq = '';

$file = fopen($FASTA_FILE, 'r');
while (($line = fgets($file)) !== false) {
    $line = trim($line);
    if (strpos($line, '>') === 0) {
        if ($current_id !== '') {
            $sequences[$current_id] = $current_seq;
        }
        $current_id = substr($line, 1); // Remove '>'
        $current_seq = '';
    } else {
        $current_seq .= strtoupper($line);
    }
}
if ($current_id !== '') {
    $sequences[$current_id] = $current_seq;
}
fclose($file);

// Calculate amino acid percentages
$aa_data = [];
$amino_acids = str_split('ACDEFGHIKLMNPQRSTVWY'); // Standard amino acids

foreach ($sequences as $id => $seq) {
    $total = strlen($seq);
    $counts = array_fill_keys($amino_acids, 0);
    
    foreach (str_split($seq) as $aa) {
        if (isset($counts[$aa])) {
            $counts[$aa]++;
        }
    }
    
    $percentages = [];
    foreach ($counts as $aa => $count) {
        $percentages[$aa] = $total > 0 ? ($count / $total) * 100 : 0;
    }
    
    $aa_data[$id] = $percentages;
}

$aa_data_json = json_encode($aa_data);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Amino Acid Content Analysis</title>
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        #sequenceSelector {
            width: 100%;
            padding: 10px;
            margin: 20px 0;
        }
        #aaChart {
            width: 100%;
            height: 600px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Amino Acid Content Analysis: <?= htmlspecialchars($row['protein_family']) ?></h1>
        <p><strong>Job ID:</strong> <?= htmlspecialchars($job_id) ?></p>
        
        <select id="sequenceSelector">
            <?php foreach (array_keys($aa_data) as $id): ?>
                <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($id) ?></option>
            <?php endforeach; ?>
        </select>
        
        <div id="aaChart"></div>
    </div>

    <script>
        const aaData = <?= $aa_data_json ?>;
        
        function updateChart(selectedId) {
            const data = [{
                x: Object.keys(aaData[selectedId]),
                y: Object.values(aaData[selectedId]),
                type: 'bar',
                marker: { color: '#007BFF' }
            }];
            
            const layout = {
                title: `Amino Acid Composition: ${selectedId}`,
                xaxis: { title: 'Amino Acid' },
                yaxis: { title: 'Percentage (%)' },
                hovermode: 'closest'
            };
            
            Plotly.react('aaChart', data, layout);
        }
        
        // Initial chart load
        const initialId = Object.keys(aaData)[0];
        updateChart(initialId);
        
        // Update chart on selection change
        document.getElementById('sequenceSelector').addEventListener('change', function(e) {
            updateChart(e.target.value);
        });
    </script>
</body>
</html>
