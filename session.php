<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function wpuBaseUrl(): string {
    // Assume project folder name matches this directory's parent (e.g. /WPU)
    $project = basename(dirname(__DIR__));
    return '/' . $project;
}

function wpuRedirect(string $path): void {
    $path = '/' . ltrim($path, '/');
    header('Location: ' . wpuBaseUrl() . $path);
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

// Check if user is a student
function isStudent() {
    return isLoggedIn() && $_SESSION['user_type'] === 'student';
}

// Check if user is registrar
function isRegistrar() {
    return isLoggedIn() && $_SESSION['user_type'] === 'registrar';
}

// Check if user is student services
function isStudentServices() {
    return isLoggedIn() && $_SESSION['user_type'] === 'student_services';
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        wpuRedirect('index.php');
    }
}

// Require specific user type
function requireUserType($type) {
    requireLogin();
    if ($_SESSION['user_type'] !== $type) {
        wpuRedirect('dashboard.php');
    }
}
?>

