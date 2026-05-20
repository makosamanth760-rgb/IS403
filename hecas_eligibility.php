<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

requireUserType('registrar');

$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'eligible', 'ineligible'], true)) {
    $filter = 'all';
}

$conn = getDBConnection();

$sql = "SELECT s.student_id, s.name, s.email,
               COUNT(e.mark) AS graded_count,
               AVG(e.mark) AS avg_mark
        FROM students s
        LEFT JOIN enrollments e ON e.student_id = s.student_id AND e.mark IS NOT NULL
        GROUP BY s.student_id, s.name, s.email
        ORDER BY s.name";
$result = $conn->query($sql);
$students = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();

$rows = [];
$counts = ['eligible' => 0, 'ineligible' => 0, 'no_grades' => 0];

foreach ($students as $student) {
    $graded_count = intval($student['graded_count']);
    if ($graded_count > 0) {
        $gpa = round(floatval($student['avg_mark']) / 25.0, 2);
    } else {
        $gpa = 0.00;
    }
    $eligible = $graded_count > 0 && $gpa >= HECAS_MIN_GPA;

    if ($graded_count === 0) {
        $counts['no_grades']++;
    } elseif ($eligible) {
        $counts['eligible']++;
    } else {
        $counts['ineligible']++;
    }

    if ($filter === 'eligible' && !$eligible) {
        continue;
    }
    if ($filter === 'ineligible' && $eligible) {
        continue;
    }

    $rows[] = [
        'student_id' => $student['student_id'],
        'name' => $student['name'],
        'email' => $student['email'],
        'gpa' => $gpa,
        'graded_count' => $graded_count,
        'eligible' => $eligible,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HECAS Eligibility - WPU SMS</title>
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
            <h1>HECAS Eligibility</h1>
        </div>

        <div class="card" style="margin-bottom: 20px;">
            <h3>Financial assistance criteria</h3>
            <p>Students qualify for <strong>HECAS</strong> (Higher Education Contribution Assistance Scheme) when their overall GPA is at least <strong><?php echo number_format(HECAS_MIN_GPA, 1); ?></strong> on a 4.0 scale, based on graded unit enrollments.</p>
            <p><strong>Eligible:</strong> <?php echo $counts['eligible']; ?>
                &nbsp;|&nbsp; <strong>Not eligible:</strong> <?php echo $counts['ineligible']; ?>
                <?php if ($counts['no_grades'] > 0): ?>
                    &nbsp;|&nbsp; <strong>No graded units:</strong> <?php echo $counts['no_grades']; ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="card" style="margin-bottom: 20px;">
            <h3>Filter</h3>
            <form method="GET" action="hecas_eligibility.php">
                <div class="form-group">
                    <label for="filter">Show</label>
                    <select name="filter" id="filter">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All students</option>
                        <option value="eligible" <?php echo $filter === 'eligible' ? 'selected' : ''; ?>>Eligible only (GPA &ge; <?php echo HECAS_MIN_GPA; ?>)</option>
                        <option value="ineligible" <?php echo $filter === 'ineligible' ? 'selected' : ''; ?>>Not eligible</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Apply filter</button>
            </form>
        </div>

        <div class="card">
            <h3>Student eligibility</h3>
            <?php if (!empty($rows)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Overall GPA</th>
                            <th>Graded units</th>
                            <th>HECAS status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <?php if ($row['graded_count'] > 0): ?>
                                        <span class="gpa"><?php echo number_format($row['gpa'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['graded_count']; ?></td>
                                <td>
                                    <?php if ($row['graded_count'] === 0): ?>
                                        <span class="status-badge status-pending">No grades</span>
                                    <?php elseif ($row['eligible']): ?>
                                        <span class="status-badge status-eligible">Eligible</span>
                                    <?php else: ?>
                                        <span class="status-badge status-ineligible">Not eligible</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No students match the selected filter.</p>
                <?php if (count($students) === 0): ?>
                    <p>Add students via <a href="add_student.php">Add New Student</a>.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
