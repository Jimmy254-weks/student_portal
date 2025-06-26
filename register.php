<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Initialize User class
$user = new User();

$page_title = 'Register';
$page_css = 'auth.css';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Initialize variables
$username = $email = $password = $confirm_password = '';
$username_err = $email_err = $password_err = $confirm_password_err = '';

// Process form data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate username
    if (empty(trim($_POST['username']))) {
        $username_err = 'Please enter a username.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST['username']))) {
        $username_err = 'Username can only contain letters, numbers, and underscores.';
    } elseif (strlen(trim($_POST['username'])) < 4) {
        $username_err = 'Username must be at least 4 characters.';
    } else {
        $username = sanitize(trim($_POST['username']));
        if ($user->findUserByUsername($username)) {
            $username_err = 'This username is already taken.';
        }
    }

    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_err = 'Please enter an email address.';
    } elseif (!filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
        $email_err = 'Please enter a valid email address.';
    } else {
        $email = sanitize(trim($_POST['email']));
        if ($user->findUserByEmail($email)) {
            $email_err = 'This email is already registered.';
        }
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter a password.';
    } elseif (strlen(trim($_POST['password'])) < 6) {
        $password_err = 'Password must have at least 6 characters.';
    } else {
        $password = trim($_POST['password']);
    }

    // Validate confirm password
    if (empty(trim($_POST['confirm_password']))) {
        $confirm_password_err = 'Please confirm password.';
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = 'Passwords did not match.';
        }
    }

    // Check for errors before inserting
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Register user
        $user_data = [
            'username' => $username,
            'email' => $email,
            'password' => $hashed_password
        ];

        if ($user_id = $user->register($user_data)) {
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['user_role'] = 'student';

            // Redirect to student profile setup
            flash('register_success', 'Registration successful! Please complete your profile.');
            redirect('profile_setup.php');
        } else {
            flash('register_error', 'Something went wrong. Please try again.', 'alert alert-danger');
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center mb-4">
            <h1 class="display-6">STUDENT PORTAL</h1>
            <img src="<?php echo SITE_URL; ?>/assets/images/student_icon.jpg" alt="Student Icon" class="img-fluid"
                style="max-height: 150px;">
            <h2 class="mt-6">Register here</h2>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <?php flash('register_error'); ?>

                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username"
                                class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>"
                                value="<?php echo $username; ?>">
                            <span class="invalid-feedback"><?php echo $username_err; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email"
                                class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>"
                                value="<?php echo $email; ?>">
                            <span class="invalid-feedback"><?php echo $email_err; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password"
                                class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>"
                                value="<?php echo $password; ?>">
                            <span class="invalid-feedback"><?php echo $password_err; ?></span>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" name="confirm_password"
                                class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>"
                                value="<?php echo $confirm_password; ?>">
                            <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" style="width: 120px;">Register</button>
                        </div>

                        <div class="text-center">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>