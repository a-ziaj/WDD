<?php
session_start();
require_once 'includes/db_connect.php';

$family = $_POST['protein_family'];
$group = $_POST['taxonomic_group'];

// Escape for shell safety
$family_safe = escapeshellarg($family);
$group_safe = escapeshellarg($group);

// Call shell script with protein family and taxonomic group
$results = shell_exec("./run_pipeline.sh $family_safe $group_safe");

// Save to DB
$job_id = uniqid("job_");
$stmt = $pdo->prepare("INSERT INTO user_jobs (job_id, protein_family, taxonomic_group, results) VALUES (?, ?, ?, ?)");
$stmt->execute([$job_id, $family, $group, $results]);

// Save session and redirect
setcookie("last_job", $job_id, time() + (86400 * 30), "/");
header("Location: results.php?job_id=$job_id");
exit;
?>

