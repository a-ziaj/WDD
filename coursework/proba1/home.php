<?php
session_start();
require_once "includes/db.php";

// Turn on error reporting while developing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $protein = $_POST['protein_family'];
    $taxon = $_POST['taxonomic_group'];

    // Insert job metadata
    $stmt = $pdo->prepare("INSERT INTO user_jobs (protein_family, taxonomic_group) VALUES (?, ?)");
    $stmt->execute([$protein, $taxon]);
    $job_id = $pdo->lastInsertId();

    // Set up file paths
    $outdir = "/job_outputs";
    $fasta = "$outdir/job_{$job_id}.fasta";
    $aln = "$outdir/job_{$job_id}.aln";
    $motifs = "$outdir/job_{$job_id}_motifs.txt";

    // Prepare EDirect search query
    $query = escapeshellarg("$protein AND $taxon");
    $bash = <<<BASH
#!/bin/bash
export PATH=\$PATH:/usr/bin:/usr/local/bin
esearch -db protein -query $query | efetch -format fasta > "$fasta"

esearch -db protein -query $query | efetch -format fasta 

if [ -s "$fasta" ]; then
    clustalo -i "$fasta" -o "$aln" --outfmt=clu --force
    patmatmotifs -sequence "$fasta" -outfile "$motifs"
fi
BASH;

    // Execute the shell script

    shell_exec($bash);

    // Read and combine results
    $fasta_data = file_exists($fasta) ? file_get_contents($fasta) : "No FASTA data found.";
    $aln_data = file_exists($aln) ? file_get_contents($aln) : "No alignment data found.";
    $motif_data = file_exists($motifs) ? file_get_contents($motifs) : "No motif data found.";

    $results = <<<TEXT
=== FASTA Sequences ===
$fasta_data

=== Alignment (Clustal Omega) ===
$aln_data

=== Motif Scan (PROSITE via patmatmotifs) ===
$motif_data
TEXT;

    // Store results in DB
    $stmt = $pdo->prepare("UPDATE user_jobs SET results = ? WHERE job_id = ?");
    $stmt->execute([$results, $job_id]);

    // Redirect to results page
    header("Location: results.php?job_id=$job_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Protein Job</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <h1>Protein Sequence Analysis</h1>
    <form action="home.php" method="post">
        <label for="protein_family">Protein Family:</label>
        <input type="text" id="protein_family" name="protein_family" required>
        <br>
        <label for="taxonomic_group">Taxonomic Group:</label>
        <input type="text" id="taxonomic_group" name="taxonomic_group" required>
        <br>
        <input type="submit" value="Submit">
    </form>
</div>
</body>
</html>

