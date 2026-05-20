<?php
/**
 * Setup Database Tables
 * This script will create all necessary tables and insert sample data
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><title>Database Setup</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;padding:15px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:15px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:10px 0;}";
echo "pre{background:#f4f4f4;padding:15px;border-radius:5px;overflow-x:auto;}";
echo "</style></head><body>";
echo "<h2>Database Setup - Creating Tables and Sample Data</h2>";

try {
    $conn = getDBConnection();
    
    // Read and execute the schema file (project root; legacy path database/schema.sql)
    $schema_candidates = [
        __DIR__ . '/schema.sql',
        __DIR__ . '/database/schema.sql',
    ];
    $schema_file = null;
    foreach ($schema_candidates as $path) {
        if (file_exists($path)) {
            $schema_file = $path;
            break;
        }
    }
    if ($schema_file === null) {
        die("<div class='error'>Schema file not found. Expected <code>schema.sql</code> in the project folder.</div>");
    }
    
    $sql = file_get_contents($schema_file);
    
    // Remove CREATE DATABASE and USE statements (database already selected)
    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
    $sql = preg_replace('/USE\s+\w+\s*;/i', '', $sql);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }
        
        if ($conn->query($statement)) {
            $success_count++;
        } else {
            // Ignore "already exists" errors
            if (strpos($conn->error, 'already exists') === false && 
                strpos($conn->error, 'Duplicate') === false) {
                echo "<div class='error'>Error: " . $conn->error . "<br>Statement: " . htmlspecialchars(substr($statement, 0, 100)) . "...</div>";
                $error_count++;
            } else {
                $success_count++;
            }
        }
    }
    
    // Ensure staff/student passwords are bcrypt hashes (fixes old plaintext seed data)
    $default_password_hash = password_hash('password', PASSWORD_DEFAULT);
    $hash_stmt = $conn->prepare("UPDATE staff SET password = ? WHERE staff_id IN ('REG001', 'SS001')");
    if ($hash_stmt) {
        $hash_stmt->bind_param('s', $default_password_hash);
        $hash_stmt->execute();
        $hash_stmt->close();
    }
    $hash_stmt = $conn->prepare("UPDATE students SET password = ? WHERE student_id = 'STU001'");
    if ($hash_stmt) {
        $hash_stmt->bind_param('s', $default_password_hash);
        $hash_stmt->execute();
        $hash_stmt->close();
    }

    // Seed enrollments when table is empty (so enrollment_list.php has data to show)
    $enrollment_count = 0;
    $count_result = $conn->query('SELECT COUNT(*) AS total FROM enrollments');
    if ($count_result) {
        $enrollment_count = intval($count_result->fetch_assoc()['total'] ?? 0);
    }
    if ($enrollment_count === 0) {
        $conn->query(
            "INSERT INTO enrollments (student_id, offering_id)
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
        echo "<div class='success'>✅ Sample enrollment records created for the enrollment list report.</div>";
    }

    echo "<div class='success'>✅ Database setup completed!</div>";
    echo "<p>Successfully executed: $success_count statements</p>";
    if ($error_count > 0) {
        echo "<p>Errors: $error_count (some may be expected if tables already exist)</p>";
    }
    echo "<div class='success'>✅ Login passwords reset to <code>password</code> for sample accounts.</div>";
    
    echo "<hr>";
    echo "<h3>Sample Login Credentials:</h3>";
    echo "<p>Select the matching <strong>Login As</strong> role on the login page.</p>";
    echo "<ul>";
    echo "<li><strong>Registrar:</strong> registrar@wpu.edu / <code>password</code></li>";
    echo "<li><strong>Student Services:</strong> services@wpu.edu / <code>password</code></li>";
    echo "<li><strong>Student:</strong> student@wpu.edu / <code>password</code></li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<p><a href='index.php'><button style='padding:10px 20px;background:#667eea;color:white;border:none;border-radius:5px;cursor:pointer;font-size:16px;'>Go to Login Page</button></a></p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>

