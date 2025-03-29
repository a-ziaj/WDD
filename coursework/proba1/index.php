<?php
require_once "includes/db.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Protein Sequence Submission</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>Submit a Protein Job</h1>
    <form action="home.php" method="POST">
        <label>Protein Family:</label>
        <input type="text" name="protein_family" required>

        <label>Taxonomic Group:</label>
        <input type="text" name="taxonomic_group" required>

        <button type="submit">Submit Job</button>
    </form>
    <p>Or try the <a href="example.php">example dataset</a>.</p>
</div>
</body>
</html>

