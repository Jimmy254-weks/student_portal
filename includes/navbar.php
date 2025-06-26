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