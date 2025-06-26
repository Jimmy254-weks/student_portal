<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Initialize classes
$user = new User();
$student = new Student();

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize inputs
    $data = [
        'user_id' => $_SESSION['user_id'],
        'first_name' => sanitize(trim($_POST['first_name'])),
        'last_name' => sanitize(trim($_POST['last_name'])),
        'gender' => sanitize(trim($_POST['gender'])),
        'date_of_birth' => sanitize(trim($_POST['date_of_birth'])),
        'phone' => sanitize(trim($_POST['phone'])),
        'address' => sanitize(trim($_POST['address'])),
        'county' => sanitize(trim($_POST['county']))
    ];

    // Validate required fields
    $errors = [];
    foreach ($data as $key => $value) {
        if (empty($value)) {
            $errors[$key] = "This field is required";
        }
    }

    // If no errors, update profile
    if (empty($errors)) {
        if ($student->updateProfile($data)) {
            flash('profile_success', 'Profile updated successfully!');
            redirect('dashboard.php');
        } else {
            flash('profile_error', 'Failed to update profile. Please try again.', 'alert alert-danger');
            redirect('dashboard.php');
        }
    } else {
        // Store errors in session and redirect back
        $_SESSION['profile_errors'] = $errors;
        $_SESSION['profile_old_data'] = $data;
        redirect('dashboard.php');
    }
} else {
    // If someone tries to access this page directly
    redirect('dashboard.php');
}
