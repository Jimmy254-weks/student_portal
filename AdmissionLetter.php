<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

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

// Set page title and CSS
$page_title = 'Admission Letter';
$page_css = 'dashboard.css';

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-file-alt mr-2"></i>Admission Letter
                    </h3>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>You can view and download your admission letter below.
                    </div>

                    <div class="text-center mb-4">
                        <a href="generate_admission_letter.php" class="btn btn-success btn-lg">
                            <i class="fas fa-download mr-2"></i>Download Admission Letter
                        </a>
                    </div>

                    <!-- Admission Letter Preview -->
                    <div class="admission-letter-preview border p-4">
                        <div class="letter-header text-center mb-4">
                            <img src="<?= SITE_URL ?>/assets/images/university_logo.png" alt="University Logo"
                                class="mb-3" style="max-height: 80px;">
                            <h2 class="text-uppercase mb-0" style="color: #730000;">
                                <?= htmlspecialchars($university_info->name) ?>
                            </h2>
                            <p class="mb-0"><?= htmlspecialchars($university_info->address) ?>,
                                <?= htmlspecialchars($university_info->city) ?>
                            </p>
                            <p>Tel: <?= htmlspecialchars($university_info->phone) ?> | Email:
                                <?= htmlspecialchars($university_info->email) ?>
                            </p>
                            <hr style="border-top: 2px solid #730000;">
                        </div>

                        <div class="letter-body">
                            <div class="text-right mb-4">
                                <p class="mb-0">Date: <?= date('F j, Y') ?></p>
                                <p>Ref: ADM/<?= date('Y') ?>/<?= str_pad($student_profile->id, 5, '0', STR_PAD_LEFT) ?>
                                </p>
                            </div>

                            <div class="mb-4">
                                <p class="mb-1">
                                    <?= htmlspecialchars($student_profile->first_name . ' ' . $student_profile->last_name) ?>
                                </p>
                                <p class="mb-1"><?= htmlspecialchars($student_profile->address) ?></p>
                                <p class="mb-1"><?= htmlspecialchars($student_profile->county) ?></p>
                            </div>

                            <h4 class="text-center text-uppercase mb-4" style="color: #730000;">Letter of Admission</h4>

                            <p>Dear <?= htmlspecialchars($student_profile->first_name) ?>,</p>

                            <p>We are pleased to inform you that you have been offered admission to
                                <?= htmlspecialchars($university_info->name) ?> for the academic year 2025/2026. You
                                have been admitted to the following program:
                            </p>

                            <div class="admission-details p-3 bg-light mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Admission Number:</strong>
                                            <?= htmlspecialchars($student_profile->admission_no) ?></p>
                                        <p><strong>Program:</strong> Bachelor of Science in Computer Science</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Duration:</strong> 4 Years (8 Semesters)</p>
                                        <p><strong>Mode of Study:</strong> Full Time</p>
                                    </div>
                                </div>
                            </div>

                            <p>This admission is subject to the following conditions:</p>

                            <ol class="mb-4">
                                <li>Payment of all required fees by the stipulated deadlines</li>
                                <li>Submission of all original academic documents for verification</li>
                                <li>Compliance with the university rules and regulations</li>
                            </ol>

                            <p>Congratulations on your admission. We look forward to welcoming you to our university
                                community.</p>

                            <div class="signature-section mt-5">
                                <p class="mb-4">Yours sincerely,</p>

                                <div class="signature-line mb-1" style="width: 200px; border-bottom: 1px solid #000;">
                                </div>
                                <p class="mb-0"><strong>Prof. Jane Muthoni</strong></p>
                                <p class="mb-0">Registrar (Academic Affairs)</p>
                                <p><?= htmlspecialchars($university_info->name) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer text-center">
                    <p class="mb-0 text-muted">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        For any issues with your admission letter, please contact the admissions office.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>