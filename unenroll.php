<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireUserType('student');

$student_id = $_SESSION['user_id'];
$offering_id = intval($_GET['offering_id'] ?? 0);

if ($offering_id <= 0) {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();

// Verify the enrollment belongs to this student
$check_sql = "SELECT * FROM enrollments WHERE student_id = ? AND offering_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("si", $student_id, $offering_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $check_stmt->close();
    $conn->close();
    header('Location: dashboard.php');
    exit();
}

// Delete enrollment
$delete_sql = "DELETE FROM enrollments WHERE student_id = ? AND offering_id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("si", $student_id, $offering_id);

if ($delete_stmt->execute()) {
    $_SESSION['message'] = 'Successfully unenrolled from the unit.';
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = 'Error unenrolling from unit.';
    $_SESSION['message_type'] = 'error';
}

$check_stmt->close();
$delete_stmt->close();
$conn->close();

header('Location: dashboard.php');
exit();
?>

