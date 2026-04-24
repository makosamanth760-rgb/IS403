<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

// Redirect based on user type
if (isStudent()) {
    header('Location: student/dashboard.php');
    exit();
} elseif (isRegistrar()) {
    header('Location: registrar/dashboard.php');
    exit();
} elseif (isStudentServices()) {
    header('Location: student_services/dashboard.php');
    exit();
} else {
    header('Location: index.php');
    exit();
}
?>

