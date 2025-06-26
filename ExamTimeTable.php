<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Initialize classes
$user = new User();
$student = new Student();
$db = new Database();

$page_title = 'Exam Timetable';
$page_css = 'dashboard.css';
$page_css = 'exam_timetable.css';

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

// Determine current semester and academic year
$current_month = date('n');
$current_semester = ($current_month >= 1 && $current_month <= 6) ? 'First Semester' : 'Second Semester';
$current_year = date('Y');
$academic_year = ($current_semester == 'First Semester') ? "$current_year/" . ($current_year + 1) : ($current_year - 1) . "/$current_year";

// Check if student has registered for the current semester
$is_registered = false;
$db->query('SELECT * FROM semester_registrations 
            WHERE student_id = :student_id 
            AND semester = :semester 
            AND academic_year = :academic_year');
$db->bind(':student_id', $student_profile->id);
$db->bind(':semester', $current_semester);
$db->bind(':academic_year', $academic_year);
$registration_data = $db->single();

if (!$registration_data) {
    flash('error', 'You must register for the semester before viewing exam timetable');
    redirect('SemRegister.php');
    exit();
}

// Get registered units for current semester
$registered_units = [];
$db->query('SELECT u.id as unit_id, u.code, u.name 
            FROM student_units su
            JOIN units u ON su.unit_id = u.id
            WHERE su.student_id = :student_id
            AND su.semester = :semester
            AND su.academic_year = :academic_year
            ORDER BY u.code');
$db->bind(':student_id', $student_profile->id);
$db->bind(':semester', $current_semester);
$db->bind(':academic_year', $academic_year);
$registered_units = $db->resultSet();

// Get exam schedule for registered units
$exam_schedule = [];
if (!empty($registered_units)) {
    $unit_ids = array_column($registered_units, 'unit_id');
    $placeholders = implode(',', array_fill(0, count($unit_ids), '?'));
    
    $db->query("SELECT es.*, u.code as unit_code, u.name as unit_name 
                FROM exam_schedule es
                JOIN units u ON es.unit_id = u.id
                WHERE es.unit_id IN ($placeholders)
                AND es.semester = ?
                AND es.academic_year = ?
                ORDER BY es.exam_date, es.start_time");
    
    // Bind parameters dynamically
    $params = array_merge($unit_ids, [$current_semester, $academic_year]);
    foreach ($params as $k => $v) {
        $db->bind(($k+1), $v);
    }
    
    $exam_schedule = $db->resultSet();
}

include 'includes/header.php';
?>

<!-- Navbar -->
<header class="main-header">
    <nav class="navbar navbar-expand-md navbar-dark" style="background-color: #730000;">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand"><b>Student Portal</b></a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarCollapse">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">Dashboard</a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="financialsDropdown"
                            data-toggle="dropdown">Financials</a>
                        <div class="dropdown-menu">
                            <a href="ProformaInvoice.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Proforma Invoice</a>
                            <div class="dropdown-divider"></div>
                            <a href="FeeStatement.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Fee Statement</a>
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-divider"></div>
                            <a href="Receipts.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Payment Receipts</a>
                        </div>
                    </li>

                    <li class="nav-item dropdown active">
                        <a class="nav-link dropdown-toggle" href="#" id="academicsDropdown"
                            data-toggle="dropdown">Academics</a>
                        <div class="dropdown-menu">
                            <a href="SemRegister.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Semester Registration</a>
                            <div class="dropdown-divider"></div>
                            <a href="SemesterUnitsRegistration.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Register Units</a>
                            <div class="dropdown-divider"></div>
                            <a href="ExamTimeTable.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Exam Timetable</a>
                            <div class="dropdown-divider"></div>
                            <a href="ResitExamRegistration.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Special/Supplementary Exams</a>
                            <div class="dropdown-divider"></div>
                            <a href="ProvisionalResults.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Provisional Results</a>
                            <div class="dropdown-divider"></div>
                            <a href="Transcript.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Transcript</a>
                            <div class="dropdown-divider"></div>
                            <a href="ExamCards.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Exam Card</a>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="downloadsDropdown"
                            data-toggle="dropdown">Downloads</a>
                        <div class="dropdown-menu">
                            <a href="AdmissionLetter.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Admission Letter</a>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="graduationDropdown"
                            data-toggle="dropdown">Graduation</a>
                        <div class="dropdown-menu">
                            <a href="Clearance.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Graduation Clearance</a>
                            <div class="dropdown-divider"></div>
                            <a href="GraduationTracker.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Graduation Tracker</a>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="issuesDropdown" data-toggle="dropdown">Student
                            Issues</a>
                        <div class="dropdown-menu">
                            <a href="IssueListing.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Issues List</a>
                        </div>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="activitiesDropdown"
                            data-toggle="dropdown">Periodic Activities</a>
                        <div class="dropdown-menu">
                            <a href="LecsEvaluation.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Lecturers Evaluation</a>
                            <div class="dropdown-divider"></div>
                            <a href="GraduationApplication.php" class="dropdown-item"><i
                                    class="fas fa-hand-point-right mr-2"></i>Graduation Application</a>
                        </div>
                    </li>
                </ul>

                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-toggle="dropdown">
                            <span><?= htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" style="min-width: 200px; padding: 0.5rem 0;">
                            <a href="change_password.php" class="dropdown-item py-2 px-3">
                                <i class="fas fa-lock text-primary mr-3" style="width: 20px;"></i>Change Password
                            </a>
                            <div class="dropdown-divider my-1"></div>
                            <a href="logout.php" class="dropdown-item py-2 px-3">
                                <i class="fas fa-sign-out-alt text-danger mr-3" style="width: 20px;"></i>Sign out
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Exam Timetable - <?= htmlspecialchars($current_semester) ?> <?= htmlspecialchars($academic_year) ?></h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($registered_units)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle"></i> You haven't registered any units for this semester yet.
                            </div>
                        <?php elseif (empty($exam_schedule)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> The exam schedule for this semester hasn't been published yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Unit Code</th>
                                            <th>Unit Name</th>
                                            <th>Venue</th>
                                            <th>Instructions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($exam_schedule as $exam): ?>
                                            <tr>
                                                <td><?= date('D, jS M Y', strtotime($exam->exam_date)) ?></td>
                                                <td><?= date('h:i A', strtotime($exam->start_time)) ?> - <?= date('h:i A', strtotime($exam->end_time)) ?></td>
                                                <td><?= htmlspecialchars($exam->unit_code) ?></td>
                                                <td><?= htmlspecialchars($exam->unit_name) ?></td>
                                                <td><?= htmlspecialchars($exam->venue) ?></td>
                                                <td><?= !empty($exam->instructions) ? htmlspecialchars($exam->instructions) : 'None' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3">
                                <button class="btn btn-success" onclick="window.print()">
                                    <i class="fas fa-print"></i> Print Timetable
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        .card, .card * {
            visibility: visible;
        }
        .card {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            border: none;
        }
        .card-header, .card-tools {
            display: none;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>