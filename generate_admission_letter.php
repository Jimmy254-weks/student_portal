<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'vendor/autoload.php'; // Path to TCPDF autoload

use TCPDF as TCPDF;

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Initialize classes
$user = new User();
$student = new Student();
$university = new University();

// Get user data
$current_user = $user->getUserById($_SESSION['user_id'] ?? 0);
$student_profile = $student->getStudentByUserId($_SESSION['user_id'] ?? 0);
$university_info = $university->getUniversityInfo();

// Verify we got valid results
if (!$current_user || !$student_profile || !$university_info) {
    flash('error', 'Unable to retrieve required information');
    redirect('dashboard.php');
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($university_info->name);
$pdf->SetTitle('Admission Letter - ' . $student_profile->admission_no);
$pdf->SetSubject('Admission Letter');
$pdf->SetKeywords('Admission, Letter, University');

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Add a page
$pdf->AddPage();

// University header
$header = '<div style="text-align: center;">
    <h1 style="color: #730000;">' . htmlspecialchars($university_info->name) . '</h1>
    <p>' . htmlspecialchars($university_info->address) . ', ' . htmlspecialchars($university_info->city) . '</p>
    <p>Tel: ' . htmlspecialchars($university_info->phone) . ' | Email: ' . htmlspecialchars($university_info->email) . '</p>
    <hr style="border-top: 2px solid #730000;">
</div>';

$pdf->writeHTML($header, true, false, true, false, '');

// Letter content
$content = '
<div style="text-align: right;">
    <p>Date: ' . date('F j, Y') . '</p>
    <p>Ref: ADM/' . date('Y') . '/' . str_pad($student_profile->id, 5, '0', STR_PAD_LEFT) . '</p>
</div>

<div style="margin-bottom: 20px;">
    <p>' . htmlspecialchars($student_profile->first_name . ' ' . $student_profile->last_name) . '</p>
    <p>' . htmlspecialchars($student_profile->address) . '</p>
    <p>' . htmlspecialchars($student_profile->county) . '</p>
</div>

<h2 style="text-align: center; color: #730000;">LETTER OF ADMISSION</h2>

<p>Dear ' . htmlspecialchars($student_profile->first_name) . ',</p>

<p>We are pleased to inform you that you have been offered admission to ' . htmlspecialchars($university_info->name) . ' for the academic year 2025/2026. You have been admitted to the following program:</p>

<div style="background-color: #f5f5f5; padding: 10px; margin: 10px 0;">
    <p><strong>Admission Number:</strong> ' . htmlspecialchars($student_profile->admission_no) . '</p>
    <p><strong>Program:</strong> Bachelor of Science in Computer Science</p>
    <p><strong>Duration:</strong> 4 Years (8 Semesters)</p>
    <p><strong>Mode of Study:</strong> Full Time</p>
</div>

<p>This admission is subject to the following conditions:</p>

<ol>
    <li>Payment of all required fees by the stipulated deadlines</li>
    <li>Submission of all original academic documents for verification</li>
    <li>Compliance with the university rules and regulations</li>
</ol>

<p>Congratulations on your admission. We look forward to welcoming you to our university community.</p>

<div style="margin-top: 50px;">
    <p>Yours sincerely,</p>
    
    <div style="margin-top: 40px;">
        <div style="width: 200px; border-bottom: 1px solid #000; margin-bottom: 5px;"></div>
        <p><strong>Prof. Jane Muthoni</strong></p>
        <p>Registrar (Academic Affairs)</p>
        <p>' . htmlspecialchars($university_info->name) . '</p>
    </div>
</div>';

$pdf->writeHTML($content, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('admission_letter_' . $student_profile->admission_no . '.pdf', 'D');