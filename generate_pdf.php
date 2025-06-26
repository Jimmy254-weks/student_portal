<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/University.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if student_id is provided
if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
    die('Invalid student ID');
}

$student_id = (int) $_GET['student_id'];

// Initialize classes
$user = new User();
$student = new Student();
$university = new University();

// Get user and student data
$current_user = $user->getUserById($_SESSION['user_id'] ?? 0);
$student_profile = $student->getStudentByUserId($_SESSION['user_id'] ?? 0);

// Verify the requested student_id matches the logged-in user's student profile
if (!$student_profile || $student_profile->id != $student_id) {
    die('Unauthorized access');
}

// Get all required data
$university_info = $university->getUniversityDetails();
$invoice_number = 'INV-' . date('Ymd') . '-' . str_pad($student_profile->id, 5, '0', STR_PAD_LEFT);
$filename = 'Proforma - ' . $student_profile->admission_no;
$fees = $student->getStudentFees($student_profile->id);
$fee_summary = $student->calculateTotalFees($student_profile->id);
$courses = $student->getStudentCourses($student_profile->id) ?: [];
$payments = $student->getStudentPayments($student_profile->id);

// Determine academic period
$current_month = date('n');
$semester = ($current_month >= 1 && $current_month <= 6) ? 'First Semester' : 'Second Semester';
$academic_year = date('Y') . '/' . (date('Y') + 1);

// HTML content
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $filename . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { margin-bottom: 20px; }
        .university-info { margin-bottom: 15px; }
        .invoice-info { text-align: right; }
        .section { margin-bottom: 15px; }
        .section-title { font-weight: bold; margin-bottom: 5px; border-bottom: 1px solid #ddd; }
        .bordered { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table th { background-color: #f5f5f5; text-align: left; padding: 5px; border: 1px solid #ddd; }
        table td { padding: 5px; border: 1px solid #ddd; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background-color: #f5f5f5; }
        .footer { margin-top: 30px; }
        .signature { margin-top: 50px; }
        .logo { max-height: 80px; margin-bottom: 10px; }
        
        @media print {
            body { padding: 20px; }
            .no-print { display: none !important; }
            @page {
                size: A4;
                margin: 10mm;
            }
        }
        
        .print-controls {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
        
        .print-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="header">
        <div style="display: flex; justify-content: space-between;">
            <div class="university-info">
                ' . (!empty($university_info->logo_path) ? '<img src="' . htmlspecialchars($university_info->logo_path) . '" class="logo">' : '') . '
                <h3>' . htmlspecialchars($university_info->name) . '</h3>
                <p>' . htmlspecialchars($university_info->address) . '</p>
                <p>' . htmlspecialchars($university_info->city) . ', ' . htmlspecialchars($university_info->country) . '</p>
                <p>Tel: ' . htmlspecialchars($university_info->phone) . ' | Email: ' . htmlspecialchars($university_info->email) . '</p>
            </div>
            <div class="invoice-info">
                <h3>PROFORMA INVOICE #' . htmlspecialchars($invoice_number) . '</h3>
                <p><strong>Date:</strong> ' . date('F j, Y') . '</p>
                <p><strong>Due Date:</strong> ' . date('F j, Y', strtotime('+30 days')) . '</p>
                <p><strong>Student ID:</strong> ' . htmlspecialchars($student_profile->admission_no) . '</p>
            </div>
        </div>
    </div>

    <div class="section">
        <div style="display: flex; justify-content: space-between; gap: 15px;">
            <div class="bordered" style="flex: 1;">
                <h4 class="section-title">Bill To:</h4>
                <p><strong>' . htmlspecialchars($student_profile->first_name . ' ' . $student_profile->last_name) . '</strong></p>
                <p>' . htmlspecialchars($student_profile->address) . '</p>
                <p>' . htmlspecialchars($student_profile->county) . '</p>
                <p>Phone: ' . htmlspecialchars($student_profile->phone) . '</p>
                <p>Email: ' . htmlspecialchars($current_user->email) . '</p>
            </div>
            <div class="bordered" style="flex: 1;">
                <h4 class="section-title">Academic Information:</h4>
                ' . (!empty($courses) ? '<p><strong>Program:</strong> ' . htmlspecialchars($courses[0]->code . ' - ' . $courses[0]->name) . '</p>' : '') . '
                <p><strong>Academic Year:</strong> ' . htmlspecialchars($academic_year) . '</p>
                <p><strong>Semester:</strong> ' . htmlspecialchars($semester) . '</p>
            </div>
        </div>
    </div>

    <div class="section">
        <h4 class="section-title">Fee Breakdown</h4>
        <table>
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
        $status_class = '';
        if ($fee->status == 'paid')
            $status_class = 'color: green;';
        elseif ($fee->status == 'partial')
            $status_class = 'color: orange;';
        else
            $status_class = 'color: red;';

        $html .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td>' . htmlspecialchars($fee->description) . '</td>
                    <td class="text-right">' . number_format($fee->amount, 2) . '</td>
                    <td>' . date('M j, Y', strtotime($fee->due_date)) . '</td>
                    <td style="' . $status_class . '">' . ucfirst($fee->status) . '</td>
                </tr>';
    }
} else {
    $html .= '
                <tr>
                    <td colspan="5" class="text-center">No fee records found</td>
                </tr>';
}

$html .= '
                <tr class="total-row">
                    <td colspan="2" class="text-right">TOTAL BILLED:</td>
                    <td class="text-right">' . number_format($fee_summary->total_billed, 2) . '</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="total-row">
                    <td colspan="2" class="text-right">TOTAL PAID:</td>
                    <td class="text-right">' . number_format($fee_summary->total_paid, 2) . '</td>
                    <td colspan="2"></td>
                </tr>
                <tr style="background-color: #e9f7ef; font-weight: bold;">
                    <td colspan="2" class="text-right">BALANCE DUE:</td>
                    <td class="text-right">' . number_format($fee_summary->total_billed - $fee_summary->total_paid, 2) . '</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h4 class="section-title">Payment Instructions</h4>
        <div class="bordered">
            <ol>
                <li>M-Pesa: Paybill ' . htmlspecialchars($university_info->paybill_number) . ', Account: ' . htmlspecialchars($student_profile->admission_no) . '</li>
                <li>Bank Transfer: ' . htmlspecialchars($university_info->bank_name) . ', A/C: ' . htmlspecialchars($university_info->bank_account_number) . ', Name: ' . htmlspecialchars($university_info->bank_account_name) . '</li>
                <li>Payments must be made by the due date to avoid late payment penalties</li>
            </ol>
            <p style="color: red;"><strong>Note:</strong> This is a proforma invoice. Official receipts will be issued upon payment.</p>
        </div>
    </div>';

if (!empty($payments)) {
    $html .= '
    <div class="section">
        <h4 class="section-title">Payment History</h4>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-right">Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Receipt No.</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($payments as $payment) {
        $html .= '
                <tr>
                    <td>' . date('M j, Y', strtotime($payment->payment_date)) . '</td>
                    <td class="text-right">' . number_format($payment->amount, 2) . '</td>
                    <td>' . htmlspecialchars($payment->payment_method) . '</td>
                    <td>' . htmlspecialchars($payment->reference_number) . '</td>
                    <td>' . htmlspecialchars($payment->receipt_number ?? 'N/A') . '</td>
                </tr>';
    }

    $html .= '
            </tbody>
        </table>
    </div>';
}

$html .= '
    <div class="footer">
        <div style="display: flex; justify-content: space-between;">
            <div class="signature" style="width: 45%;">
                <p>Student Signature: _________________________</p>
                <p>Date: ' . date('d/m/Y') . '</p>
            </div>
            <div class="signature" style="width: 45%; text-align: right;">
                <p>For: ' . htmlspecialchars($university_info->name) . '</p>
                <p>Authorized Signature: _________________________</p>
            </div>
        </div>
    </div>
    
    <script>
        // Automatically trigger print dialog when page loads (optional)
        // window.addEventListener("load", function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 1000);
        // });
        
        // Set the PDF filename when printing
        window.onbeforeprint = function() {
            document.title = "' . $filename . '";
        };
        
        // Reset title after printing
        window.onafterprint = function() {
            document.title = "' . $filename . '";
        };
    </script>
</body>
</html>';

// Output the HTML
echo $html;
exit;