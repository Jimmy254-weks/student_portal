<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_portal');

// Site configuration
define('SITE_NAME', 'Student Portal');
define('SITE_URL', 'http://localhost/student_portal');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include necessary files
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/functions.php';


// Create database connection
$db = new Database();