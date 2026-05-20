<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

requireUserType('student');

$student_id = $_SESSION['user_id'];
$conn = getDBConnection();

$sql = "SELECT u.unit_code, u.unit_name, uo.semester, uo.year, e.mark, e.grade, e.enrollment_date
        FROM enrollments e
        JOIN unit_offerings uo ON e.offering_id = uo.offering_id
        JOIN units u ON uo.unit_code = u.unit_code
        WHERE e.student_id = ?
        ORDER BY uo.year DESC, uo.semester, u.unit_code";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $student_id);
$stmt->execute();
$transcript = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$gpa = calculateGPA($student_id);

$student_sql = 'SELECT student_id, name, email FROM students WHERE student_id = ?';
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param('s', $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();
$student_stmt->close();

$conn->close();

$total_units = count($transcript);
$graded_units = 0;
$periods = [];

foreach ($transcript as $record) {
    if ($record['mark'] !== null) {
        $graded_units++;
    }
    $period_key = intval($record['year']) . '|' . $record['semester'];
    if (!isset($periods[$period_key])) {
        $periods[$period_key] = [
            'year' => intval($record['year']),
            'semester' => $record['semester'],
            'records' => [],
        ];
    }
    $periods[$period_key]['records'][] = $record;
}

$has_grades = $graded_units > 0;
$hecas_eligible = $has_grades && $gpa >= HECAS_MIN_GPA;

function transcriptGradeClass(?string $grade): string {
    if ($grade === null || $grade === '') {
        return 'grade-pending';
    }
    $g = strtoupper(trim($grade));
    if (in_array($g, ['A+', 'A', 'A-'], true)) {
        return 'grade-high';
    }
    if (in_array($g, ['B+', 'B', 'B-'], true)) {
        return 'grade-mid';
    }
    if (in_array($g, ['C+', 'C', 'C-'], true)) {
        return 'grade-pass';
    }
    return 'grade-low';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Transcript - WPU SMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-page transcript-page">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="assets/image/WPU-Logo-Main.png" alt="WPU Logo" class="navbar-logo">
            </div>
            <h2>WPU Student Management System</h2>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="dashboard.php">Dashboard</a>
                <a href="profile.php">My Profile</a>
                <a href="enroll.php">Enroll in Units</a>
                <a href="enrollment_form.php">Enrollment Form</a>
                <a href="transcript.php">My Transcript</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header transcript-header">
            <h1>Academic Transcript</h1>
            <p class="transcript-subtitle">Your complete academic record at Western Pacific University, including overall GPA on a 4.0 scale.</p>
        </div>

        <div class="transcript-summary card">
            <div class="transcript-summary-inner">
                <div class="transcript-identity">
                    <span class="transcript-uni-label">Western Pacific University</span>
                    <h2 class="transcript-student-name"><?php echo htmlspecialchars($student['name'] ?? ''); ?></h2>
                    <p class="transcript-student-id">
                        <span class="unit-code-tag"><?php echo htmlspecialchars($student['student_id'] ?? ''); ?></span>
                    </p>
                    <?php if (!empty($student['email'])): ?>
                        <p class="transcript-student-email"><?php echo htmlspecialchars($student['email']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="transcript-gpa-panel" aria-label="Overall GPA">
                    <span class="transcript-gpa-label">Overall GPA</span>
                    <?php if ($has_grades): ?>
                        <span class="transcript-gpa-value"><?php echo number_format($gpa, 2); ?></span>
                        <span class="transcript-gpa-scale">out of 4.00</span>
                    <?php else: ?>
                        <span class="transcript-gpa-value transcript-gpa-na">—</span>
                        <span class="transcript-gpa-scale">pending grades</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="transcript-stats">
                <div class="transcript-stat">
                    <span class="transcript-stat-value"><?php echo $total_units; ?></span>
                    <span class="transcript-stat-label">Units enrolled</span>
                </div>
                <div class="transcript-stat">
                    <span class="transcript-stat-value"><?php echo $graded_units; ?></span>
                    <span class="transcript-stat-label">Graded units</span>
                </div>
                <div class="transcript-stat">
                    <?php if ($has_grades): ?>
                        <span class="status-badge <?php echo $hecas_eligible ? 'status-eligible' : 'status-ineligible'; ?>">
                            <?php echo $hecas_eligible ? 'HECAS eligible' : 'Below HECAS threshold'; ?>
                        </span>
                        <span class="transcript-stat-label">GPA &ge; <?php echo number_format(HECAS_MIN_GPA, 1); ?> for assistance</span>
                    <?php else: ?>
                        <span class="status-badge status-pending">Awaiting grades</span>
                        <span class="transcript-stat-label">HECAS eligibility</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="transcript-layout">
            <aside class="card transcript-sidebar">
                <h3>Record summary</h3>
                <table class="info-table">
                    <tr>
                        <th>Student</th>
                        <td><?php echo htmlspecialchars($student['name'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th>Student ID</th>
                        <td><?php echo htmlspecialchars($student['student_id'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th>Total units</th>
                        <td><?php echo $total_units; ?></td>
                    </tr>
                    <tr>
                        <th>Graded</th>
                        <td><?php echo $graded_units; ?> of <?php echo $total_units; ?></td>
                    </tr>
                </table>
                <?php if ($has_grades): ?>
                    <p class="transcript-sidebar-note">
                        GPA is calculated from your graded enrollments (marks out of 100, converted to a 4.0 scale).
                    </p>
                <?php else: ?>
                    <p class="text-muted transcript-sidebar-note">
                        Grades will appear here once marks are recorded for your enrollments.
                    </p>
                <?php endif; ?>
            </aside>

            <section class="card transcript-main">
                <h3>Academic record</h3>

                <?php if ($total_units > 0): ?>
                    <?php foreach ($periods as $period): ?>
                        <div class="transcript-period">
                            <div class="transcript-period-head">
                                <span class="semester-badge"><?php echo htmlspecialchars($period['semester'] . ' ' . $period['year']); ?></span>
                                <span class="transcript-period-count"><?php echo count($period['records']); ?> unit<?php echo count($period['records']) === 1 ? '' : 's'; ?></span>
                            </div>
                            <div class="transcript-table-wrap">
                                <table class="data-table transcript-table">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Unit</th>
                                            <th>Mark</th>
                                            <th>Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($period['records'] as $record): ?>
                                            <?php
                                            $mark_display = $record['mark'] !== null ? number_format((float) $record['mark'], 0) : '—';
                                            $grade_display = $record['grade'] ? $record['grade'] : '—';
                                            $grade_class = transcriptGradeClass($record['grade'] ?? null);
                                            ?>
                                            <tr>
                                                <td><span class="unit-code-tag"><?php echo htmlspecialchars($record['unit_code']); ?></span></td>
                                                <td class="transcript-unit-name"><?php echo htmlspecialchars($record['unit_name']); ?></td>
                                                <td class="transcript-mark"><?php echo htmlspecialchars($mark_display); ?></td>
                                                <td>
                                                    <span class="grade-badge <?php echo htmlspecialchars($grade_class); ?>">
                                                        <?php echo htmlspecialchars($grade_display); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="enrollment-empty transcript-empty">
                        <p>No academic records found yet. Enroll in units to build your transcript.</p>
                        <a href="enroll.php" class="btn btn-primary">Enroll in units</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <div class="enroll-page-footer">
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
