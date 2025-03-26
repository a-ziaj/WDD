<?php
// Start the session
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Protein Sequence Fetcher</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Protein Sequence Fetcher</h1>
    <form action="results.php" method="post">
        <label for="protein_family">Protein Family:</label>
        <input type="text" id="protein_family" name="protein_family" required>
        <br>
        <label for="taxonomic_group">Taxonomic Group:</label>
        <input type="text" id="taxonomic_group" name="taxonomic_group" required>
        <br>
        <input type="submit" value="Fetch Sequences">
    </form>
</body>
</html>

