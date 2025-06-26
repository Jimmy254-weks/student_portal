<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
flash('logout_success', 'You have been logged out successfully.');
redirect('login.php');
?>