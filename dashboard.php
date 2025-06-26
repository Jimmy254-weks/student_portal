<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Initialize classes - have User and Student classes defined properly
$user = new User();
$student = new Student();

$page_title = 'Dashboard';
$page_css = 'dashboard.css'; //load CSS file for this page

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user data
$current_user = $user->getUserById($_SESSION['user_id'] ?? 0);
$student_profile = $student->getStudentByUserId($_SESSION['user_id'] ?? 0);

// Verify we got valid results
if (!$current_user) {
    // Handle case where user doesn't exist
    flash('error', 'User not found');
    redirect('login.php');
}

if (!$student_profile) {
    // Handle case where student profile doesn't exist
    flash('profile_error', 'Please complete your profile first');
    redirect('profile_setup.php');
}
// Check if student profile exists
if (!$student_profile) {
    flash('profile_error', 'Please complete your profile first.', 'alert alert-danger');
    redirect('profile_setup.php');
}

// Get student financial data (with null checks)
$fees = $student->getStudentFees($student_profile->id) ?? [];
$fee_summary = $student->calculateTotalFees($student_profile->id) ?? (object) ['total_billed' => 0, 'total_paid' => 0];

// Get student courses
$courses = $student->getStudentCourses($student_profile->id) ?? [];

include 'includes/header.php'; // This should include the HTML head and opening body tags
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
                    <li class="nav-item active">
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

                    <li class="nav-item dropdown">
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
<section class="content" style="padding: 10px;">
    <div class="container-fluid">
        <?php flash('login_success'); ?>

        <!-- Info boxes -->
        <div class="row">
            <div class="col-md-4 col-sm-6 col-xs-12">
                <div class="info-box" style="background-color: #ffde1a; border-radius: 15px; min-height: 120px;">
                    <span class="info-box-icon" style="border-radius: 15px"><i class="ion ion-ios-paper"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Billed:</span>
                        <span class="info-box-number">Ksh.
                            <?= number_format($fee_summary->total_billed ?? 0, 2); ?></span>
                        <div class="progress">
                            <div class="progress-bar" style="width: 100%; background-color: #3498db;"></div>
                        </div>
                        <a href="FeeStatement.php" class="small-box-footer" style="color: black">View Details <i
                                class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6 col-xs-12">
                <div class="info-box" style="background-color: #ffde1a; border-radius: 15px; min-height: 120px;">
                    <span class="info-box-icon" style="border-radius: 15px"><i class="ion ion-ios-card"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Paid:</span>
                        <span class="info-box-number">Ksh.
                            <?= number_format($fee_summary->total_paid ?? 0, 2); ?></span>
                        <div class="progress">
                            <?php
                            $paid_percentage = ($fee_summary->total_billed > 0)
                                ? ($fee_summary->total_paid / $fee_summary->total_billed) * 100
                                : 0;
                            ?>
                            <div class="progress-bar"
                                style="width: <?= round($paid_percentage) ?>%; background-color: #2ecc71;"></div>
                        </div>
                        <a href="Receipts.php" class="small-box-footer" style="color: black">View Receipts <i
                                class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-sm-6 col-xs-12">
                <div class="info-box" style="background-color: #ffde1a; border-radius: 15px; min-height: 120px;">
                    <span class="info-box-icon" style="border-radius: 15px"><i class="ion ion-jet"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Balance:</span>
                        <span class="info-box-number">Ksh.
                            <?= number_format(($fee_summary->total_billed ?? 0) - ($fee_summary->total_paid ?? 0), 2); ?></span>
                        <div class="progress">
                            <?php
                            $balance_percentage = ($fee_summary->total_billed > 0)
                                ? (($fee_summary->total_billed - $fee_summary->total_paid) / $fee_summary->total_billed) * 100
                                : 0;
                            ?>
                            <div class="progress-bar"
                                style="width: <?= round($balance_percentage) ?>%; background-color: #e74c3c;"></div>
                        </div>
                        <a href="FeeStatement.php" class="small-box-footer" style="color: black">View Statement <i
                                class="fa fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main row -->
        <div class="row" style="margin-top: 20px;">
            <div class="col-lg-4 col-md-12">
                <div class="box box-default" style="border-radius: 15px; margin-bottom: 20px;">
                    <div class="box-header with-border"
                        style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="box-title" style="margin: 0;">User Profile</h3>
                        <div class="box-tools">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse"
                                style="margin-left: auto;">
                                <i class="fa fa-minus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="box-content">
                        <div class="box-profile"
                            style="border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); padding: 20px; background-color: #fff; border: 1px solid #ddd;">
                            <div class="profile-image-container">
                                <img class="profile-image" alt="User profile picture"
                                    src="<?= SITE_URL ?>/assets/images/student_123.png" />
                            </div>
                            <h3 class="profile-username text-center"
                                style="font-size: 1.5em; color: #333; margin-top: 15px;">
                                <?= htmlspecialchars(($student_profile->first_name ?? '') . ' ' . ($student_profile->last_name ?? '')); ?>
                            </h3>

                            <strong style="display: block; margin-top: 15px; font-size: 1.2em; color: #555;">
                                <!-- Two non-breaking spaces for additional spacing.... &nbsp;&nbsp -->
                                <!-- margin-r-5' class adds right margin of 5px-->
                                <i class="fa fa-book margin-r-5"></i>&nbsp;&nbsp;Programme
                            </strong>

                            <p class="text-muted" style="font-size: 1em; color: #777; margin-top: 10px;">
                                <?= !empty($courses) ? htmlspecialchars($courses[0]->code . ' - ' . $courses[0]->name) : 'Not enrolled in any course'; ?>
                            </p>
                            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
                        </div>
                    </div>
                </div>
            </div>

            <script>
                $(document).ready(function () {
                    // Initialize box collapse
                    $('[data-widget="collapse"]').click(function () {
                        // Toggle the box
                        var box = $(this).closest('.box');
                        box.toggleClass('collapsed-box');

                        // Toggle the content with slide effect
                        box.find('.box-content').slideToggle(300);
                    });
                });
            </script>

            <!-- Personal info column -->
            <div class="col-lg-8 col-md-12">
                <div class="box box-info"
                    style="border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border: 1px solid #ddd; background-color: #fff; height: 100%;">
                    <div class="box-header with-border"
                        style="padding: 15px 20px; border-bottom: 1px solid #eee; background-color: #f7f7f7; border-radius: 10px 10px 0 0;">
                        <h3 class="box-title" style="font-size: 1.5em; font-weight: 600; color: #333;">Personal
                            Information</h3>
                    </div>

                    <div class="box-body" style="padding: 20px;">
                        <div class="table-responsive">
                            <table class="table no-margin"
                                style="width: 100%; border-collapse: collapse; font-size: 1em; color: #555;">
                                <tbody>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="font-weight: 600; padding: 10px 15px; width: 30%;">Admission No:</td>
                                        <td style="padding: 10px 15px; width: 70%;">
                                            <?= htmlspecialchars($student_profile->admission_no ?? 'Not set'); ?>
                                        </td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="font-weight: 600; padding: 10px 15px;">Full Name:</td>
                                        <td style="padding: 10px 15px;">
                                            <?= htmlspecialchars(($student_profile->first_name ?? '') . ' ' . ($student_profile->last_name ?? '')); ?>
                                        </td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="font-weight: 600; padding: 10px 15px;">Gender:</td>
                                        <td style="padding: 10px 15px;">
                                            <?= htmlspecialchars($student_profile->gender ?? 'Not set'); ?>
                                        </td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="font-weight: 600; padding: 10px 15px;">Date of Birth:</td>
                                        <td style="padding: 10px 15px;">
                                            <?= !empty($student_profile->date_of_birth) ? date('d/m/Y', strtotime($student_profile->date_of_birth)) : 'Not set'; ?>
                                        </td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="font-weight: 600; padding: 10px 15px;">Phone Number:</td>
                                        <td style="padding: 10px 15px;">
                                            <?= htmlspecialchars($student_profile->phone ?? 'Not set'); ?>
                                        </td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="font-weight: 600; padding: 10px 15px;">Email Address:</td>
                                        <td style="padding: 10px 15px;">
                                            <?= htmlspecialchars($current_user->email ?? ''); ?>
                                        </td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="font-weight: 600; padding: 10px 15px;">Postal Address:</td>
                                        <td style="padding: 10px 15px;">
                                            <?= htmlspecialchars($student_profile->address ?? 'Not set'); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="box-footer" style="padding: 15px 20px;">
                        <button type="button" class="btn btn-success"
                            style="font-size: 1em; padding: 8px 15px; border-radius: 5px; background-color: #28a745; border-color: #28a745;"
                            data-toggle="modal" data-target="#profileModal">
                            <i class="fa fa-edit"></i> Update Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Profile Update Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="profileModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header"
                style="background-color: #730000; color: #fff; border-radius: 10px 10px 0 0; padding: 15px 20px;">
                <h4 class="modal-title" id="profileModalLabel" style="margin: 0; flex-grow: 1;">Update Profile</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                    style="color: #fff; opacity: 1; margin-left: 15px; order: 2;">
                    <span aria-hidden="true" style="font-size: 1.5rem;">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <form id="profileForm" method="post" action="update_profile.php">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="form-control"
                            value="<?= htmlspecialchars($student_profile->first_name ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="form-control"
                            value="<?= htmlspecialchars($student_profile->last_name ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender" class="form-control" required>
                            <option value="Male" <?= ($student_profile->gender ?? '') == 'Male' ? 'selected' : ''; ?>>Male
                            </option>
                            <option value="Female" <?= ($student_profile->gender ?? '') == 'Female' ? 'selected' : ''; ?>>
                                Female</option>
                            <option value="Other" <?= ($student_profile->gender ?? '') == 'Other' ? 'selected' : ''; ?>>
                                Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control"
                            value="<?= htmlspecialchars($student_profile->date_of_birth ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control"
                            value="<?= htmlspecialchars($student_profile->phone ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="3"
                            required><?= htmlspecialchars($student_profile->address ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>County</label>
                        <input type="text" name="county" class="form-control"
                            value="<?= htmlspecialchars($student_profile->county ?? ''); ?>" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="padding: 15px 20px; border-top: 1px solid #eee;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" form="profileForm" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>