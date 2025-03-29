<?php
require_once 'includes/db_connect.php';

if (!isset($_GET['job_id'])) {
    die("No job ID provided.");
}

$job_id = $_GET['job_id'];

// AJAX endpoints
if (isset($_GET['check_progress'])) {
    header('Content-Type: application/json');
    
    $progress_file = "tmp/{$job_id}_progress.txt";
    $response = ['progress' => 0];
    
    if (file_exists($progress_file)) {
        $content = trim(file_get_contents($progress_file));
        if (strpos($content, 'error:') === 0) {
            $response = ['error' => substr($content, 6)];
        } else {
            $response = ['progress' => (int)$content];
        }
    }
    
    // Debug output
    error_log("Progress check for $job_id: " . json_encode($response));
    echo json_encode($response);
    exit;
}

if (isset($_GET['get_log'])) {
    header('Content-Type: text/plain');
    $log_type = $_GET['get_log'];
    $log_file = "tmp/{$job_id}_{$log_type}.log";
    
    if (file_exists($log_file)) {
        readfile($log_file);
    } else {
        echo "Waiting for log data...";
    }
    exit;
}

// Normal page load
$stmt = $pdo->prepare("SELECT * FROM user_jobs WHERE job_id = ?");
$stmt->execute([$job_id]);
$row = $stmt->fetch();

if (!$row) {
    die("No job found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Analysis Progress</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .progress-container {
            width: 100%;
            background-color: #f1f1f1;
            border-radius: 5px;
            margin: 20px 0;
        }
        .progress-bar {
            height: 30px;
            background-color: #4CAF50;
            border-radius: 5px;
            width: 0%;
            transition: width 0.3s;
            text-align: center;
            line-height: 30px;
            color: white;
        }
        .log-container {
            max-height: 200px;
            overflow-y: auto;
            background: #f5f5f5;
            padding: 10px;
            border: 1px solid #ddd;
            margin-bottom: 15px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <h1>Running Analysis for Job: <?= htmlspecialchars($job_id) ?></h1>
    
    <div class="progress-container">
        <div id="progressBar" class="progress-bar">0%</div>
    </div>
    
    <h3>Clustal Omega Log:</h3>
    <div class="log-container" id="clustalLog">Initializing...</div>
    
    <h3>Plotcon Log:</h3>
    <div class="log-container" id="plotconLog">Initializing...</div>
    
    <div id="resultsContainer"></div>

    <script>
        const jobId = '<?= $job_id ?>';
        
        function updateProgress() {
            fetch(`conservation.php?job_id=${jobId}&check_progress=1`)
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    // Update progress bar
                    const progressBar = document.getElementById('progressBar');
                    if (data.error) {
                        progressBar.style.backgroundColor = '#f44336';
                        progressBar.textContent = `Error: ${data.error}`;
                        Swal.fire('Error', data.error, 'error');
                        return;
                    }
                    
                    const progress = data.progress;
                    progressBar.style.width = `${progress}%`;
                    progressBar.textContent = `${progress}%`;
                    
                    // Update logs every 2 seconds
                    updateLogs();
                    
                    // Check if complete
                    if (progress >= 100) {
                        loadResults();
                    } else {
                        setTimeout(updateProgress, 2000);
                    }
                })
                .catch(error => {
                    console.error('Progress check failed:', error);
                    setTimeout(updateProgress, 2000);
                });
        }
        
        function updateLogs() {
            // Update Clustal log
            fetch(`conservation.php?job_id=${jobId}&get_log=clustalo`)
                .then(response => response.text())
                .then(text => {
                    const logElement = document.getElementById('clustalLog');
                    logElement.textContent = text;
                    logElement.scrollTop = logElement.scrollHeight;
                });
            
            // Update Plotcon log
            fetch(`conservation.php?job_id=${jobId}&get_log=plotcon`)
                .then(response => response.text())
                .then(text => {
                    const logElement = document.getElementById('plotconLog');
                    logElement.textContent = text;
                    logElement.scrollTop = logElement.scrollHeight;
                });
        }
        
        function loadResults() {
            fetch(`get_results.php?job_id=${jobId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('resultsContainer').innerHTML = html;
                    Swal.fire({
                        title: 'Analysis Complete!',
                        icon: 'success',
                        timer: 3000
                    });
                });
        }
        
        // Start progress tracking
        updateProgress();
        updateLogs();
    </script>
</body>
</html>
