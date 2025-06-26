<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Initialize User class
$user = new User();

$page_title = 'Change Password';
$page_css = 'auth.css';
$page_css = 'dashboard.css'; //load CSS file for this page

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Initialize variables
$current_password = $new_password = $confirm_password = '';
$current_password_err = $new_password_err = $confirm_password_err = '';

// Process form data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate current password
    if (empty(trim($_POST['current_password']))) {
        $current_password_err = 'Please enter your current password.';
    } else {
        $current_password = trim($_POST['current_password']);
        // Verify current password
        if (!$user->verifyPassword($_SESSION['user_id'], $current_password)) {
            $current_password_err = 'Current password is incorrect.';
        }
    }

    // Validate new password
    if (empty(trim($_POST['new_password']))) {
        $new_password_err = 'Please enter a new password.';
    } elseif (strlen(trim($_POST['new_password'])) < 6) {
        $new_password_err = 'Password must have at least 6 characters.';
    } else {
        $new_password = trim($_POST['new_password']);
    }

    // Validate confirm password
    if (empty(trim($_POST['confirm_password']))) {
        $confirm_password_err = 'Please confirm the new password.';
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if (empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = 'Passwords did not match.';
        }
    }

    // Check for errors before updating
    if (empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {
        // Update password
        if ($user->updatePassword($_SESSION['user_id'], $new_password)) {
            // Logout the user
            session_destroy();
            // Redirect to login with success message
            $_SESSION['password_changed'] = true;
            redirect('login.php');
        } else {
            flash('password_error', 'Something went wrong. Please try again.', 'alert alert-danger');
        }
    }
}

include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Change Password</h4>
                </div>
                <div class="card-body">
                    <?php flash('password_error'); ?>

                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" name="current_password"
                                class="form-control <?php echo (!empty($current_password_err)) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo $current_password_err; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" name="new_password"
                                class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password"
                                class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                            <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                        </div>

                        <div class="form-group row">
                            <div class="col-sm-6">
                                <button type="submit" class="btn btn-primary btn-block">Change Password</button>
                            </div>
                            <div class="col-sm-6">
                                <a href="dashboard.php" class="btn btn-secondary btn-block">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>