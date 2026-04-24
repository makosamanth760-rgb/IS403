<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireUserType('student');

$student_id = $_SESSION['user_id'];
$conn = getDBConnection();

$current_semester = 'Semester 1';
$current_year = 2024;

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $offering_id = intval($_POST['offering_id'] ?? 0);
    if ($offering_id <= 0) {
        $message = 'Please select a unit offering.';
        $message_type = 'error';
    } else {
        $check_sql = "SELECT enrollment_id FROM enrollments WHERE student_id = ? AND offering_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $student_id, $offering_id);
        $check_stmt->execute();
        $already_enrolled = $check_stmt->get_result()->num_rows > 0;
        $check_stmt->close();

        if ($already_enrolled) {
            $message = 'You are already enrolled in this unit offering.';
            $message_type = 'error';
        } else {
            $limit_sql = "SELECT COUNT(*) AS total
                          FROM enrollments e
                          JOIN unit_offerings uo ON e.offering_id = uo.offering_id
                          WHERE e.student_id = ? AND uo.semester = ? AND uo.year = ?";
            $limit_stmt = $conn->prepare($limit_sql);
            $limit_stmt->bind_param("ssi", $student_id, $current_semester, $current_year);
            $limit_stmt->execute();
            $total = intval($limit_stmt->get_result()->fetch_assoc()['total'] ?? 0);
            $limit_stmt->close();

            if ($total >= 4) {
                $message = 'Enrollment limit reached (maximum 4 units per semester).';
                $message_type = 'error';
            } else {
                $insert_sql = "INSERT INTO enrollments (student_id, offering_id) VALUES (?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("si", $student_id, $offering_id);
                if ($insert_stmt->execute()) {
                    $message = 'Enrollment form submitted successfully.';
                    $message_type = 'success';
                } else {
                    $message = 'Unable to submit enrollment form.';
                    $message_type = 'error';
                }
                $insert_stmt->close();
            }
        }
    }
}

$student_sql = "SELECT student_id, name, email FROM students WHERE student_id = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("s", $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();
$student_stmt->close();

$units_sql = "SELECT uo.offering_id, u.unit_code, u.unit_name, uo.semester, uo.year
              FROM unit_offerings uo
              JOIN units u ON uo.unit_code = u.unit_code
              WHERE uo.semester = ? AND uo.year = ?
              AND uo.offering_id NOT IN (
                SELECT offering_id FROM enrollments WHERE student_id = ?
              )
              ORDER BY u.unit_code";
$units_stmt = $conn->prepare($units_sql);
$units_stmt->bind_param("sis", $current_semester, $current_year, $student_id);
$units_stmt->execute();
$available_units = $units_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$units_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enrollment Form - WPU SMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="../assets/image/WPU-Logo-Main.webp" alt="WPU Logo" class="navbar-logo">
            </div>
            <h2>WPU Student Management System</h2>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="dashboard.php">Dashboard</a>
                <a href="transcript.php">My Transcript</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Student Enrollment Form</h1>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 20px;">
            <h3>Student Details</h3>
            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id'] ?? ''); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($student['name'] ?? ''); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?? ''); ?></p>
            <p><strong>Semester:</strong> <?php echo htmlspecialchars($current_semester . ' ' . $current_year); ?></p>
        </div>

        <div class="card">
            <h3>Enroll Into Unit</h3>
            <?php if (!empty($available_units)): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="offering_id">Select Unit Offering</label>
                        <select name="offering_id" id="offering_id" required>
                            <option value="">-- Select Unit --</option>
                            <?php foreach ($available_units as $unit): ?>
                                <option value="<?php echo intval($unit['offering_id']); ?>">
                                    <?php echo htmlspecialchars($unit['unit_code'] . ' - ' . $unit['unit_name'] . ' (' . $unit['semester'] . ' ' . $unit['year'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Enrollment Form</button>
                </form>
            <?php else: ?>
                <p>No available units for enrollment in the current semester.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
