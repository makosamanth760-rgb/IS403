<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireUserType('registrar');

$conn = getDBConnection();

$selected_semester = trim($_GET['semester'] ?? '');
$selected_year = intval($_GET['year'] ?? 0);

// Load available semesters/years from existing offerings.
$filters_sql = "SELECT DISTINCT semester, year FROM unit_offerings ORDER BY year DESC, semester";
$filters_result = $conn->query($filters_sql);
$available_filters = $filters_result->fetch_all(MYSQLI_ASSOC);

if ($selected_semester === '' || $selected_year <= 0) {
    if (!empty($available_filters)) {
        $selected_semester = $available_filters[0]['semester'];
        $selected_year = intval($available_filters[0]['year']);
    } else {
        $selected_semester = 'Semester 1';
        $selected_year = date('Y');
    }
}

$enrollment_list = [];
$summary = [
    'total_students' => 0,
    'total_enrollments' => 0
];

$sql = "SELECT s.student_id, s.name AS student_name, s.email, u.unit_code, u.unit_name, uo.semester, uo.year, e.enrollment_date
        FROM enrollments e
        JOIN students s ON e.student_id = s.student_id
        JOIN unit_offerings uo ON e.offering_id = uo.offering_id
        JOIN units u ON uo.unit_code = u.unit_code
        WHERE uo.semester = ? AND uo.year = ?
        ORDER BY s.name, u.unit_code";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $selected_semester, $selected_year);
$stmt->execute();
$enrollment_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$summary_sql = "SELECT COUNT(DISTINCT e.student_id) AS total_students, COUNT(*) AS total_enrollments
                FROM enrollments e
                JOIN unit_offerings uo ON e.offering_id = uo.offering_id
                WHERE uo.semester = ? AND uo.year = ?";
$summary_stmt = $conn->prepare($summary_sql);
$summary_stmt->bind_param("si", $selected_semester, $selected_year);
$summary_stmt->execute();
$summary_row = $summary_stmt->get_result()->fetch_assoc();
if ($summary_row) {
    $summary = $summary_row;
}
$summary_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Enrollment List - WPU SMS</title>
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
                <a href="add_student.php">Add New Student</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Student Enrollment List Report</h1>

        <div class="card" style="margin-bottom: 20px;">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select name="semester" id="semester" required>
                        <?php foreach ($available_filters as $filter): ?>
                            <option value="<?php echo htmlspecialchars($filter['semester']); ?>"
                                    <?php echo $selected_semester === $filter['semester'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($filter['semester']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="year">Year</label>
                    <select name="year" id="year" required>
                        <?php foreach ($available_filters as $filter): ?>
                            <option value="<?php echo intval($filter['year']); ?>"
                                    <?php echo $selected_year === intval($filter['year']) ? 'selected' : ''; ?>>
                                <?php echo intval($filter['year']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Load Enrollment List</button>
            </form>
        </div>

        <div class="card">
            <h3>Enrollment Summary</h3>
            <p><strong>Semester/Year:</strong> <?php echo htmlspecialchars($selected_semester . ' ' . $selected_year); ?></p>
            <p><strong>Total Students Enrolled:</strong> <?php echo intval($summary['total_students']); ?></p>
            <p><strong>Total Enrollments:</strong> <?php echo intval($summary['total_enrollments']); ?></p>

            <?php if (!empty($enrollment_list)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Unit Code</th>
                            <th>Unit Name</th>
                            <th>Enrollment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollment_list as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['unit_code']); ?></td>
                                <td><?php echo htmlspecialchars($row['unit_name']); ?></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($row['enrollment_date']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No enrollment records found for the selected semester and year.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
