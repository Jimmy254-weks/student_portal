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

// Initialize Student and University classes
$student = new Student();
$university = new University();

// Get student profile
$student_profile = $student->getStudentByUserId($_SESSION['user_id'] ?? 0);

if (!$student_profile) {
    flash('profile_error', 'Please complete your profile first');
    redirect('profile_setup.php');
}

// Get fee statements
$fee_statements = $student->getFeeStatements($student_profile->id);
$university_info = $university->getUniversityDetails();

// Calculate totals
$total_debit = 0;
$total_credit = 0;
foreach ($fee_statements as $statement) {
    $total_debit += $statement->debit_amount;
    $total_credit += $statement->credit_amount;
}

// Check if PDF generation requested
if (isset($_GET['generate_pdf']) && $_GET['generate_pdf'] == '1') {
    // Prepare PDF HTML
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Fee Statement - ' . htmlspecialchars($student_profile->admission_no) . '</title>
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
                        <h3>FEE STATEMENT</h3>
                        <p><strong>Date Generated:</strong> ' . date('F j, Y') . '</p>
                        <p><strong>Student ID:</strong> ' . htmlspecialchars($student_profile->admission_no) . '</p>
                        <p><strong>Name:</strong> ' . htmlspecialchars($student_profile->first_name . ' ' . $student_profile->last_name) . '</p>
                    </td>
                </tr>
            </table>
        </div>

        <div>
            <h4>Fee Transactions</h4>
            <table cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Ref #</th>
                        <th>Description</th>
                        <th class="text-right">Debit (Ksh)</th>
                        <th class="text-right">Credit (Ksh)</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($fee_statements as $statement) {
        $html .= '
                    <tr>
                        <td>' . htmlspecialchars(date('d/m/Y', strtotime($statement->transaction_date))) . '</td>
                        <td>' . htmlspecialchars($statement->reference_number) . '</td>
                        <td>' . htmlspecialchars($statement->description) . '</td>
                        <td class="text-right">' . number_format($statement->debit_amount, 2) . '</td>
                        <td class="text-right">' . number_format($statement->credit_amount, 2) . '</td>
                    </tr>';
    }

    $balance = $total_debit - $total_credit;
    $balance_class = ($balance > 0) ? 'negative-balance' : 'positive-balance';

    $html .= '
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="text-right"><strong>Totals:</strong></td>
                        <td class="text-right"><strong>' . number_format($total_debit, 2) . '</strong></td>
                        <td class="text-right"><strong>' . number_format($total_credit, 2) . '</strong></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="3" class="text-right"><strong>Balance:</strong></td>
                        <td colspan="2" class="text-right ' . $balance_class . '"><strong>' . number_format($balance, 2) . '</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div style="text-align:center; font-size:10px; color:#666;">
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

    $filename = 'Fee_Statement_' . $student_profile->admission_no . '_' . date('Ymd_His') . '.pdf';

    // Output PDF for download
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}

// If not generating PDF, continue to output HTML page normally
$page_css = 'dashboard.css'; //load CSS file for this page
$page_title = 'Fee Statement';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Fee Statement</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item active">Fee Statement</li>
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
                        <div class="card-header">
                            <h3 class="card-title">Your Fee Transactions</h3>
                            <div class="card-tools">
                            </div>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="feeStatementTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Ref #</th>
                                            <th>Description</th>
                                            <th class="text-right">Debit Amount (Ksh)</th>
                                            <th class="text-right">Credit Amount (Ksh)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fee_statements as $statement): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($statement->transaction_date))) ?>
                                                </td>
                                                <td><?= htmlspecialchars($statement->reference_number) ?></td>
                                                <td><?= htmlspecialchars($statement->description) ?></td>
                                                <td class="text-right"><?= number_format($statement->debit_amount, 2) ?>
                                                </td>
                                                <td class="text-right"><?= number_format($statement->credit_amount, 2) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-right">Totals:</th>
                                            <th class="text-right"><?= number_format($total_debit, 2) ?></th>
                                            <th class="text-right"><?= number_format($total_credit, 2) ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="3" class="text-right">Balance:</th>
                                            <th colspan="2"
                                                class="text-right text-<?= ($total_debit - $total_credit > 0) ? 'danger' : 'success' ?>">
                                                <?= number_format($total_debit - $total_credit, 2) ?>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <!-- /.card-body -->
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="?generate_pdf=1" class="btn btn-default">
                                        <i class="fas fa-file-pdf"></i> Generate PDF
                                    </a>
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
<script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="plugins/jszip/jszip.min.js"></script>
<script src="plugins/pdfmake/pdfmake.min.js"></script>
<script src="plugins/pdfmake/vfs_fonts.js"></script>
<script src="plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.colVis.min.js"></script>

<script>
    $(document).ready(function () {
        $('#feeStatementTable').DataTable({
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "buttons": [
                'copy', 'csv', 'excel', 'pdf', 'print', 'colvis'
            ],
            "order": [[0, "desc"]],
            "dom": '<"top"<"float-left"l><"float-right"f>><"row"<"col-sm-12"tr>><"bottom"<"float-left"i><"float-right"p>><"clear">',
            "pageLength": 10,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search...",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)"
            }
        }).buttons().container().appendTo('#feeStatementTable_wrapper .col-md-6:eq(0)');
    });
</script>