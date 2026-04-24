<?php
/**
 * Fix Staff Passwords
 * This script will update staff passwords to use the correct hash
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Fix Passwords</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;padding:15px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:15px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:10px 0;}";
echo "</style></head><body>";
echo "<h2>Fix Staff Passwords</h2>";

try {
    $conn = getDBConnection();
    
    // Hash for password "password"
    $hashed_password = password_hash('password', PASSWORD_DEFAULT);
    
    // Update registrar password
    $sql1 = "UPDATE staff SET password = ? WHERE staff_id = 'REG001'";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("s", $hashed_password);
    
    if ($stmt1->execute()) {
        echo "<div class='success'>✅ Updated REG001 password</div>";
    } else {
        echo "<div class='error'>❌ Error updating REG001: " . $conn->error . "</div>";
    }
    $stmt1->close();
    
    // Update student services password
    $sql2 = "UPDATE staff SET password = ? WHERE staff_id = 'SS001'";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("s", $hashed_password);
    
    if ($stmt2->execute()) {
        echo "<div class='success'>✅ Updated SS001 password</div>";
    } else {
        echo "<div class='error'>❌ Error updating SS001: " . $conn->error . "</div>";
    }
    $stmt2->close();
    
    echo "<hr>";
    echo "<div class='success'>✅ All passwords updated!</div>";
    echo "<p><strong>Login credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Registrar:</strong> registrar@wpu.edu / password: <code>password</code></li>";
    echo "<li><strong>Student Services:</strong> services@wpu.edu / password: <code>password</code></li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<p><a href='index.php'><button style='padding:10px 20px;background:#667eea;color:white;border:none;border-radius:5px;cursor:pointer;font-size:16px;'>Go to Login Page</button></a></p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>

