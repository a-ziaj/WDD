<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Protein Job Submitter</title>
    <style>
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: inline-block;
            width: 150px;
        }
    </style>
</head>
<body>
    <h1>Submit a Protein Sequence Job</h1>
    <form action="process.php" method="post">
        <div class="form-group">
            <label>Protein Family:</label>
            <input type="text" name="protein_family" required>
        </div>

        <div class="form-group">
            <label>Taxonomic Group:</label>
            <input type="text" name="taxonomic_group" required>
        </div>

        <div class="form-group">
            <input type="submit" value="Run Analysis">
        </div>
    </form>

    <br>
    <a href="example.php">Try Example Dataset</a> |
    <a href="revisit.php">Revisit Past Jobs</a>
</body>
</html>
