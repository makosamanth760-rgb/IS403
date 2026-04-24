<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireUserType('student');

$student_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get student information
$sql = "SELECT * FROM students WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get current semester enrollments
$current_semester = 'Semester 1';
$current_year = 2024;

$sql = "SELECT u.unit_code, u.unit_name, uo.semester, uo.year, e.mark, e.grade
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

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - WPU SMS</title>
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
                <a href="enroll.php">Enroll in Units</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>My Profile</h1>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>Student Information</h3>
                <table class="info-table">
                    <tr>
                        <th>Student ID:</th>
                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                    </tr>
                    <tr>
                        <th>Name:</th>
                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                    </tr>
                    <tr>
                        <th>Gender:</th>
                        <td><?php echo htmlspecialchars($student['gender']); ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                    </tr>
                    <tr>
                        <th>Contact Number:</th>
                        <td><?php echo htmlspecialchars($student['contact_number'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Address:</th>
                        <td><?php echo htmlspecialchars($student['address'] ?? 'N/A'); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="card">
                <h3>Current Semester Enrollments</h3>
                <p><strong>Semester:</strong> <?php echo htmlspecialchars($current_semester . ' ' . $current_year); ?></p>
                
                <?php if (count($enrolled_units) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Unit Code</th>
                                <th>Unit Name</th>
                                <th>Mark</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrolled_units as $unit): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($unit['unit_code']); ?></td>
                                    <td><?php echo htmlspecialchars($unit['unit_name']); ?></td>
                                    <td><?php echo $unit['mark'] !== null ? htmlspecialchars($unit['mark']) : 'N/A'; ?></td>
                                    <td><?php echo $unit['grade'] ? htmlspecialchars($unit['grade']) : 'N/A'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><strong>Total Enrolled:</strong> <?php echo count($enrolled_units); ?> units</p>
                <?php else: ?>
                    <p>You are not enrolled in any units for this semester.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html>

