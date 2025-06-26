<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Initialize User class
$user = new User();

$page_title = 'Login';
$page_css = 'auth.css';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Initialize variables
$username = $password = '';
$username_err = $password_err = '';

// Process form data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate username
    if (empty(trim($_POST['username']))) {
        $username_err = 'Please enter your username.';
    } else {
        $username = sanitize(trim($_POST['username']));
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter your password.';
    } else {
        $password = trim($_POST['password']);
    }

    // Check for errors before login
    if (empty($username_err) && empty($password_err)) {
        // Attempt login
        $loggedInUser = $user->login($username, $password);

        if ($loggedInUser) {
            // Set session variables
            $_SESSION['user_id'] = $loggedInUser->id;
            $_SESSION['username'] = $loggedInUser->username;
            $_SESSION['user_role'] = $loggedInUser->role;

            if (isset($_SESSION['password_changed'])) {
                flash('password_success', 'Password changed successfully! Please login with your new password.', 'alert alert-success');
                unset($_SESSION['password_changed']);
            }

            // Redirect to dashboard
            flash('login_success', 'You are now logged in!');
            redirect('dashboard.php');
        } else {
            flash('login_error', 'Invalid username or password.', 'alert alert-danger');
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <!-- Header Section (outside form, same container) -->
    <div class="row justify-content-center">
        <div class="col-md-6 text-center mb-4">
            <h1 class="display-6">STUDENT PORTAL</h1>
            <img src="<?php echo SITE_URL; ?>/assets/images/student_icon.jpg" alt="Student Icon" class="img-fluid"
                style="max-height: 150px;">
            <h2 class="mt-2">Login to Your Account</h2> <!-- Added login title -->
        </div>
    </div>

    <!-- Form Section -->
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <?php flash('login_error'); ?>
                    <?php flash('register_success'); ?>

                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <!-- Form fields remain the same -->
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username"
                                class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>"
                                value="<?php echo htmlspecialchars($username); ?>">
                            <span class="invalid-feedback"><?php echo htmlspecialchars($username_err); ?></span>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password"
                                class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo htmlspecialchars($password_err); ?></span>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" style="width: 120px;">Login</button>
                        </div>

                        <div class="text-center">
                            <p>Don't have an account? <a href="register.php">Register here</a></p>
                            <p><a href="forgot_password.php">Forgot Password?</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>