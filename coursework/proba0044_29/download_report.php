<?php
include 'db_connect.php';

$job_id = $_GET['job_id'] ?? '';
$type = $_GET['type'] ?? '';

if (empty($job_id) || empty($type)) {
    die("Invalid parameters");
}

// Generate PDF report using a library like TCPDF or FPDF
// This is a simplified example
require_once('tcpdf/tcpdf.php');

$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, "Phylogenetic Tree Report - Job $job_id", 0, 1);

// Get all trees for this job
$stmt = $pdo->prepare("SELECT * FROM tree_results WHERE job_id = ?");
$stmt->execute([$job_id]);

while ($row = $stmt->fetch()) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, "Method: {$row['method']}", 0, 1);
    
    // Add tree image (simplified - would need proper image handling)
    $pdf->Image($row['image_file'], 15, null, 180);
    $pdf->AddPage();
}

$pdf->Output("tree_report_$job_id.pdf", 'D');
