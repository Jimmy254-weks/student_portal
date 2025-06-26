<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Initialize classes
$user = new User();
$student = new Student();

$page_title = 'Complete Your Profile';
$page_css = 'auth.css';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if profile already exists
$existing_profile = $student->getStudentByUserId($_SESSION['user_id']);
if ($existing_profile) {
    redirect('dashboard.php');
}

// Initialize variables
$first_name = $last_name = $gender = $date_of_birth = $phone = $address = $county = '';
$errors = [];

// Process form data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate inputs
    $first_name = sanitize(trim($_POST['first_name']));
    $last_name = sanitize(trim($_POST['last_name']));
    $gender = sanitize(trim($_POST['gender']));
    $date_of_birth = sanitize(trim($_POST['date_of_birth']));
    $phone = sanitize(trim($_POST['phone']));
    $address = sanitize(trim($_POST['address']));
    $county = sanitize(trim($_POST['county']));

    if (empty($first_name)) {
        $errors['first_name'] = 'Please enter your first name';
    }

    if (empty($last_name)) {
        $errors['last_name'] = 'Please enter your last name';
    }

    if (empty($gender)) {
        $errors['gender'] = 'Please select your gender';
    }

    if (empty($date_of_birth)) {
        $errors['date_of_birth'] = 'Please enter your date of birth';
    }

    if (empty($phone)) {
        $errors['phone'] = 'Please enter your phone number';
    }

    if (empty($address)) {
        $errors['address'] = 'Please enter your address';
    }

    if (empty($county)) {
        $errors['county'] = 'Please enter your county';
    }

    // Generate admission number
    $admission_no = 'STU' . str_pad($_SESSION['user_id'], 5, '0', STR_PAD_LEFT);

    // If no errors, create profile
    if (empty($errors)) {
        $profile_data = [
            'user_id' => $_SESSION['user_id'],
            'admission_no' => $admission_no,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'gender' => $gender,
            'date_of_birth' => $date_of_birth,
            'phone' => $phone,
            'address' => $address,
            'county' => $county
        ];

        if ($student->createProfile($profile_data)) {
            flash('profile_success', 'Profile setup completed successfully!');
            redirect('dashboard.php');
        } else {
            flash('profile_error', 'Something went wrong. Please try again.', 'alert alert-danger');
        }
    }
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Complete Your Profile</h4>
                </div>
                <div class="card-body">
                    <?php flash('profile_error'); ?>
                    <p class="text-muted">Please provide the following details to complete your registration.</p>

                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name">First Name</label>
                                    <input type="text" name="first_name"
                                        class="form-control <?php echo (!empty($errors['first_name'])) ? 'is-invalid' : ''; ?>"
                                        value="<?php echo $first_name; ?>">
                                    <?php if (!empty($errors['first_name'])): ?>
                                        <span class="invalid-feedback"><?php echo $errors['first_name']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input type="text" name="last_name"
                                        class="form-control <?php echo (!empty($errors['last_name'])) ? 'is-invalid' : ''; ?>"
                                        value="<?php echo $last_name; ?>">
                                    <?php if (!empty($errors['last_name'])): ?>
                                        <span class="invalid-feedback"><?php echo $errors['last_name']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select name="gender"
                                        class="form-control <?php echo (!empty($errors['gender'])) ? 'is-invalid' : ''; ?>">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo ($gender == 'Male') ? 'selected' : ''; ?>>Male
                                        </option>
                                        <option value="Female" <?php echo ($gender == 'Female') ? 'selected' : ''; ?>>
                                            Female</option>
                                        <option value="Other" <?php echo ($gender == 'Other') ? 'selected' : ''; ?>>Other
                                        </option>
                                    </select>
                                    <?php if (!empty($errors['gender'])): ?>
                                        <span class="invalid-feedback"><?php echo $errors['gender']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_of_birth">Date of Birth</label>
                                    <input type="date" name="date_of_birth"
                                        class="form-control <?php echo (!empty($errors['date_of_birth'])) ? 'is-invalid' : ''; ?>"
                                        value="<?php echo $date_of_birth; ?>">
                                    <?php if (!empty($errors['date_of_birth'])): ?>
                                        <span class="invalid-feedback"><?php echo $errors['date_of_birth']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" name="phone"
                                class="form-control <?php echo (!empty($errors['phone'])) ? 'is-invalid' : ''; ?>"
                                value="<?php echo $phone; ?>">
                            <?php if (!empty($errors['phone'])): ?>
                                <span class="invalid-feedback"><?php echo $errors['phone']; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea name="address"
                                class="form-control <?php echo (!empty($errors['address'])) ? 'is-invalid' : ''; ?>"><?php echo $address; ?></textarea>
                            <?php if (!empty($errors['address'])): ?>
                                <span class="invalid-feedback"><?php echo $errors['address']; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="county">County</label>
                            <input type="text" name="county"
                                class="form-control <?php echo (!empty($errors['county'])) ? 'is-invalid' : ''; ?>"
                                value="<?php echo $county; ?>">
                            <?php if (!empty($errors['county'])): ?>
                                <span class="invalid-feedback"><?php echo $errors['county']; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">Complete Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>