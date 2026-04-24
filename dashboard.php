<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireUserType('registrar');

$conn = getDBConnection();

// Get students eligible for HECAS scholarship (GPA >= 2.5)
$sql = "SELECT s.student_id, s.name, s.email, 
        AVG(e.mark) as avg_mark,
        (AVG(e.mark) / 25.0) as gpa
        FROM students s
        INNER JOIN enrollments e ON s.student_id = e.student_id
        WHERE e.mark IS NOT NULL
        GROUP BY s.student_id, s.name, s.email
        HAVING gpa >= 2.5
        ORDER BY gpa DESC, s.name";
$result = $conn->query($sql);
$eligible_students = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Dashboard - WPU SMS</title>
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
                <a href="add_student.php">Add New Student</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Registrar's Office Dashboard</h1>
        </div>
        
        <div class="dashboard-grid registrar-dashboard">
            <div class="card">
                <h3>Quick Actions</h3>
                <div class="card-content">
                    <ul class="action-list">
                        <li><a href="add_student.php" class="btn btn-primary">Add New Student</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <h3>HECAS Scholarship Eligible Students</h3>
                <div class="card-content">
                    <p style="margin-bottom: 20px; color: #666; font-size: 14px;">Students with GPA of 2.5 or higher:</p>
                
                <?php if (count($eligible_students) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>GPA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eligible_students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo $student['gpa'] ? number_format($student['gpa'], 2) : '0.00'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No students are currently eligible for HECAS scholarship.</p>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

