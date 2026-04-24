<?php
/**
 * Test exact connection code from password finder
 */

echo "<!DOCTYPE html><html><head><title>Test Connection</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;padding:15px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:15px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:10px 0;}";
echo "</style></head><body>";
echo "<h2>Testing Exact Connection Code</h2>";

$host = "localhost:3307";
$username = "root";
$password = ""; // Empty string - same as password finder

echo "<h3>Test 1: Using exact code from password finder</h3>";
$conn1 = @new mysqli($host, $username, $password);

if (!$conn1->connect_error) {
    echo "<div class='success'>✅ SUCCESS! Connection works with empty password!</div>";
    echo "<p>This means the connection should work in config/database.php too.</p>";
    $conn1->close();
} else {
    echo "<div class='error'>❌ FAILED: " . $conn1->connect_error . "</div>";
}

echo "<hr>";

echo "<h3>Test 2: Using config/database.php connection function</h3>";
require_once 'config/database.php';

try {
    $conn2 = getDBConnection();
    if ($conn2) {
        echo "<div class='success'>✅ SUCCESS! Config connection works!</div>";
        $conn2->close();
    } else {
        echo "<div class='error'>❌ FAILED: Connection returned false</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ FAILED: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<h3>Debug Info</h3>";
echo "<p>Host: " . htmlspecialchars($host) . "</p>";
echo "<p>Username: " . htmlspecialchars($username) . "</p>";
echo "<p>Password: '" . htmlspecialchars($password) . "' (length: " . strlen($password) . ")</p>";
echo "<p>Password type: " . gettype($password) . "</p>";
echo "<p>Password === '': " . ($password === '' ? 'true' : 'false') . "</p>";

echo "<hr>";
echo "<p><a href='index.php'>Go to Login Page</a> | <a href='find_password.php'>Find Password</a></p>";
echo "</body></html>";
?>

