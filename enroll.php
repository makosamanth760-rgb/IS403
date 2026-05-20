<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

requireUserType('student');

$student_id = $_SESSION['user_id'];
$conn = getDBConnection();

$current_semester = 'Semester 1';
$current_year = 2024;
$max_enrollments = 4;

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
        
        if (intval($count_result['count'] ?? 0) >= $max_enrollments) {
            $message = 'You have reached the maximum enrollment limit of ' . $max_enrollments . ' units per semester.';
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
$enrollment_count = intval($count_stmt->get_result()->fetch_assoc()['count'] ?? 0);
$count_stmt->close();

$slots_remaining = max(0, $max_enrollments - $enrollment_count);
$at_limit = $enrollment_count >= $max_enrollments;

$student_sql = 'SELECT student_id, name, email FROM students WHERE student_id = ?';
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param('s', $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();
$student_stmt->close();

$enrolled_sql = "SELECT u.unit_code, u.unit_name
                 FROM enrollments e
                 JOIN unit_offerings uo ON e.offering_id = uo.offering_id
                 JOIN units u ON uo.unit_code = u.unit_code
                 WHERE e.student_id = ? AND uo.semester = ? AND uo.year = ?
                 ORDER BY u.unit_code";
$enrolled_stmt = $conn->prepare($enrolled_sql);
$enrolled_stmt->bind_param('ssi', $student_id, $current_semester, $current_year);
$enrolled_stmt->execute();
$enrolled_units = $enrolled_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$enrolled_stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll in Units - WPU SMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-page enrollment-page enroll-catalog-page">
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
        <div class="dashboard-header enrollment-header">
            <h1>Enroll in Units</h1>
            <p class="enrollment-subtitle">Add unit offerings for this semester. You may enroll in up to <?php echo $max_enrollments; ?> units. Tap a card to enroll immediately.</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($at_limit): ?>
            <div class="message error">
                You have reached the maximum enrollment limit of <?php echo $max_enrollments; ?> units per semester.
            </div>
        <?php endif; ?>

        <div class="enrollment-progress card">
            <div class="enrollment-progress-top">
                <div>
                    <span class="semester-badge"><?php echo htmlspecialchars($current_semester . ' ' . $current_year); ?></span>
                    <h3 class="enrollment-progress-title">Semester enrollment</h3>
                </div>
                <span class="enrollment-count"><?php echo $enrollment_count; ?> / <?php echo $max_enrollments; ?> units</span>
            </div>
            <div class="progress-bar" role="progressbar"
                 aria-valuenow="<?php echo $enrollment_count; ?>"
                 aria-valuemin="0"
                 aria-valuemax="<?php echo $max_enrollments; ?>">
                <?php for ($i = 0; $i < $max_enrollments; $i++): ?>
                    <span class="progress-segment <?php echo $i < $enrollment_count ? 'filled' : ''; ?>"></span>
                <?php endfor; ?>
            </div>
            <?php if ($at_limit): ?>
                <p class="enrollment-limit-note">You have reached the maximum enrollment for this semester.</p>
            <?php elseif ($slots_remaining === 1): ?>
                <p class="enrollment-limit-note"><?php echo $slots_remaining; ?> slot remaining.</p>
            <?php else: ?>
                <p class="enrollment-limit-note"><?php echo $slots_remaining; ?> slots remaining.</p>
            <?php endif; ?>
        </div>

        <div class="enrollment-layout">
            <aside class="card enrollment-sidebar">
                <h3>Your details</h3>
                <table class="info-table">
                    <tr>
                        <th>Student ID</th>
                        <td><?php echo htmlspecialchars($student['student_id'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th>Name</th>
                        <td><?php echo htmlspecialchars($student['name'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($student['email'] ?? ''); ?></td>
                    </tr>
                </table>

                <?php if (!empty($enrolled_units)): ?>
                    <h4 class="enrolled-heading">Current enrollments</h4>
                    <ul class="enrolled-list">
                        <?php foreach ($enrolled_units as $unit): ?>
                            <li>
                                <span class="unit-code-tag"><?php echo htmlspecialchars($unit['unit_code']); ?></span>
                                <?php echo htmlspecialchars($unit['unit_name']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted enrollment-hint">No units enrolled yet this semester.</p>
                <?php endif; ?>
            </aside>

            <section class="card enrollment-main enroll-catalog-main">
                <h3>Available unit offerings</h3>
                <p class="enroll-catalog-intro">Choose a unit below to enroll straight away.</p>

                <?php if ($at_limit): ?>
                    <div class="enrollment-empty">
                        <p>You cannot add more units until you unenroll from an existing offering.</p>
                        <a href="profile.php" class="btn btn-secondary">View my profile</a>
                    </div>
                <?php elseif (count($available_units) > 0): ?>
                    <div class="enroll-unit-grid" role="list">
                        <?php foreach ($available_units as $unit): ?>
                            <article class="unit-enroll-card" role="listitem">
                                <div class="unit-enroll-card-head">
                                    <span class="unit-code-tag"><?php echo htmlspecialchars($unit['unit_code']); ?></span>
                                    <span class="unit-option-period"><?php echo htmlspecialchars($unit['semester'] . ' ' . $unit['year']); ?></span>
                                </div>
                                <h4 class="unit-enroll-title"><?php echo htmlspecialchars($unit['unit_name']); ?></h4>
                                <?php if (!empty($unit['description'])): ?>
                                    <p class="unit-enroll-desc"><?php echo htmlspecialchars($unit['description']); ?></p>
                                <?php endif; ?>
                                <div class="unit-enroll-actions">
                                    <form method="POST" action="">
                                        <input type="hidden" name="offering_id" value="<?php echo intval($unit['offering_id']); ?>">
                                        <button type="submit" name="enroll" value="1" class="btn btn-primary btn-enroll-inline">Enroll in this unit</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="enrollment-empty">
                        <p>There are no additional units available for enrollment this semester, or you are already enrolled in every offering.</p>
                        <a href="profile.php" class="btn btn-secondary">Back to profile</a>
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

