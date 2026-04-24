<?php
// Database configuration
$host = "localhost:3307";
$username = "root";
$password = "";
$database = "WPU";

// Create database connection
function getDBConnection() {
    // Read config values directly (avoid global variable issues)
    $db_host = "localhost:3307";
    $db_username = "root";
    $db_password = ""; // Empty string - no password
    $db_database = "WPU";
    
    // Create connection without database (exact pattern from working example)
    $conn = new mysqli($db_host, $db_username, $db_password);
    
    // Check connection
    if ($conn->connect_error) {
        // Show helpful error message with link to password finder
        $error_msg = "<div style='font-family:Arial;padding:20px;max-width:800px;margin:50px auto;'>";
        $error_msg .= "<h2 style='color:#dc3545;'>Database Connection Failed</h2>";
        $error_msg .= "<p><strong>Error:</strong> " . $conn->connect_error . "</p>";
        $error_msg .= "<p><strong>Current settings:</strong></p>";
        $error_msg .= "<ul>";
        $error_msg .= "<li>Host: " . htmlspecialchars($db_host) . "</li>";
        $error_msg .= "<li>Username: " . htmlspecialchars($db_username) . "</li>";
        $error_msg .= "<li>Password: " . ($db_password === '' ? '(empty)' : '***') . "</li>";
        $error_msg .= "<li>Password length: " . strlen($db_password) . "</li>";
        $error_msg .= "<li>Password type: " . gettype($db_password) . "</li>";
        $error_msg .= "</ul>";
        $error_msg .= "<p><strong>Solutions:</strong></p>";
        $error_msg .= "<ol>";
        $error_msg .= "<li><a href='find_password.php' style='color:#667eea;font-weight:bold;'>Find your MySQL password</a> - This will test common passwords and update the config automatically</li>";
        $error_msg .= "<li>Update the password in <code>config/database.php</code> - Change line 5: <code>\$password = \"your_password\";</code></li>";
        $error_msg .= "<li>Reset MySQL password to empty - See instructions in find_password.php</li>";
        $error_msg .= "</ol>";
        $error_msg .= "<p><a href='find_password.php'><button style='padding:10px 20px;background:#28a745;color:white;border:none;border-radius:5px;cursor:pointer;font-size:16px;'>Find Password Now</button></a></p>";
        $error_msg .= "</div>";
        die($error_msg);
    }
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $db_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sql) === TRUE) {
        // Database created successfully or already exists
    } else {
        die("Error creating database: " . $conn->error);
    }
    
    // Select the database
    $conn->select_db($db_database);
    
    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper function to calculate GPA
function calculateGPA($student_id) {
    $conn = getDBConnection();
    
    $sql = "SELECT mark FROM enrollments e
            JOIN students s ON e.student_id = s.student_id
            WHERE s.student_id = ? AND e.mark IS NOT NULL";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $total_marks = 0;
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $total_marks += $row['mark'];
        $count++;
    }
    
    $stmt->close();
    $conn->close();
    
    if ($count == 0) {
        return 0.00;
    }
    
    // Convert to 4.0 scale (assuming marks are out of 100)
    $gpa = ($total_marks / $count) / 25.0; // 100/4 = 25
    return round($gpa, 2);
}

// Helper function to get grade from mark
function getGrade($mark) {
    if ($mark >= 90) return 'A+';
    if ($mark >= 85) return 'A';
    if ($mark >= 80) return 'A-';
    if ($mark >= 75) return 'B+';
    if ($mark >= 70) return 'B';
    if ($mark >= 65) return 'B-';
    if ($mark >= 60) return 'C+';
    if ($mark >= 55) return 'C';
    if ($mark >= 50) return 'C-';
    if ($mark >= 45) return 'D+';
    if ($mark >= 40) return 'D';
    return 'F';
}
?>

