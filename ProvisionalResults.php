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

$page_title = 'Provisional Results';
$page_css = 'provisional_results.css';

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

// Get available semesters for the student
$db->query('SELECT DISTINCT semester, academic_year 
            FROM student_units 
            WHERE student_id = :student_id
            ORDER BY academic_year DESC, semester DESC');
$db->bind(':student_id', $student_profile->id);
$available_semesters = $db->resultSet();

// Get selected semester (default to most recent)
$selected_semester = $_GET['semester'] ?? '';
$selected_academic_year = $_GET['academic_year'] ?? '';

if (empty($selected_semester) && !empty($available_semesters)) {
    $selected_semester = $available_semesters[0]->semester;
    $selected_academic_year = $available_semesters[0]->academic_year;
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
    try {
        // Generate HTML for PDF
        $html = generateProvisionalResultsHTML($student_profile, $db, $program, $university_info, $selected_semester, $selected_academic_year, true);

        // Configure DomPDF options
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        // Instantiate DomPDF
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        // Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Add metadata
        $dompdf->addInfo('Title', 'Provisional Results - ' . $student_profile->admission_no);
        $dompdf->addInfo('Author', $university_info->name);

        // Output the generated PDF to browser
        $dompdf->stream("provisional_results_" . $student_profile->admission_no . ".pdf", [
            "Attachment" => true
        ]);
        exit;
    } catch (Exception $e) {
        flash('error', 'Failed to generate PDF: ' . $e->getMessage());
        redirect('ProvisionalResults.php');
    }
}

/**
 * Generates the HTML for the provisional results
 */
function generateProvisionalResultsHTML($student_profile, $db, $program, $university_info, $semester, $academic_year, $for_pdf = false)
{
    global $available_semesters, $selected_semester, $selected_academic_year;

    // Get units for the selected semester
    $db->query('SELECT su.id as registration_id, u.code as unit_code, u.name as unit_name, 
                       sm.cat_mark, sm.exam_mark, sm.total_score, sm.grade, u.credits
                FROM student_units su
                JOIN units u ON su.unit_id = u.id
                LEFT JOIN student_marks sm ON sm.registration_id = su.id
                WHERE su.student_id = :student_id
                AND su.semester = :semester
                AND su.academic_year = :academic_year
                ORDER BY u.code');
    $db->bind(':student_id', $student_profile->id);
    $db->bind(':semester', $semester);
    $db->bind(':academic_year', $academic_year);
    $units = $db->resultSet();

    // Start HTML output
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>Provisional Results - <?= htmlspecialchars($student_profile->admission_no) ?></title>
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

            .results-title {
                font-weight: bold;
                font-size: 14px;
                margin: 10px 0;
                text-align: center;
                text-transform: uppercase;
            }

            .semester-selector {
                margin: 15px 0;
                text-align: center;
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

            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 15px;
            }

            table th {
                font-weight: bold;
                padding: 8px;
                text-align: left;
                border-bottom: 2px solid #000;
            }

            table td {
                padding: 8px;
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

            .select-semester {
                padding: 5px;
                border-radius: 3px;
                border: 1px solid #ccc;
            }
        </style>
    </head>

    <body>
        <div class="header">
            <div class="university-name"><?= htmlspecialchars($university_info->name) ?></div>
            <div class="results-title">Provisional Results</div>
        </div>

        <?php if (!$for_pdf): ?>
            <div class="semester-selector no-print">
                <form method="get" action="ProvisionalResults.php">
                    <label for="semester">Select Period:</label>
                    <select name="semester" id="semester" class="select-semester" onchange="this.form.submit()">
                        <?php foreach ($available_semesters as $sem): ?>
                            <?php
                            $semester_parts = explode(' ', $sem->semester);
                            $display_value = strtoupper($semester_parts[0]) . substr($sem->academic_year, -2);
                            $is_selected = ($sem->semester == $selected_semester && $sem->academic_year == $selected_academic_year);
                            ?>
                            <option value="<?= htmlspecialchars($sem->semester) ?>"
                                data-year="<?= htmlspecialchars($sem->academic_year) ?>" <?= $is_selected ? 'selected' : '' ?>>
                                <?= htmlspecialchars($display_value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="academic_year" id="academic_year"
                        value="<?= htmlspecialchars($selected_academic_year) ?>">
                </form>
            </div>
        <?php endif; ?>

        <div class="student-info">
            <table>
                <tr>
                    <td width="20%"><strong>Student No:</strong></td>
                    <td width="30%"><?= htmlspecialchars($student_profile->admission_no) ?></td>
                    <td width="20%"><strong>STAGE:</strong></td>
                    <td width="30%"><?= htmlspecialchars($student_profile->stage ?? '') ?></td>
                </tr>
                <tr>
                    <td><strong>Name:</strong></td>
                    <td colspan="3">
                        <?= htmlspecialchars($student_profile->first_name . ' ' . $student_profile->last_name) ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Programme:</strong></td>
                    <td colspan="3"><?= ($program ? htmlspecialchars($program->name) : 'Not assigned') ?></td>
                </tr>
                <tr>
                    <td><strong>Academic Year:</strong></td>
                    <td colspan="3"><?= htmlspecialchars($academic_year) ?></td>
                </tr>
                <tr>
                    <td><strong>Semester:</strong></td>
                    <td colspan="3"><?= htmlspecialchars($semester) ?></td>
                </tr>
            </table>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="15%">UNIT CODE</th>
                    <th width="40%">UNIT DESCRIPTION</th>
                    <th width="10%">CAT MARKS</th>
                    <th width="10%">FINAL EXAM</th>
                    <th width="10%">TOTAL SCORE</th>
                    <th width="10%">GRADE</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($units)): ?>
                    <?php foreach ($units as $unit): ?>
                        <tr>
                            <td><?= htmlspecialchars($unit->unit_code) ?></td>
                            <td><?= htmlspecialchars($unit->unit_name) ?></td>
                            <td><?= ($unit->cat_mark !== null ? htmlspecialchars($unit->cat_mark) : '0') ?></td>
                            <td><?= ($unit->exam_mark !== null ? htmlspecialchars($unit->exam_mark) : '0') ?></td>
                            <td><?= ($unit->total_score !== null ? htmlspecialchars($unit->total_score) : '0') ?></td>
                            <td><?= htmlspecialchars($unit->grade ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No results available for this semester</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="footer">
            <p>Generated on: <?= date('Y-m-d H:i:s') ?></p>
        </div>

        <?php if (!$for_pdf): ?>
            <div class="action-buttons no-print">
                <a href="ProvisionalResults.php?download=1&semester=<?= urlencode($selected_semester) ?>&academic_year=<?= urlencode($selected_academic_year) ?>"
                    class="btn btn-download">
                    <i class="fa fa-download"></i> Download PDF
                </a>
                <a href="dashboard.php" class="btn btn-back">
                    <i class="fa fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        <?php endif; ?>

        <script>
            document.getElementById('semester').addEventListener('change', function () {
                var selectedOption = this.options[this.selectedIndex];
                document.getElementById('academic_year').value = selectedOption.getAttribute('data-year');
            });
        </script>
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
                    <h3 class="box-title">Provisional Results</h3>
                    <div class="box-tools pull-right">
                        <a href="dashboard.php" class="btn btn-default btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="box-body provisional-results-container">
                    <?php echo generateProvisionalResultsHTML($student_profile, $db, $program, $university_info, $selected_semester, $selected_academic_year); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .provisional-results-container {
        background: white;
        padding: 20px;
        margin-bottom: 20px;
    }

    .box-primary {
        border-top-color: #000;
    }
</style>

<?php include 'includes/footer.php'; ?>