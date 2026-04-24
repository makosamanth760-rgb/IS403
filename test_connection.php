<?php
/**
 * MySQL Connection Test Script
 * This script helps diagnose database connection issues
 */

echo "<h2>MySQL Connection Test</h2>";

// Test 1: Try connecting without password
echo "<h3>Test 1: Connecting with empty password...</h3>";
$conn1 = @new mysqli('localhost', 'root', '');
if ($conn1->connect_error) {
    echo "<p style='color:red;'>❌ Failed: " . $conn1->connect_error . "</p>";
} else {
    echo "<p style='color:green;'>✅ Success! MySQL root user has no password.</p>";
    $conn1->close();
}

// Test 2: Try connecting with common passwords
echo "<h3>Test 2: Trying common passwords...</h3>";
$common_passwords = ['', 'root', 'password', '123456', 'admin'];
$connected = false;

foreach ($common_passwords as $pass) {
    $conn2 = @new mysqli('localhost', 'root', $pass);
    if (!$conn2->connect_error) {
        echo "<p style='color:green;'>✅ Success! Password is: '" . ($pass === '' ? '(empty)' : $pass) . "'</p>";
        echo "<p><strong>Update config/database.php with: define('DB_PASS', '" . addslashes($pass) . "');</strong></p>";
        $conn2->close();
        $connected = true;
        break;
    }
}

if (!$connected) {
    echo "<p style='color:red;'>❌ Could not connect with common passwords.</p>";
    echo "<h3>Solutions:</h3>";
    echo "<ol>";
    echo "<li><strong>Find your MySQL root password:</strong> Check XAMPP Control Panel or your MySQL configuration</li>";
    echo "<li><strong>Update config/database.php:</strong> Change <code>define('DB_PASS', '');</code> to <code>define('DB_PASS', 'your_password');</code></li>";
    echo "<li><strong>Reset MySQL root password:</strong> Use XAMPP's MySQL configuration or phpMyAdmin</li>";
    echo "</ol>";
}

// Test 3: Check if MySQL is running
echo "<h3>Test 3: Checking MySQL service...</h3>";
$conn3 = @new mysqli('localhost', 'root', '');
if ($conn3->connect_error) {
    if (strpos($conn3->connect_error, 'Access denied') !== false) {
        echo "<p style='color:orange;'>⚠️ MySQL is running but password is required.</p>";
    } else {
        echo "<p style='color:red;'>❌ MySQL might not be running. Please start MySQL from XAMPP Control Panel.</p>";
    }
} else {
    echo "<p style='color:green;'>✅ MySQL service is running.</p>";
    $conn3->close();
}

echo "<hr>";
echo "<p><a href='index.php'>Go to Login Page</a></p>";
?>

