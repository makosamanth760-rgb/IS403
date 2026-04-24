<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireUserType('student');

$student_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get all enrollments with marks
$sql = "SELECT u.unit_code, u.unit_name, uo.semester, uo.year, e.mark, e.grade, e.enrollment_date
        FROM enrollments e
        JOIN unit_offerings uo ON e.offering_id = uo.offering_id
        JOIN units u ON uo.unit_code = u.unit_code
        WHERE e.student_id = ?
        ORDER BY uo.year DESC, uo.semester, u.unit_code";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$transcript = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate overall GPA
$gpa = calculateGPA($student_id);

// Get student info
$sql = "SELECT name, student_id FROM students WHERE student_id = ?";
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
    <title>My Transcript - WPU SMS</title>
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
                <a href="enroll.php">Enroll in Units</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Academic Transcript</h1>
        
        <div class="card">
            <h2>Western Pacific University</h2>
            <p><strong>Student Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
            <p><strong>Overall GPA:</strong> <span class="gpa"><?php echo number_format($gpa, 2); ?></span></p>
        </div>
        
        <div class="card">
            <h3>Academic Record</h3>
            
            <?php if (count($transcript) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Unit Code</th>
                            <th>Unit Name</th>
                            <th>Semester</th>
                            <th>Year</th>
                            <th>Mark</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transcript as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['unit_code']); ?></td>
                                <td><?php echo htmlspecialchars($record['unit_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['semester']); ?></td>
                                <td><?php echo htmlspecialchars($record['year']); ?></td>
                                <td><?php echo $record['mark'] !== null ? htmlspecialchars($record['mark']) : 'N/A'; ?></td>
                                <td><?php echo $record['grade'] ? htmlspecialchars($record['grade']) : 'N/A'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No academic records found.</p>
            <?php endif; ?>
        </div>
        
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</body>
</html>

