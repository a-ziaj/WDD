<?php
require_once 'includes/db_connect.php';

// Example dataset
$example_protein = 'glucose-6-phosphatase';
$example_taxon = 'Aves';

// You can store the example analysis pre-generated in your DB
$stmt = $pdo->prepare("SELECT * FROM user_jobs WHERE protein_family=? AND taxonomic_group=? LIMIT 1");
$stmt->execute([$example_protein, $example_taxon]);
$example = $stmt->fetch();

echo "<h1>Example Dataset: $example_protein from $example_taxon</h1>";
echo "<pre>{$example['results']}</pre>";
?>

