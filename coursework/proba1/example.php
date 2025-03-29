<?php
require_once "includes/db.php";

$protein = "glucose-6-phosphatase";
$taxon = "Aves";

$stmt = $pdo->prepare("INSERT INTO user_jobs (protein_family, taxonomic_group, results) VALUES (?, ?, ?)");
$stmt->execute([$protein, $taxon, "Preprocessed example data"]);
$job_id = $pdo->lastInsertId();

header("Location: results.php?job_id=$job_id");
exit;
?>

