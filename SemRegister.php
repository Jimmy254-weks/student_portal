<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Initialize classes
$user = new User();
$student = new Student();
$db = new Database();

$page_title = 'Semester Registration';
$page_css = 'dashboard.css';

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

// Check if student has any fee balance
$has_fee_balance = false;
$db->query('SELECT SUM(amount - paid_amount) as balance FROM fees 
            WHERE student_id = :student_id AND status != "paid"');
$db->bind(':student_id', $student_profile->id);
$fee_balance = $db->single();
if ($fee_balance && $fee_balance->balance > 0) {
    $has_fee_balance = true;
}

// Check if student has already registered for the current semester
$is_registered = false;
$db->query('SELECT * FROM semester_registrations 
            WHERE student_id = :student_id 
            AND semester = :semester 
            AND academic_year = :academic_year');
$db->bind(':student_id', $student_profile->id);
$db->bind(':semester', $current_semester);
$db->bind(':academic_year', $academic_year);
$registration_data = $db->single();

if ($registration_data) {
    $is_registered = true;
    $registration_deadline = $registration_data->deadline_date;
}

// Process semester registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['register_semester'])) {
        if ($has_fee_balance) {
            flash('error', 'You cannot register for a new semester with outstanding fee balance');
        } elseif (!$is_registered) {
            // Calculate deadline (30 days from now)
            $deadline_date = date('Y-m-d', strtotime('+30 days'));

            $db->query('INSERT INTO semester_registrations 
                       (student_id, semester, academic_year, registration_date, deadline_date, status) 
                       VALUES (:student_id, :semester, :academic_year, NOW(), :deadline_date, "pending")');
            $db->bind(':student_id', $student_profile->id);
            $db->bind(':semester', $current_semester);
            $db->bind(':academic_year', $academic_year);
            $db->bind(':deadline_date', $deadline_date);

            if ($db->execute()) {
                flash('success', 'Semester registration submitted successfully! You can now register units.');
                redirect('SemesterUnitsRegistration.php');
                exit();
            } else {
                flash('error', 'Failed to register for the semester. Please try again.');
            }
        }
    } elseif (isset($_POST['delete_registration'])) {
        if ($is_registered && strtotime($registration_data->deadline_date) > time()) {
            $db->query('DELETE FROM semester_registrations 
                       WHERE id = :id AND student_id = :student_id');
            $db->bind(':id', $registration_data->id);
            $db->bind(':student_id', $student_profile->id);

            if ($db->execute()) {
                flash('success', 'Semester registration deleted successfully');
                redirect('SemRegister.php');
                exit();
            } else {
                flash('error', 'Failed to delete semester registration');
            }
        } else {
            flash('error', 'Cannot delete registration after the deadline has passed');
        }
    }
}

include 'includes/header.php';
?>
<style>
    /* Main card styling */
    .semester-registration-card {
        border-radius: 3px;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
        border: none;
        margin-bottom: 20px;
    }

    .semester-registration-card .card-header {
        background-color: #730000;
        color: white;
        padding: 15px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }

    .semester-registration-card .card-header h3 {
        margin: 0;
        font-size: 18px;
    }

    /* Alert styling */
    .alert-custom {
        border-left: 5px solid;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .alert-custom.fee-warning {
        border-left-color: #ffc107;
        background-color: #fff3cd;
        color: #856404;
    }

    .alert-custom .d-flex {
        align-items: center;
    }

    .alert-custom i {
        font-size: 24px;
        margin-right: 15px;
    }

    .alert-success {
        border-left-color: #28a745;
        background-color: #d4edda;
        color: #155724;
    }

    .alert-info {
        border-left-color: #17a2b8;
        background-color: #d1ecf1;
        color: #0c5460;
    }

    .alert-warning {
        border-left-color: #ffc107;
        background-color: #fff3cd;
        color: #856404;
    }

    /* Registration info styling */
    .registration-info {
        background-color: #f8f9fa;
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #ddd;
    }

    .registration-info h4 {
        color: #730000;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }

    .registration-info p {
        margin-bottom: 8px;
    }

    .registration-info strong {
        color: #495057;
        min-width: 150px;
        display: inline-block;
    }

    /* Button styling */
    .btn {
        border-radius: 3px;
        padding: 8px 20px;
        font-size: 14px;
        font-weight: 500;
    }

    .btn-register {
        background-color: #28a745;
        border-color: #28a745;
    }

    .btn-register:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }

    .btn-delete {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .btn-delete:hover {
        background-color: #c82333;
        border-color: #bd2130;
    }

    .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #212529;
    }

    .btn-warning:hover {
        background-color: #e0a800;
        border-color: #d39e00;
    }

    .btn-primary {
        background-color: #730000;
        border-color: #5a0000;
    }

    .btn-primary:hover {
        background-color: #5a0000;
        border-color: #400000;
    }

    /* Form styling */
    .form-control-static {
        padding-top: 7px;
        margin-bottom: 0;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .registration-info strong {
            min-width: 120px;
        }

        .alert-custom .d-flex {
            flex-direction: column;
            align-items: flex-start;
        }

        .alert-custom i {
            margin-bottom: 10px;
        }
    }

    /* Utility classes */
    .mt-2 {
        margin-top: 10px;
    }

    .mt-3 {
        margin-top: 15px;
    }

    .mt-4 {
        margin-top: 20px;
    }

    .text-center {
        text-align: center;
    }

    /* Icon spacing */
    .fa,
    .fas {
        margin-right: 8px;
    }
</style>

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

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card semester-registration-card">
                <div class="card-header">
                    <h3 class="mb-0">Semester Registration</h3>
                </div>

                <div class="card-body">
                    <?php if ($has_fee_balance): ?>
                        <div class="alert alert-warning alert-custom fee-warning">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                                <div>
                                    <h5 class="alert-heading">Outstanding Fee Balance</h5>
                                    <p>You have an outstanding fee balance of KES
                                        <?= number_format($fee_balance->balance, 2) ?>.
                                    </p>
                                    <p>Please clear your fee balance before registering for the semester.</p>
                                    <a href="FeeStatement.php" class="btn btn-warning mt-2">
                                        <i class="fas fa-file-invoice-dollar mr-2"></i>View Fee Statement
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_registered): ?>
                        <div class="alert alert-success alert-custom">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle fa-2x mr-3"></i>
                                <div>
                                    <h5 class="alert-heading">Already Registered</h5>
                                    <p>You have already registered for the <?= htmlspecialchars($current_semester) ?> of
                                        <?= htmlspecialchars($academic_year) ?>.
                                    </p>
                                    <p>You can now proceed to register your units.</p>
                                    <a href="SemesterUnitsRegistration.php" class="btn btn-primary mt-2">
                                        <i class="fas fa-book mr-2"></i>Register Units
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="registration-info">
                            <h4>Registration Details</h4>
                            <p><strong>Semester:</strong> <?= htmlspecialchars($current_semester) ?></p>
                            <p><strong>Academic Year:</strong> <?= htmlspecialchars($academic_year) ?></p>
                            <p><strong>Registration Date:</strong>
                                <?= date('F j, Y', strtotime($registration_data->registration_date)) ?></p>
                            <p><strong>Changes Deadline:</strong> <?= date('F j, Y', strtotime($registration_deadline)) ?>
                            </p>

                            <?php if (strtotime($registration_deadline) > time()): ?>
                                <form method="post" class="mt-3">
                                    <button type="submit" name="delete_registration" class="btn btn-delete"
                                        onclick="return confirm('Are you sure you want to delete this semester registration?')">
                                        <i class="fas fa-trash-alt mr-2"></i>Delete New Semester
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php if (!$has_fee_balance): ?>
                            <div class="alert alert-info alert-custom">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle fa-2x mr-3"></i>
                                    <div>
                                        <h5 class="alert-heading">Semester Registration Required</h5>
                                        <p>You need to register for the current semester before you can access academic
                                            services.</p>
                                        <p>After registration, you'll be able to register for units.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="registration-info">
                                <h4>Your Details</h4>
                                <p><strong>Current Semester:</strong> <?= htmlspecialchars($current_semester) ?></p>
                                <p><strong>Academic Year:</strong> <?= htmlspecialchars($academic_year) ?></p>
                                <p><strong>Student Name:</strong>
                                    <?= htmlspecialchars($student_profile->first_name . ' ' . $student_profile->last_name) ?>
                                </p>
                                <p><strong>Admission Number:</strong> <?= htmlspecialchars($student_profile->admission_no) ?>
                                </p>
                            </div>

                            <form method="post">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    By submitting this form, you confirm that all your personal and academic details are
                                    correct.
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" name="register_semester" class="btn btn-register">
                                        <i class="fas fa-check-circle mr-2"></i>Register for Semester
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>