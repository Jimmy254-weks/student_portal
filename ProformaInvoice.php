<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/University.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Initialize classes
$user = new User();
$student = new Student();
$university = new University();

$page_title = 'Proforma Invoice';
$page_css = 'invoice.css';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user and student data
$current_user = $user->getUserById($_SESSION['user_id'] ?? 0);
$student_profile = $student->getStudentByUserId($_SESSION['user_id'] ?? 0);

if (!$current_user || !$student_profile) {
    flash('error', 'Please complete your profile first');
    redirect('profile_setup.php');
}

// Get all required data
$university_info = $university->getUniversityDetails();
$invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($student_profile->id, 5, '0', STR_PAD_LEFT);
$fees = $student->getStudentFees($student_profile->id);
$fee_summary = $student->calculateTotalFees($student_profile->id);
$courses = $student->getStudentCourses($student_profile->id) ?: [];
$payments = $student->getStudentPayments($student_profile->id);

// Determine academic period
$current_month = date('n');
$semester = ($current_month >= 1 && $current_month <= 6) ? 'First Semester' : 'Second Semester';
$academic_year = date('Y') . '/' . (date('Y') + 1);

// Check if PDF generation is requested
if (isset($_GET['generate_pdf']) && $_GET['generate_pdf'] == '1') {
    // Prepare PDF HTML
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Proforma Invoice - ' . htmlspecialchars($invoice_number) . '</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .header { margin-bottom: 20px; }
            .logo { max-height: 80px; margin-bottom: 10px; }
            .text-right { text-align: right; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
            table th, table td { border: 1px solid #ddd; padding: 8px; }
            table th { background-color: #f5f5f5; text-align: left; }
            .total-row { font-weight: bold; background-color: #f5f5f5; }
            .negative-balance { color: #d9534f; }
            .positive-balance { color: #5cb85c; }
            .border-top { border-top: 1px solid #ddd; }
            .border { border: 1px solid #ddd; padding: 10px; }
            .text-muted { color: #6c757d; }
            .text-danger { color: #dc3545; }
            .bg-light { background-color: #f8f9fa; }
            .rounded { border-radius: 0.25rem; }
            .mb-0 { margin-bottom: 0; }
            .mb-1 { margin-bottom: 0.25rem; }
            .mb-3 { margin-bottom: 1rem; }
            .mb-4 { margin-bottom: 1.5rem; }
            .mt-4 { margin-top: 1.5rem; }
            .p-3 { padding: 1rem; }
            .p-4 { padding: 1.5rem; }
            .small { font-size: 80%; }
        </style>
    </head>
    <body>
        <div class="header">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="60%" valign="top">
                        ' . (!empty($university_info->logo_path) ? '<img src="' . htmlspecialchars($university_info->logo_path) . '" class="logo">' : '') . '
                        <h3>' . htmlspecialchars($university_info->name) . '</h3>
                        <p>' . htmlspecialchars($university_info->address) . '</p>
                        <p>' . htmlspecialchars($university_info->city) . ', ' . htmlspecialchars($university_info->country) . '</p>
                        <p>Tel: ' . htmlspecialchars($university_info->phone) . ' | Email: ' . htmlspecialchars($university_info->email) . '</p>
                    </td>
                    <td width="40%" valign="top" style="text-align: right;">
                        <h3>PROFORMA INVOICE #' . htmlspecialchars($invoice_number) . '</h3>
                        <p><strong>Date:</strong> ' . date('F j, Y') . '</p>
                        <p><strong>Due Date:</strong> ' . date('F j, Y', strtotime('+30 days')) . '</p>
                        <p><strong>Student ID:</strong> ' . htmlspecialchars($student_profile->admission_no) . '</p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Student Information -->
        <div style="margin-bottom: 20px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="50%" valign="top">
                        <div class="border p-3">
                            <h5>Bill To:</h5>
                            <p class="mb-1">
                                <strong>' . htmlspecialchars($student_profile->first_name . ' ' . $student_profile->last_name) . '</strong>
                            </p>
                            <p class="mb-1">' . htmlspecialchars($student_profile->address) . '</p>
                            <p class="mb-1">' . htmlspecialchars($student_profile->county) . '</p>
                            <p class="mb-1">Phone: ' . htmlspecialchars($student_profile->phone) . '</p>
                            <p class="mb-0">Email: ' . htmlspecialchars($current_user->email) . '</p>
                        </div>
                    </td>
                    <td width="50%" valign="top">
                        <div class="border p-3">
                            <h5>Academic Information:</h5>';

    if (!empty($courses)) {
        $html .= '
                            <p class="mb-1"><strong>Program:</strong>
                                ' . htmlspecialchars($courses[0]->code . ' - ' . $courses[0]->name) . '
                            </p>';
    }

    $html .= '
                            <p class="mb-1"><strong>Academic Year:</strong> ' . htmlspecialchars($academic_year) . '</p>
                            <p class="mb-0"><strong>Semester:</strong> ' . htmlspecialchars($semester) . '</p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Fees Table -->
        <div>
            <h4>Fee Details</h4>
            <table cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fee Description</th>
                        <th class="text-right">Amount (KES)</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';

    if (!empty($fees)) {
        foreach ($fees as $index => $fee) {
            $status_class = $fee->status == 'paid' ? 'success' : ($fee->status == 'partial' ? 'warning' : 'danger');
            $html .= '
                    <tr>
                        <td>' . ($index + 1) . '</td>
                        <td>' . htmlspecialchars($fee->description) . '</td>
                        <td class="text-right">' . number_format($fee->amount, 2) . '</td>
                        <td>' . date('M j, Y', strtotime($fee->due_date)) . '</td>
                        <td><span style="color: ' . ($status_class == 'success' ? '#28a745' : ($status_class == 'warning' ? '#ffc107' : '#dc3545')) . '">' . ucfirst($fee->status) . '</span></td>
                    </tr>';
        }
    } else {
        $html .= '
                    <tr>
                        <td colspan="5" class="text-center text-muted" style="padding: 20px 0;">
                            No fee records found. Please contact the finance office.
                        </td>
                    </tr>';
    }

    $html .= '
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2" class="text-right"><strong>TOTAL BILLED:</strong></td>
                        <td class="text-right"><strong>' . number_format($fee_summary->total_billed, 2) . '</strong></td>
                        <td colspan="2"></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="2" class="text-right"><strong>TOTAL PAID:</strong></td>
                        <td class="text-right"><strong>' . number_format($fee_summary->total_paid, 2) . '</strong></td>
                        <td colspan="2"></td>
                    </tr>
                    <tr style="font-weight: bold; background-color: #e9ecef;">
                        <td colspan="2" class="text-right"><strong>BALANCE DUE:</strong></td>
                        <td class="text-right"><strong>' . number_format($fee_summary->total_billed - $fee_summary->total_paid, 2) . '</strong></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Payment Instructions -->
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <h5 style="margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Payment Instructions</h5>
            <ol style="margin-bottom: 10px;">
                <li>M-Pesa: Paybill ' . htmlspecialchars($university_info->paybill_number) . ',
                    Account: ' . htmlspecialchars($student_profile->admission_no) . '</li>
                <li>Bank Transfer: ' . htmlspecialchars($university_info->bank_name) . ',
                    A/C: ' . htmlspecialchars($university_info->bank_account_number) . ',
                    Name: ' . htmlspecialchars($university_info->bank_account_name) . '</li>
                <li>Payments must be made by the due date to avoid late payment penalties</li>
            </ol>
            <p style="margin-bottom: 0; color: #dc3545;">
                <strong>Note:</strong> This is a proforma invoice. Official receipts will be issued upon payment.
            </p>
        </div>';

    // Payment History
    if (!empty($payments)) {
        $html .= '
        <div style="margin-bottom: 20px;">
            <h5 style="margin-bottom: 10px;"><i class="fas fa-history"></i> Payment History</h5>
            <table cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th style="border: 1px solid #ddd; padding: 8px;">Date</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">Amount</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">Method</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">Reference</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">Receipt No.</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($payments as $payment) {
            // Generate receipt number if not exists
            $receipt_number = $payment->receipt_number ?? generateReceiptNumber($payment->id, $payment->payment_date);

            $html .= '
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">' . date('M j, Y', strtotime($payment->payment_date)) . '</td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">' . number_format($payment->amount, 2) . '</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($payment->payment_method ?? 'N/A') . '</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($payment->reference_number ?? 'N/A') . '</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($receipt_number) . '</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>
        </div>';
    }

    // Signatures
    $html .= '
        <div style="margin-top: 30px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="50%" valign="top">
                        <div style="border-top: 1px solid #ddd; padding-top: 15px;">
                            <p style="margin-bottom: 5px;">Student Signature: _________________________</p>
                            <p style="margin-bottom: 0; font-size: 80%; color: #6c757d;">Date: ' . date('d/m/Y') . '</p>
                        </div>
                    </td>
                    <td width="50%" valign="top" style="text-align: right;">
                        <div style="border-top: 1px solid #ddd; padding-top: 15px;">
                            <p style="margin-bottom: 5px;">For: ' . htmlspecialchars($university_info->name) . '</p>
                            <p style="margin-bottom: 0;">Authorized Signature: _________________________</p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div style="text-align: center; font-size: 10px; color: #666; margin-top: 30px;">
            <p>Generated on ' . date('F j, Y H:i:s') . '</p>
            <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($university_info->name) . ' - All rights reserved</p>
        </div>
    </body>
    </html>';

    // Initialize DomPDF
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = 'Proforma_Invoice_' . $student_profile->admission_no . '_' . date('Ymd_His') . '.pdf';

    // Output PDF for download
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}

// Function to generate receipt number
function generateReceiptNumber($payment_id, $payment_date)
{
    $prefix = 'RCT';
    $year = date('Y', strtotime($payment_date));
    $month = date('m', strtotime($payment_date));
    $sequence = str_pad($payment_id, 5, '0', STR_PAD_LEFT);

    return $prefix . '-' . $year . $month . '-' . $sequence;
}

// If not generating PDF, continue to output HTML page normally
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Proforma Invoice</h4>
                <div>
                    <button class="btn btn-light btn-sm" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <a href="?generate_pdf=1" class="btn btn-secondary btn-sm ml-2">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- University Header -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <?php if (!empty($university_info->logo_path)): ?>
                        <img src="<?= htmlspecialchars($university_info->logo_path) ?>" alt="University Logo"
                            style="height: 80px;" class="mb-2">
                    <?php endif; ?>
                    <h5><?= htmlspecialchars($university_info->name) ?></h5>
                    <p class="mb-1"><?= htmlspecialchars($university_info->address) ?></p>
                    <p class="mb-1"><?= htmlspecialchars($university_info->city) ?>,
                        <?= htmlspecialchars($university_info->country) ?>
                    </p>
                    <p class="mb-1">Tel: <?= htmlspecialchars($university_info->phone) ?></p>
                    <p class="mb-0">Email: <?= htmlspecialchars($university_info->email) ?></p>
                </div>
                <div class="col-md-6 text-md-right">
                    <h5>INVOICE #<?= htmlspecialchars($invoice_number) ?></h5>
                    <p class="mb-1"><strong>Date:</strong> <?= date('F j, Y') ?></p>
                    <p class="mb-1"><strong>Due Date:</strong> <?= date('F j, Y', strtotime('+30 days')) ?></p>
                    <p class="mb-0"><strong>Student ID:</strong> <?= htmlspecialchars($student_profile->admission_no) ?>
                    </p>
                </div>
            </div>

            <!-- Student Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="border p-3">
                        <h5>Bill To:</h5>
                        <p class="mb-1">
                            <strong><?= htmlspecialchars($student_profile->first_name . ' ' . $student_profile->last_name) ?></strong>
                        </p>
                        <p class="mb-1"><?= htmlspecialchars($student_profile->address) ?></p>
                        <p class="mb-1"><?= htmlspecialchars($student_profile->county) ?></p>
                        <p class="mb-1">Phone: <?= htmlspecialchars($student_profile->phone) ?></p>
                        <p class="mb-0">Email: <?= htmlspecialchars($current_user->email) ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border p-3">
                        <h5>Academic Information:</h5>
                        <?php if (!empty($courses)): ?>
                            <p class="mb-1"><strong>Program:</strong>
                                <?= htmlspecialchars($courses[0]->code . ' - ' . $courses[0]->name) ?>
                            </p>
                        <?php endif; ?>
                        <p class="mb-1"><strong>Academic Year:</strong> <?= htmlspecialchars($academic_year) ?></p>
                        <p class="mb-0"><strong>Semester:</strong> <?= htmlspecialchars($semester) ?></p>
                    </div>
                </div>
            </div>

            <!-- Fees Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Fee Description</th>
                            <th class="text-right">Amount (KES)</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($fees)): ?>
                            <?php foreach ($fees as $index => $fee): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($fee->description) ?></td>
                                    <td class="text-right"><?= number_format($fee->amount, 2) ?></td>
                                    <td><?= date('M j, Y', strtotime($fee->due_date)) ?></td>
                                    <td>
                                        <span class="badge badge-<?=
                                            $fee->status == 'paid' ? 'success' :
                                            ($fee->status == 'partial' ? 'warning' : 'danger')
                                            ?>">
                                            <?= ucfirst($fee->status) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No fee records found. Please contact the finance office.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <th colspan="2" class="text-right">TOTAL BILLED:</th>
                            <th class="text-right"><?= number_format($fee_summary->total_billed, 2) ?></th>
                            <th colspan="2"></th>
                        </tr>
                        <tr class="table-active">
                            <th colspan="2" class="text-right">TOTAL PAID:</th>
                            <th class="text-right"><?= number_format($fee_summary->total_paid, 2) ?></th>
                            <th colspan="2"></th>
                        </tr>
                        <tr class="table-primary">
                            <th colspan="2" class="text-right">BALANCE DUE:</th>
                            <th class="text-right">
                                <?= number_format($fee_summary->total_billed - $fee_summary->total_paid, 2) ?>
                            </th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Payment Instructions -->
            <div class="bg-light p-4 rounded mb-4">
                <h5 class="mb-3"><i class="fas fa-info-circle"></i> Payment Instructions</h5>
                <ol class="mb-3">
                    <li>M-Pesa: Paybill <?= htmlspecialchars($university_info->paybill_number) ?>,
                        Account: <?= htmlspecialchars($student_profile->admission_no) ?></li>
                    <li>Bank Transfer: <?= htmlspecialchars($university_info->bank_name) ?>,
                        A/C: <?= htmlspecialchars($university_info->bank_account_number) ?>,
                        Name: <?= htmlspecialchars($university_info->bank_account_name) ?></li>
                    <li>Payments must be made by the due date to avoid late payment penalties</li>
                </ol>
                <p class="mb-0 text-danger">
                    <strong>Note:</strong> This is a proforma invoice. Official receipts will be issued upon payment.
                </p>
            </div>

            <!-- Payment History -->
            <?php if (!empty($payments)): ?>
                <div class="mb-4">
                    <h5 class="mb-3"><i class="fas fa-history"></i> Payment History</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Receipt No.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <?php
                                    // Generate receipt number if not exists
                                    $receipt_number = $payment->receipt_number ?? generateReceiptNumber($payment->id, $payment->payment_date);
                                    ?>
                                    <tr>
                                        <td><?= date('M j, Y', strtotime($payment->payment_date)) ?></td>
                                        <td class="text-right"><?= number_format($payment->amount, 2) ?></td>
                                        <td><?= htmlspecialchars($payment->payment_method ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($payment->reference_number ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($receipt_number) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Signatures -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="border-top pt-3">
                        <p class="mb-1">Student Signature: _________________________</p>
                        <p class="mb-0 text-muted small">Date: <?= date('d/m/Y') ?></p>
                    </div>
                </div>
                <div class="col-md-6 text-md-right">
                    <div class="border-top pt-3">
                        <p class="mb-1">For: <?= htmlspecialchars($university_info->name) ?></p>
                        <p class="mb-0">Authorized Signature: _________________________</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }

        .card,
        .card * {
            visibility: visible;
        }

        .card {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            border: none;
            box-shadow: none;
        }

        .no-print {
            display: none !important;
        }

        .table {
            page-break-inside: avoid;
        }
    }

    .card-header {
        background-color: #730000 !important;
    }

    .table thead th {
        background-color: #f8f9fa;
    }
</style>

<?php include 'includes/footer.php'; ?>