<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head><title>Protein Job Submitter</title></head>
<body>
    <h1>Submit a Protein Sequence Job</h1>
    <form action="process.php" method="post">
        <label>Protein Family: <input type="text" name="protein_family" required></label><br><br>
        <label>Taxonomic Group: <input type="text" name="taxonomic_group" required></label><br><br>
        <input type="submit" value="Run Analysis">
    </form>
    <br>
    <a href="example.php">Try Example Dataset</a> |
    <a href="revisit.php">Revisit Past Jobs</a>
</body>
</html>

