<?php
// Include the database login details
require_once('/home/s2713107/login.php');

try {
    // Establish the database connection using PDO
    $dsn = "mysql:host=$hostname;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Retrieve and sanitize user inputs
    $protein_family = filter_input(INPUT_POST, 'protein_family', FILTER_SANITIZE_STRING);
    $taxonomic_group = filter_input(INPUT_POST, 'taxonomic_group', FILTER_SANITIZE_STRING);

    // Store the job details in a cookie for future reference
    $jobDetails = json_encode(['protein_family' => $protein_family, 'taxonomic_group' => $taxonomic_group]);
    setcookie('user_job', $jobDetails, time() + (86400 * 30), "/"); // Cookie expires in 30 days

    // Example SQL query to insert the job into a 'jobs' table
    $stmt = $pdo->prepare("INSERT INTO jobs (protein_family, taxonomic_group, submission_time) VALUES (:protein_family, :taxonomic_group, NOW())");
    $stmt->bindParam(':protein_family', $protein_family);
    $stmt->bindParam(':taxonomic_group', $taxonomic_group);
    $stmt->execute();

    // Redirect back to the home page
    header("Location: home.php");
    exit;
} catch (PDOException $e) {
    // Handle database connection errors
    echo "Database error: " . htmlspecialchars($e->getMessage());
    exit;
}
?>

