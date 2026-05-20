<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function wpuBaseUrl(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($script !== '') {
        $dir = dirname(str_replace('\\', '/', $script));
        if ($dir !== '/' && $dir !== '.') {
            return rtrim($dir, '/');
        }
    }
    // Fallback: app directory name under document root (e.g. /IS403 when config lives in .../IS403/config)
    return '/' . basename(dirname(__DIR__));
}

function wpuRedirect(string $path): void {
    $path = '/' . ltrim($path, '/');
    $base = wpuBaseUrl();
    header('Location: ' . $base . $path);
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

// Check if user is dean (residential housing)
function isDean() {
    return isLoggedIn() && $_SESSION['user_type'] === 'dean';
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        wpuRedirect('index.php');
    }
}

/** Home page for the logged-in role (used after login). */
function redirectAfterLogin(): void {
    if (!isLoggedIn()) {
        wpuRedirect('index.php');
    }
    switch ($_SESSION['user_type']) {
        case 'student':
            wpuRedirect('profile.php');
        case 'registrar':
            wpuRedirect('enrollment_list.php');
        case 'student_services':
            wpuRedirect('dashboard.php');
        case 'dean':
            wpuRedirect('dean_dormitory_requests.php');
        default:
            wpuRedirect('index.php');
    }
}

// Dean or Student Services (residential housing)
function isResidentialStaff() {
    return isStudentServices() || isDean();
}

function requireResidentialStaff() {
    requireLogin();
    if (!isResidentialStaff()) {
        redirectAfterLogin();
    }
}

// Require specific user type
function requireUserType($type) {
    requireLogin();
    if ($_SESSION['user_type'] !== $type) {
        redirectAfterLogin();
    }
}
?>
