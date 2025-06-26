<?php
require_once 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/University.php';

// Initialize classes
$user = new User();
$student = new Student();
$db = new Database();
$university = new University();

$page_title = 'Academic Transcript';
$page_css = 'transcript.css';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
    exit();
}

// Get user and student data
$current_user = $user->getUserById($_SESSION['user_id'] ?? 0);
$student_profile = $student->getStudentByUserId($_SESSION['user_id'] ?? 0);

if (!$current_user || !$student_profile) {
    flash('error', 'Please complete your profile first');
    redirect('profile_setup.php');
    exit();
}

// Get program details
$program = null;
if ($student_profile->program_id) {
    $db->query('SELECT * FROM programs WHERE id = :id');
    $db->bind(':id', $student_profile->program_id);
    $program = $db->single();
}

// Get university details
$university_info = $university->getUniversityDetails();

// Process download request
if (isset($_GET['download'])) {
    // Generate HTML for PDF
    $html = generateTranscriptHTML($student_profile, $db, $program, $university_info, true);

    // Configure DomPDF options
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'Helvetica');

    // Instantiate Dompdf
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);

    // Setup the paper size and orientation
    $dompdf->setPaper('A4', 'portrait');

    // Render the HTML as PDF
    $dompdf->render();

    // Add metadata
    $dompdf->addInfo('Title', 'Academic Transcript - ' . $student_profile->admission_no);
    $dompdf->addInfo('Author', $university_info->name);

    // Output the generated PDF to browser
    $dompdf->stream("transcript_" . $student_profile->admission_no . ".pdf", [
        "Attachment" => true
    ]);
    exit;
}

/**
 * Generates the HTML for the academic transcript
 */
function generateTranscriptHTML($student_profile, $db, $program, $university_info, $for_pdf = false)
{
    // Get all registered units with marks
    $db->query('SELECT su.id as registration_id, u.code as unit_code, u.name as unit_name, 
                       sm.cat_mark, sm.exam_mark, sm.total_score, sm.grade, u.credits,
                       su.semester, su.academic_year
                FROM student_units su
                JOIN units u ON su.unit_id = u.id
                LEFT JOIN student_marks sm ON sm.registration_id = su.id
                WHERE su.student_id = :student_id
                ORDER BY su.academic_year, su.semester, u.code');
    $db->bind(':student_id', $student_profile->id);
    $units = $db->resultSet();

    // Group by academic year and semester
    $grouped_units = [];
    foreach ($units as $unit) {
        $key = $unit->academic_year . '|' . $unit->semester;
        if (!isset($grouped_units[$key])) {
            $grouped_units[$key] = [
                'academic_year' => $unit->academic_year,
                'semester' => $unit->semester,
                'units' => []
            ];
        }
        $grouped_units[$key]['units'][] = $unit;
    }

    // Start HTML output
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>Transcript - <?= htmlspecialchars($student_profile->admission_no) ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                margin: 0;
                padding: 20px;
                color: #000;
                line-height: 1.4;
            }

            .header {
                margin-bottom: 20px;
                text-align: center;
            }

            .university-name {
                font-weight: bold;
                font-size: 16px;
                margin-bottom: 5px;
                text-transform: uppercase;
            }

            .office-name {
                font-size: 14px;
                margin-bottom: 10px;
            }

            .transcript-title {
                font-weight: bold;
                font-size: 14px;
                margin: 10px 0;
                text-transform: uppercase;
            }

            .student-info {
                margin: 20px 0;
                width: 100%;
            }

            .student-info table {
                width: 100%;
                border-collapse: collapse;
            }

            .student-info td {
                padding: 5px;
                vertical-align: top;
            }

            .semester-section {
                margin-bottom: 30px;
                page-break-inside: avoid;
            }

            .semester-title {
                font-weight: bold;
                margin-bottom: 10px;
                text-transform: uppercase;
                border-bottom: 1px solid #000;
                padding-bottom: 5px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
            }

            table th {
                font-weight: bold;
                padding: 5px;
                text-align: left;
                border-bottom: 1px solid #000;
                border-top: 1px solid #000;
            }

            table td {
                padding: 5px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }

            .footer {
                margin-top: 30px;
                font-size: 10px;
                text-align: center;
            }

            @page {
                margin: 15mm;
            }

            @media print {
                .no-print {
                    display: none !important;
                }

                body {
                    padding: 0;
                }
            }

            .action-buttons {
                text-align: center;
                margin: 30px 0;
            }

            .btn {
                padding: 8px 15px;
                border-radius: 3px;
                display: inline-block;
                text-decoration: none;
                margin: 0 5px;
                font-size: 12px;
            }

            .btn-download {
                background-color: blue;
                color: white;
            }

            .btn-back {
                background-color: #fff;
                color: #000;
                border: 1px solid #000;
            }

            .page-number {
                position: absolute;
                bottom: 10px;
                right: 10px;
                font-size: 10px;
            }
        </style>
    </head>

    <body>
        <div class="header">
            <div class="university-name"><?= htmlspecialchars($university_info->name) ?></div>
            <div class="office-name">Office of the Academic Registrar</div>
            <div class="transcript-title">Progressive Transcript</div>
        </div>

        <div class="student-info">
            <table>
                <tr>
                    <td width="30%"><strong>Full Name:</strong></td>
                    <td width="70%">
                        <?= htmlspecialchars($student_profile->first_name . ' ' . $student_profile->last_name) ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Admission Number:</strong></td>
                    <td><?= htmlspecialchars($student_profile->admission_no) ?></td>
                </tr>
                <tr>
                    <td><strong>Faculty:</strong></td>
                    <td><?= ($program ? htmlspecialchars($program->faculty_name ?? '') : '') ?></td>
                </tr>
                <tr>
                    <td><strong>Programme:</strong></td>
                    <td><?= ($program ? htmlspecialchars($program->name) : 'Not assigned') ?></td>
                </tr>
            </table>
        </div>

        <?php foreach ($grouped_units as $group): ?>
            <div class="semester-section">
                <div class="semester-title"><?= htmlspecialchars($group['semester']) ?></div>

                <table>
                    <thead>
                        <tr>
                            <th width="15%">Unit Code</th>
                            <th width="40%">Unit Description</th>
                            <th width="10%">CAT</th>
                            <th width="10%">Exam Mark</th>
                            <th width="10%">Total Score</th>
                            <th width="10%">Grade</th>
                            <th width="10%">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($group['units'] as $unit): ?>
                            <tr>
                                <td><?= htmlspecialchars($unit->unit_code) ?></td>
                                <td><?= htmlspecialchars($unit->unit_name) ?></td>
                                <td><?= ($unit->cat_mark !== null ? htmlspecialchars($unit->cat_mark) : '0') ?></td>
                                <td><?= ($unit->exam_mark !== null ? htmlspecialchars($unit->exam_mark) : '0') ?></td>
                                <td><?= ($unit->total_score !== null ? htmlspecialchars($unit->total_score) : '0') ?></td>
                                <td><?= htmlspecialchars($unit->grade ?? '-') ?></td>
                                <td><?= htmlspecialchars($unit->credits) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

        <div class="footer">
            <div class="page-number"><?= htmlspecialchars($student_profile->admission_no) ?> Page 1 of 1</div>
        </div>

        <?php if (!$for_pdf): ?>
            <div class="action-buttons no-print">
                <a href="Transcript.php?download=1" class="btn btn-download">
                    <i class="fa fa-download"></i> Download PDF
                </a>
                <a href="dashboard.php" class="btn btn-back">
                    <i class="fa fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </body>

    </html>
    <?php
    return ob_get_clean();
}

include 'includes/header.php';
?>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Academic Transcript</h3>
                    <div class="box-tools pull-right">
                        <a href="dashboard.php" class="btn btn-default btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="box-body transcript-container">
                    <?php echo generateTranscriptHTML($student_profile, $db, $program, $university_info); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .transcript-container {
        background: white;
        padding: 20px;
        margin-bottom: 20px;
    }

    .box-primary {
        border-top-color: #000;
    }
</style>

<?php include 'includes/footer.php'; ?>