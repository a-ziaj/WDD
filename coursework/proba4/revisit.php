<?php
require_once 'includes/db_connect.php';

$stmt = $pdo->query("SELECT * FROM user_jobs ORDER BY created_at DESC LIMIT 20");

echo "<h1>Previous Jobs</h1><ul>";
while ($row = $stmt->fetch()) {
    echo "<li><a href='results.php?job_id={$row['job_id']}'>Protein: {$row['protein_family']}, Group: {$row['taxonomic_group']}</a></li>";
}
echo "</ul>";
?>



