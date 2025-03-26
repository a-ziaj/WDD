<?php
session_start();

// Include database connection details
require_once '/home/s2713107/includes/login.php';

// Initialize variables
$protein_family = '';
$taxonomic_group = '';
$message = '';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $protein_family = trim($_POST['protein_family']);
    $taxonomic_group = trim($_POST['taxonomic_group']);

    // Validate inputs
    if (empty($protein_family) || empty($taxonomic_group)) {
        $message = 'Please fill in both fields.';
    } else {
        try {
            // Establish PDO connection
            $dsn = "mysql:host=$hostname;dbname=$database;charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Insert job into the database
            $stmt = $pdo->prepare("INSERT INTO user_jobs (protein_family, taxonomic_group) VALUES (:protein_family, :taxonomic_group)");
            $stmt->bindParam(':protein_family', $protein_family);
            $stmt->bindParam(':taxonomic_group', $taxonomic_group);
            $stmt->execute();

            // Retrieve the last inserted job ID
            $job_id = $pdo->lastInsertId();

            // Store job ID in a cookie
            setcookie('job_id', $job_id, time() + (86400 * 30), "/"); // 30-day expiration

            $message = 'Job submitted successfully. Your Job ID is ' . $job_id;
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Protein Family and Taxonomic Group Submission</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h2>Submit Protein Family and Taxonomic Group</h2>
    <?php if (!empty($message)): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <form action="home.php" method="post">
        <label for="protein_family">Protein Family:</label>
        <input type="text" id="protein_family" name="protein_family" value="<?php echo htmlspecialchars($protein_family); ?>" required>
        <br>
        <label for="taxonomic_group">Taxonomic Group:</label>
        <input type="text" id="taxonomic_group" name="taxonomic_group" value="<?php echo htmlspecialchars($taxonomic_group); ?>" required>
        <br>
        <input type="submit" value="Submit">
    </form>
</body>
</html>

