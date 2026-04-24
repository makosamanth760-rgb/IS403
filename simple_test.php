<?php
/**
 * Simple direct connection test
 */

echo "<!DOCTYPE html><html><head><title>Simple Connection Test</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;padding:15px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:15px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:10px 0;}";
echo "pre{background:#f4f4f4;padding:15px;border-radius:5px;overflow-x:auto;}";
echo "</style></head><body>";
echo "<h2>Simple Direct Connection Test</h2>";

// Test 1: Direct connection with hardcoded values
echo "<h3>Test 1: Direct connection (hardcoded)</h3>";
$host = "localhost:3307";
$user = "root";
$pass = ""; // Empty string

echo "<pre>";
echo "Host: $host\n";
echo "User: $user\n";
echo "Pass: '$pass' (length: " . strlen($pass) . ")\n";
echo "Pass === '': " . ($pass === '' ? 'true' : 'false') . "\n";
echo "</pre>";

$conn1 = @new mysqli($host, $user, $pass);
if (!$conn1->connect_error) {
    echo "<div class='success'>✅ SUCCESS! Direct connection works!</div>";
    $conn1->close();
} else {
    echo "<div class='error'>❌ FAILED: " . $conn1->connect_error . "</div>";
}

echo "<hr>";

// Test 2: Using config file variables
echo "<h3>Test 2: Using config file variables</h3>";
require_once 'config/database.php';

echo "<pre>";
echo "Host: $host\n";
echo "Username: $username\n";
echo "Password: '$password' (length: " . strlen($password) . ")\n";
echo "Password === '': " . ($password === '' ? 'true' : 'false') . "\n";
echo "Password type: " . gettype($password) . "\n";
echo "</pre>";

$conn2 = @new mysqli($host, $username, $password);
if (!$conn2->connect_error) {
    echo "<div class='success'>✅ SUCCESS! Config variables work!</div>";
    $conn2->close();
} else {
    echo "<div class='error'>❌ FAILED: " . $conn2->connect_error . "</div>";
}

echo "<hr>";

// Test 3: Using config function
echo "<h3>Test 3: Using config function getDBConnection()</h3>";
try {
    $conn3 = getDBConnection();
    if ($conn3 && !$conn3->connect_error) {
        echo "<div class='success'>✅ SUCCESS! Config function works!</div>";
        $conn3->close();
    } else {
        echo "<div class='error'>❌ FAILED: Function returned invalid connection</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ FAILED: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='index.php'>Go to Login Page</a></p>";
echo "</body></html>";
?>

