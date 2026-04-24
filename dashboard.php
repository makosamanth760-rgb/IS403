<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireUserType('student');

$student_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Flash message (e.g., after unenroll)
$flash_message = $_SESSION['message'] ?? '';
$flash_message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Get current semester and year (defaulting to Semester 1, 2024)
$current_semester = 'Semester 1';
$current_year = 2024;

// Get enrolled units for current semester
$sql = "SELECT uo.offering_id, u.unit_code, u.unit_name, uo.semester, uo.year, e.mark, e.grade
        FROM enrollments e
        JOIN unit_offerings uo ON e.offering_id = uo.offering_id
        JOIN units u ON uo.unit_code = u.unit_code
        WHERE e.student_id = ? AND uo.semester = ? AND uo.year = ?
        ORDER BY u.unit_code";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $student_id, $current_semester, $current_year);
$stmt->execute();
$enrolled_units = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get student info
$sql = "SELECT * FROM students WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - WPU SMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="../assets/image/WPU-Logo-Main.webp" alt="WPU Logo" class="navbar-logo">
            </div>
            <h2>WPU Student Management System</h2>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="profile.php">My Profile</a>
                <a href="transcript.php">My Transcript</a>
                <a href="enroll.php">Enroll in Units</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Student Dashboard</h1>
        </div>

        <?php if ($flash_message): ?>
            <div class="message <?php echo htmlspecialchars($flash_message_type ?: 'success'); ?>">
                <?php echo htmlspecialchars($flash_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>Current Semester Enrollments</h3>
                <div class="card-content">
                    <p style="margin-bottom: 20px;"><strong>Semester:</strong> <?php echo htmlspecialchars($current_semester . ' ' . $current_year); ?></p>
                
                <?php if (count($enrolled_units) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Unit Code</th>
                                <th>Unit Name</th>
                                <th>Mark</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrolled_units as $unit): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($unit['unit_code']); ?></td>
                                    <td><?php echo htmlspecialchars($unit['unit_name']); ?></td>
                                    <td><?php echo $unit['mark'] !== null ? htmlspecialchars($unit['mark']) : 'N/A'; ?></td>
                                    <td><?php echo $unit['grade'] ? htmlspecialchars($unit['grade']) : 'N/A'; ?></td>
                                    <td>
                                        <a href="unenroll.php?offering_id=<?php echo $unit['offering_id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to unenroll from this unit?');">Unenroll</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><strong>Total Enrolled:</strong> <?php echo count($enrolled_units); ?> / 4 units</p>
                <?php else: ?>
                    <p>You are not enrolled in any units for this semester.</p>
                    <a href="enroll.php" class="btn btn-primary">Enroll in Units</a>
                <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <h3>Quick Actions</h3>
                <div class="card-content">
                    <ul class="action-list">
                        <li><a href="enroll.php" class="btn btn-primary">Enroll in Units</a></li>
                        <li><a href="profile.php" class="btn btn-secondary">View My Profile</a></li>
                        <li><a href="transcript.php" class="btn btn-secondary">View My Transcript</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

