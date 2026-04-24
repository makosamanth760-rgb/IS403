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

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $offering_id = intval($_POST['offering_id']);
    
    // Check if already enrolled
    $check_sql = "SELECT * FROM enrollments WHERE student_id = ? AND offering_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $student_id, $offering_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $message = 'You are already enrolled in this unit offering.';
        $message_type = 'error';
    } else {
        // Check enrollment limit (4 per semester)
        $count_sql = "SELECT COUNT(*) as count FROM enrollments e
                      JOIN unit_offerings uo ON e.offering_id = uo.offering_id
                      WHERE e.student_id = ? AND uo.semester = ? AND uo.year = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("ssi", $student_id, $current_semester, $current_year);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        
        if ($count_result['count'] >= 4) {
            $message = 'You have reached the maximum enrollment limit of 4 units per semester.';
            $message_type = 'error';
        } else {
            // Enroll
            $enroll_sql = "INSERT INTO enrollments (student_id, offering_id) VALUES (?, ?)";
            $enroll_stmt = $conn->prepare($enroll_sql);
            $enroll_stmt->bind_param("si", $student_id, $offering_id);
            
            if ($enroll_stmt->execute()) {
                $message = 'Successfully enrolled in the unit!';
                $message_type = 'success';
            } else {
                $message = 'Error enrolling in unit.';
                $message_type = 'error';
            }
            $enroll_stmt->close();
        }
        $count_stmt->close();
    }
    $check_stmt->close();
}

// Get available unit offerings for current semester
$sql = "SELECT uo.offering_id, u.unit_code, u.unit_name, u.description, uo.semester, uo.year
        FROM unit_offerings uo
        JOIN units u ON uo.unit_code = u.unit_code
        WHERE uo.semester = ? AND uo.year = ?
        AND uo.offering_id NOT IN (
            SELECT offering_id FROM enrollments WHERE student_id = ?
        )
        ORDER BY u.unit_code";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sis", $current_semester, $current_year, $student_id);
$stmt->execute();
$available_units = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get current enrollment count
$count_sql = "SELECT COUNT(*) as count FROM enrollments e
              JOIN unit_offerings uo ON e.offering_id = uo.offering_id
              WHERE e.student_id = ? AND uo.semester = ? AND uo.year = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("ssi", $student_id, $current_semester, $current_year);
$count_stmt->execute();
$enrollment_count = $count_stmt->get_result()->fetch_assoc()['count'];
$count_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll in Units - WPU SMS</title>
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
                <a href="profile.php">My Profile</a>
                <a href="transcript.php">My Transcript</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Enroll in Units</h1>
        
        <p><strong>Current Semester:</strong> <?php echo htmlspecialchars($current_semester . ' ' . $current_year); ?></p>
        <p><strong>Current Enrollments:</strong> <?php echo $enrollment_count; ?> / 4 units</p>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($enrollment_count >= 4): ?>
            <div class="message error">
                You have reached the maximum enrollment limit of 4 units per semester.
            </div>
        <?php endif; ?>
        
        <?php if (count($available_units) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Unit Code</th>
                        <th>Unit Name</th>
                        <th>Description</th>
                        <th>Semester</th>
                        <th>Year</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($available_units as $unit): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($unit['unit_code']); ?></td>
                            <td><?php echo htmlspecialchars($unit['unit_name']); ?></td>
                            <td><?php echo htmlspecialchars($unit['description']); ?></td>
                            <td><?php echo htmlspecialchars($unit['semester']); ?></td>
                            <td><?php echo htmlspecialchars($unit['year']); ?></td>
                            <td>
                                <?php if ($enrollment_count < 4): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="offering_id" value="<?php echo $unit['offering_id']; ?>">
                                        <button type="submit" name="enroll" class="btn btn-primary btn-sm">Enroll</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Limit Reached</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No available units to enroll in for this semester, or you are already enrolled in all available units.</p>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html>

