<?php
/**
 * One-time helper: create enrollment rows so enrollment_list.php can show data.
 */
require_once __DIR__ . '/config/database.php';

echo '<!DOCTYPE html><html><head><title>Seed Enrollments</title>';
echo '<style>body{font-family:Arial;max-width:700px;margin:40px auto;padding:20px;}';
echo '.success{color:green;background:#d4edda;padding:12px;border-radius:6px;margin:10px 0;}';
echo '.error{color:#721c24;background:#f8d7da;padding:12px;border-radius:6px;margin:10px 0;}';
echo 'a{color:#1A3A8A;}</style></head><body>';
echo '<h2>Seed enrollment records</h2>';

try {
    $conn = getDBConnection();
    $before = intval($conn->query('SELECT COUNT(*) AS total FROM enrollments')->fetch_assoc()['total'] ?? 0);

    $conn->query(
        "INSERT IGNORE INTO enrollments (student_id, offering_id)
         SELECT 'STU001', offering_id FROM unit_offerings
         WHERE semester = 'Semester 1' AND year = 2024 AND unit_code IN ('IS303', 'CS101', 'ENG101')"
    );
    $conn->query(
        "INSERT IGNORE INTO enrollments (student_id, offering_id)
         SELECT s.student_id,
                (SELECT offering_id FROM unit_offerings
                 WHERE semester = 'Semester 1' AND year = 2024 AND unit_code = 'IS303' LIMIT 1)
         FROM students s
         WHERE NOT EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id = s.student_id)"
    );

    $after = intval($conn->query('SELECT COUNT(*) AS total FROM enrollments')->fetch_assoc()['total'] ?? 0);
    $added = $after - $before;

    echo '<div class="success">Done. Added ' . $added . ' enrollment(s). Total in database: ' . $after . '.</div>';
    echo '<p><a href="enrollment_list.php">View enrollment list</a></p>';
    $conn->close();
} catch (Throwable $e) {
    echo '<div class="error">' . htmlspecialchars($e->getMessage()) . '</div>';
}

echo '</body></html>';
