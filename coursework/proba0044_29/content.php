<?php
require_once 'includes/db_connect.php';

if (!isset($_GET['job_id'])) {
    die("No job ID provided.");
}

$job_id = $_GET['job_id'];
$subset = isset($_GET['subset']) ? (int)$_GET['subset'] : null;

// Check if we need to generate a report
if (isset($_GET['generate_report'])) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="aa_content_report_' . $job_id . '.txt"');
    
    $stmt = $pdo->prepare("SELECT * FROM user_jobs WHERE job_id = ?");
    $stmt->execute([$job_id]);
    $row = $stmt->fetch();

    if (!$row) {
        die("No job found.");
    }

    // Determine which FASTA file to use
    if ($subset) {
        $FASTA_FILE = "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_subset_{$subset}.fasta";
    } else {
        $FASTA_FILE = "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_sequences.fasta";
    }

    if (!file_exists($FASTA_FILE)) {
        die("FASTA file not found.");
    }

    // Basic report header
    echo "Amino Acid Content Analysis Report\n";
    echo "=================================\n\n";
    echo "Job ID: $job_id\n";
    echo "Protein Family: " . $row['protein_family'] . "\n";
    echo "Taxonomic Group: " . $row['taxonomic_group'] . "\n";
    echo "Date: " . date('Y-m-d H:i:s') . "\n";
    if ($subset) {
        echo "Subset: First $subset sequences\n";
    }
    echo "\n";

    // Parse FASTA file and calculate percentages
    $sequences = [];
    $current_id = '';
    $current_seq = '';
    $amino_acids = str_split('ACDEFGHIKLMNPQRSTVWY');

    $file = fopen($FASTA_FILE, 'r');
    while (($line = fgets($file)) !== false) {
        $line = trim($line);
        if (strpos($line, '>') === 0) {
            if ($current_id !== '') {
                $sequences[$current_id] = $current_seq;
            }
            $current_id = substr($line, 1);
            $current_seq = '';
        } else {
            $current_seq .= strtoupper($line);
        }
    }
    if ($current_id !== '') {
        $sequences[$current_id] = $current_seq;
    }
    fclose($file);

    // Generate report content
    foreach ($sequences as $id => $seq) {
        $total = strlen($seq);
        $counts = array_fill_keys($amino_acids, 0);

        foreach (str_split($seq) as $aa) {
            if (isset($counts[$aa])) {
                $counts[$aa]++;
            }
        }

        echo "Sequence: $id\n";
        echo "Length: $total amino acids\n";
        echo "Amino Acid Composition:\n";
        
        foreach ($counts as $aa => $count) {
            $percentage = $total > 0 ? ($count / $total) * 100 : 0;
            echo sprintf("  %s: %6.2f%% (%d/%d)\n", $aa, $percentage, $count, $total);
        }
        
        echo str_repeat("-", 60) . "\n\n";
    }
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM user_jobs WHERE job_id = ?");
$stmt->execute([$job_id]);
$row = $stmt->fetch();

if (!$row) {
    die("No job found.");
}

// Determine which FASTA file to use
if ($subset) {
    $FASTA_FILE = "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_subset_{$subset}.fasta";
} else {
    $FASTA_FILE = "tmp/{$row['protein_family']}_{$row['taxonomic_group']}_sequences.fasta";
}

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
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        #sequenceSelector { width: 100%; padding: 10px; margin: 20px 0; }
        #aaChart { width: 100%; height: 600px; }
        .subset-info { background: #e8f5e9; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .download-btn { 
            display: inline-block; 
            background: #27ae60; 
            color: white; 
            padding: 10px 20px; 
            text-decoration: none; 
            border-radius: 5px; 
            margin-top: 20px; 
            border: none;
            cursor: pointer;
            font-size: 1em;
        }
        .download-btn:hover {
            background: #219653;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Amino Acid Content Analysis: <?= htmlspecialchars($row['protein_family']) ?></h1>
        <p><strong>Job ID:</strong> <?= htmlspecialchars($job_id) ?></p>

        <?php if ($subset): ?>
            <div class="subset-info">
                <strong>Using subset:</strong> First <?= $subset ?> sequences
            </div>
        <?php endif; ?>

        <select id="sequenceSelector">
            <?php foreach (array_keys($aa_data) as $id): ?>
                <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($id) ?></option>
            <?php endforeach; ?>
        </select>

        <div id="aaChart"></div>

        <a href="?job_id=<?= $job_id ?>&subset=<?= $subset ?>&generate_report=1" class="download-btn">
            Generate TXT Report
        </a>
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
