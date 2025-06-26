<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/University.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Initialize classes
$student = new Student();
$university = new University();

// Get student profile
$student_profile = $student->getStudentByUserId($_SESSION['user_id'] ?? 0);

if (!$student_profile) {
    flash('profile_error', 'Please complete your profile first');
    redirect('profile_setup.php');
}

// Get payment receipts
$receipts = $student->getStudentPayments($student_profile->id);
$university_info = $university->getUniversityDetails();

// Check if PDF generation requested
if (isset($_GET['generate_pdf']) && is_numeric($_GET['generate_pdf'])) {
    $receipt_id = (int) $_GET['generate_pdf'];

    // Find the specific receipt
    $selected_receipt = null;
    foreach ($receipts as $receipt) {
        if ($receipt->id == $receipt_id) {
            $selected_receipt = $receipt;
            break;
        }
    }

    if ($selected_receipt) {
        // Function to convert numbers to words
        function convertNumberToWords($number)
        {
            $whole = floor($number);
            $fraction = round(($number - $whole) * 100);

            $conjunction = ' and ';
            $words = array();
            $dictionary = array(
                0 => 'zero',
                1 => 'one',
                2 => 'two',
                3 => 'three',
                4 => 'four',
                5 => 'five',
                6 => 'six',
                7 => 'seven',
                8 => 'eight',
                9 => 'nine',
                10 => 'ten',
                11 => 'eleven',
                12 => 'twelve',
                13 => 'thirteen',
                14 => 'fourteen',
                15 => 'fifteen',
                16 => 'sixteen',
                17 => 'seventeen',
                18 => 'eighteen',
                19 => 'nineteen',
                20 => 'twenty',
                30 => 'thirty',
                40 => 'forty',
                50 => 'fifty',
                60 => 'sixty',
                70 => 'seventy',
                80 => 'eighty',
                90 => 'ninety',
                100 => 'hundred',
                1000 => 'thousand',
                1000000 => 'million',
                1000000000 => 'billion'
            );

            // Convert whole number
            if ($whole > 0) {
                $whole_words = convertWholeNumberToWords($whole, $dictionary);
                $words[] = $whole_words . ' shilling' . ($whole != 1 ? 's' : '');
            }

            // Convert fraction (cents)
            if ($fraction > 0) {
                $fraction_words = convertWholeNumberToWords($fraction, $dictionary);
                $words[] = $fraction_words . ' cent' . ($fraction != 1 ? 's' : '');
            }

            return ucfirst(implode($conjunction, $words)) . ' only';
        }

        function convertWholeNumberToWords($number, $dictionary)
        {
            $string = $fraction = null;
            $hyphen = '-';
            $conjunction = ' and ';
            $separator = ', ';

            switch (true) {
                case $number < 21:
                    $string = $dictionary[$number];
                    break;
                case $number < 100:
                    $tens = ((int) ($number / 10)) * 10;
                    $units = $number % 10;
                    $string = $dictionary[$tens];
                    if ($units) {
                        $string .= $hyphen . $dictionary[$units];
                    }
                    break;
                case $number < 1000:
                    $hundreds = $number / 100;
                    $remainder = $number % 100;
                    $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                    if ($remainder) {
                        $string .= $conjunction . convertWholeNumberToWords($remainder, $dictionary);
                    }
                    break;
                default:
                    $baseUnit = pow(1000, floor(log($number, 1000)));
                    $numBaseUnits = (int) ($number / $baseUnit);
                    $remainder = $number % $baseUnit;
                    $string = convertWholeNumberToWords($numBaseUnits, $dictionary) . ' ' . $dictionary[$baseUnit];
                    if ($remainder) {
                        $string .= $remainder < 100 ? $conjunction : $separator;
                        $string .= convertWholeNumberToWords($remainder, $dictionary);
                    }
                    break;
            }

            return $string;
        }

        // Prepare PDF HTML
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Receipt - ' . htmlspecialchars($student_profile->admission_no) . '</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                .header { margin-bottom: 20px; text-align: center; }
                .logo { max-height: 80px; margin-bottom: 10px; }
                .receipt-title { 
                    font-size: 18px; 
                    font-weight: bold; 
                    margin: 15px 0; 
                    color: #000;
                    text-decoration: underline;
                }
                .receipt-info { 
                    width: 100%; 
                    margin-bottom: 20px;
                    border-collapse: collapse;
                }
                .receipt-info th, .receipt-info td { 
                    padding: 5px;
                    text-align: left;
                    border: 1px solid #ddd;
                }
                .receipt-info th {
                    background-color: #f5f5f5;
                    width: 30%;
                    font-weight: bold;
                }
                .amount-details { 
                    width: 100%; 
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                .amount-details th, .amount-details td { 
                    padding: 8px;
                    text-align: left;
                    border: 1px solid #ddd;
                }
                .amount-details th {
                    background-color: #f5f5f5;
                    font-weight: bold;
                }
                .total-row { 
                    font-weight: bold; 
                    background-color: #f9f9f9;
                }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .border-top { border-top: 1px solid #000; }
                .border-bottom { border-bottom: 1px solid #000; }
                .signature { 
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px dashed #000;
                }
                .footer { 
                    margin-top: 30px; 
                    text-align: center; 
                    font-size: 10px; 
                    color: #666;
                }
                .bold { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="header">
                ' . (!empty($university_info->logo_path) ? '<img src="' . htmlspecialchars($university_info->logo_path) . '" class="logo">' : '') . '
                <h3 style="color: #000; margin-bottom: 5px;"><strong>' . htmlspecialchars($university_info->name) . '</strong></h3>
                <p>' . htmlspecialchars($university_info->address) . '</p>
                <p>Tel: ' . htmlspecialchars($university_info->phone) . ' | Email: ' . htmlspecialchars($university_info->email) . '</p>
                
                <div class="receipt-title">OFFICIAL STUDENT RECEIPT</div>
            </div>

            <table class="receipt-info">
                <tr>
                    <th>RECEIPT NO.</th>
                    <td><strong>' . htmlspecialchars($selected_receipt->receipt_number) . '</strong></td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td><strong>' . date('d F, Y', strtotime($selected_receipt->payment_date)) . '</strong></td>
                </tr>
                <tr>
                    <th>Student No.</th>
                    <td><strong>' . htmlspecialchars($student_profile->admission_no) . '</strong></td>
                </tr>
                <tr>
                    <th>Payment Mode</th>
                    <td><strong>' . htmlspecialchars($selected_receipt->payment_method) . '</strong></td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td><strong>' . htmlspecialchars($student_profile->first_name . ' ' . $student_profile->last_name) . '</strong></td>
                </tr>
            </table>

            <table class="amount-details">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Amount (KES)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>' . htmlspecialchars($selected_receipt->description ?? 'Payment') . '</td>
                        <td class="text-right">' . number_format($selected_receipt->amount, 2) . '</td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>Total Paid</strong></td>
                        <td class="text-right"><strong>' . number_format($selected_receipt->amount, 2) . '</strong></td>
                    </tr>
                    <tr>
                        <td><strong>Current Balance</strong></td>
                        <td class="text-right"><strong>' . number_format($selected_receipt->balance_after ?? 0, 2) . '</strong></td>
                    </tr>
                    <tr>
                        <td colspan="2"><strong>In Words:</strong> ' . convertNumberToWords($selected_receipt->amount) . '</td>
                    </tr>
                </tbody>
            </table>

            <div class="signature">
                <p>Received With thanks for and on behalf of</p>
                <p><strong>' . htmlspecialchars($university_info->name) . '</strong></p>
                <br><br>
                <p>You were served by: <strong>' . htmlspecialchars($selected_receipt->received_by ?? 'Cashier') . '</strong></p>
                <p><strong>Cashier Signature/Stamp</strong></p>
            </div>

            <div class="footer">
                <p>Generated on ' . date('d-M-y H:i:s') . '</p>
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

        $filename = 'Receipt-' . $student_profile->admission_no . '.pdf';

        // Output PDF for download
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }
}

$page_css = 'dashboard.css';
$page_title = 'Payment Receipts';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6"><br>
                    <h1>Your Payment Receipts</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Receipts</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">

                        <!-- /.card-header -->
                        <div class="card-body">
                            <?php if (empty($receipts)): ?>
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> No Receipts Found!</h5>
                                    You don't have any payment receipts yet. Receipts will appear here after payments are
                                    processed.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="receiptsTable" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Receipt No.</th>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th class="text-right">Amount (KES)</th>
                                                <th>Payment Method</th>
                                                <th>Reference</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($receipts as $receipt): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($receipt->receipt_number) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($receipt->payment_date)) ?></td>
                                                    <td><?= htmlspecialchars($receipt->description ?? 'Payment') ?></td>
                                                    <td class="text-right"><?= number_format($receipt->amount, 2) ?></td>
                                                    <td><?= htmlspecialchars($receipt->payment_method) ?></td>
                                                    <td><?= htmlspecialchars($receipt->reference_number) ?></td>
                                                    <td>
                                                        <a href="?generate_pdf=<?= $receipt->id ?>"
                                                            class="btn btn-sm btn-primary" title="Download Receipt">
                                                            <i class="fas fa-file-pdf"></i> Download
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-right">Total:</th>
                                                <th class="text-right">
                                                    <?= number_format(array_sum(array_column($receipts, 'amount')), 2) ?>
                                                </th>
                                                <th colspan="3"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- /.card-body -->
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">Showing <?= count($receipts) ?> receipt(s)</small>
                                </div>
                                <div class="col-md-6 text-right">
                                    <small class="text-muted">Last updated: <?= date('d/m/Y H:i') ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php include 'includes/footer.php'; ?>

<!-- DataTables & Plugins -->
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>

<script>
    $(document).ready(function () {
        $('#receiptsTable').DataTable({
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "order": [[1, "desc"]],
            "pageLength": 10,
            "language": {
                "lengthMenu": "Show _MENU_ receipts per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ receipts",
                "infoEmpty": "No receipts available",
                "emptyTable": "No receipts available"
            }
        });
    });
</script>