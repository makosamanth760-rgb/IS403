<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

requireUserType('registrar');

$conn = getDBConnection();

$enrollment_list = [];
$summary = [
    'total_students' => 0,
    'total_enrollments' => 0,
];
$period_options = [];
$unit_options = [];
$db_error = '';
$show_all_periods = true;
$selected_semester = '';
$selected_year = 0;
$period_label = 'All periods';
$selected_unit_code = trim($_GET['unit_code'] ?? '');
$filter_all_units = ($selected_unit_code === '' || $selected_unit_code === 'all');
$unit_label = $filter_all_units ? 'All unit codes' : $selected_unit_code;

$filters_sql = 'SELECT DISTINCT semester, year FROM unit_offerings ORDER BY year DESC, semester';
$filters_result = $conn->query($filters_sql);
if ($filters_result === false) {
    $db_error = 'Could not load filters: ' . $conn->error;
} else {
    $period_options = $filters_result->fetch_all(MYSQLI_ASSOC);
}

$units_sql = 'SELECT unit_code, unit_name FROM units ORDER BY unit_code';
$units_result = $conn->query($units_sql);
if ($units_result === false) {
    $db_error = $db_error ?: ('Could not load unit codes: ' . $conn->error);
} else {
    $unit_options = $units_result->fetch_all(MYSQLI_ASSOC);
}

// Validate unit code against catalog
if (!$filter_all_units) {
    $unit_found = false;
    foreach ($unit_options as $unit) {
        if ($unit['unit_code'] === $selected_unit_code) {
            $unit_found = true;
            $unit_label = $unit['unit_code'] . ' — ' . $unit['unit_name'];
            break;
        }
    }
    if (!$unit_found) {
        $filter_all_units = true;
        $selected_unit_code = '';
        $unit_label = 'All unit codes';
    }
}

$period_param = $_GET['period'] ?? '';
if ($period_param === 'all' || $period_param === '') {
    $show_all_periods = true;
    $period_label = 'All periods';
} else {
    $parts = explode('|', $period_param, 2);
    if (count($parts) === 2) {
        $selected_semester = trim($parts[0]);
        $selected_year = intval($parts[1]);
        $show_all_periods = false;
        $period_label = $selected_semester . ' ' . $selected_year;

        $period_valid = false;
        foreach ($period_options as $period) {
            if ($period['semester'] === $selected_semester && intval($period['year']) === $selected_year) {
                $period_valid = true;
                break;
            }
        }
        if (!$period_valid) {
            $show_all_periods = true;
            $period_label = 'All periods';
        }
    }
}

$base_from = 'FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    JOIN unit_offerings uo ON e.offering_id = uo.offering_id
    JOIN units u ON uo.unit_code = u.unit_code';

$where = [];
$types = '';
$params = [];

if (!$show_all_periods) {
    $where[] = 'uo.semester = ?';
    $where[] = 'uo.year = ?';
    $types .= 'si';
    $params[] = $selected_semester;
    $params[] = $selected_year;
}

if (!$filter_all_units) {
    $where[] = 'u.unit_code = ?';
    $types .= 's';
    $params[] = $selected_unit_code;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$list_sql = "SELECT s.student_id, s.name AS student_name, s.email, u.unit_code, u.unit_name,
                    uo.semester, uo.year, e.enrollment_date
             {$base_from}
             {$where_sql}
             ORDER BY uo.year DESC, uo.semester, u.unit_code, s.name";

$stmt = $conn->prepare($list_sql);
if (!$stmt) {
    $db_error = 'Could not load enrollments: ' . $conn->error;
} else {
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $enrollment_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$summary_sql = "SELECT COUNT(DISTINCT e.student_id) AS total_students, COUNT(*) AS total_enrollments
                {$base_from}
                {$where_sql}";
$summary_stmt = $conn->prepare($summary_sql);
if ($summary_stmt) {
    if ($types !== '') {
        $summary_stmt->bind_param($types, ...$params);
    }
    $summary_stmt->execute();
    $summary = $summary_stmt->get_result()->fetch_assoc() ?: $summary;
    $summary_stmt->close();
}

$total_in_db = intval($conn->query('SELECT COUNT(*) AS total FROM enrollments')->fetch_assoc()['total'] ?? 0);
$conn->close();

$selected_period_value = $show_all_periods ? 'all' : ($selected_semester . '|' . $selected_year);
$selected_unit_value = $filter_all_units ? 'all' : $selected_unit_code;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Enrollment List - WPU SMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-page">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="assets/image/WPU-Logo-Main.png" alt="WPU Logo" class="navbar-logo">
            </div>
            <h2>WPU Student Management System</h2>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="enrollment_list.php">Enrollment List</a>
                <a href="hecas_eligibility.php">HECAS Eligibility</a>
                <a href="add_student.php">Add New Student</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Student Enrollment List Report</h1>
        </div>

        <?php if ($db_error): ?>
            <div class="message error"><?php echo htmlspecialchars($db_error); ?></div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 20px;">
            <h3>Filters</h3>
            <form method="GET" action="enrollment_list.php">
                <div class="form-group">
                    <label for="period">Period</label>
                    <select name="period" id="period">
                        <option value="all" <?php echo $selected_period_value === 'all' ? 'selected' : ''; ?>>All periods</option>
                        <?php foreach ($period_options as $period): ?>
                            <?php
                            $value = $period['semester'] . '|' . intval($period['year']);
                            $label = $period['semester'] . ' ' . intval($period['year']);
                            ?>
                            <option value="<?php echo htmlspecialchars($value); ?>"
                                <?php echo $selected_period_value === $value ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="unit_code">Unit code</label>
                    <select name="unit_code" id="unit_code">
                        <option value="all" <?php echo $selected_unit_value === 'all' ? 'selected' : ''; ?>>All unit codes</option>
                        <?php foreach ($unit_options as $unit): ?>
                            <option value="<?php echo htmlspecialchars($unit['unit_code']); ?>"
                                <?php echo $selected_unit_value === $unit['unit_code'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($unit['unit_code'] . ' — ' . $unit['unit_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Apply filters</button>
            </form>
        </div>

        <div class="card">
            <h3>Enrollment Summary</h3>
            <p><strong>Period:</strong> <?php echo htmlspecialchars($period_label); ?></p>
            <p><strong>Unit:</strong> <?php echo htmlspecialchars($unit_label); ?></p>
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
                            <th>Semester</th>
                            <th>Year</th>
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
                                <td><?php echo htmlspecialchars($row['semester']); ?></td>
                                <td><?php echo intval($row['year']); ?></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($row['enrollment_date']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No enrollment records match the selected filters.</p>
                <?php if ($total_in_db === 0): ?>
                    <p>Students must submit the <strong>Enrollment Form</strong> after login, or run
                        <a href="seed_enrollments.php">seed_enrollments.php</a> to add sample data.</p>
                <?php else: ?>
                    <p>Try <strong>All periods</strong> and <strong>All unit codes</strong>, or adjust the filters above.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
