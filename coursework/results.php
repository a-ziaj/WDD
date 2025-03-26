<?php
session_start();
include('/home/s2713107/includes/login.php');

try {
    $dsn = "mysql:host=$hostname;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $protein_family = $_POST['protein_family'];
        $taxonomic_group = $_POST['taxonomic_group'];

        // Perform your bioinformatics analysis here
        // For demonstration, we'll simulate results
        $analysis_results = "Simulated analysis results for $protein_family in $taxonomic_group.";

        // Store the job in the database
        $stmt = $pdo->prepare("INSERT INTO user_jobs (protein_family, taxonomic_group, results) VALUES (?, ?, ?)");
        $stmt->execute([$protein_family, $taxonomic_group, $analysis_results]);

        // Retrieve the job ID
        $job_id = $pdo->lastInsertId();

        // Store the job ID in a cookie for future reference
        setcookie("job_$job_id", $job_id, time() + (86400 * 30), "/"); // 30-day expiration

        // Display the results
        echo "<h1>Analysis Results</h1>";
        echo "<p>$analysis_results</p>";
        echo "<p>Your job ID is: $job_id. You can revisit this job later.</p>";
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

