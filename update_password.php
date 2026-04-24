<?php
/**
 * Update Password in config/database.php
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $new_password = $_POST['password'];
    $config_file = 'config/database.php';
    
    // Read the current config file
    $config_content = file_get_contents($config_file);
    
    // Replace the password line
    $pattern = '/\$password\s*=\s*"[^"]*";/';
    $replacement = '$password = "' . addslashes($new_password) . '";';
    $config_content = preg_replace($pattern, $replacement, $config_content);
    
    // Write back to file
    if (file_put_contents($config_file, $config_content)) {
        echo "<!DOCTYPE html><html><head><title>Password Updated</title>";
        echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
        echo ".success{color:green;font-weight:bold;padding:15px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:20px 0;}";
        echo "button{padding:10px 20px;background:#667eea;color:white;border:none;border-radius:5px;cursor:pointer;font-size:16px;}</style></head><body>";
        echo "<div class='success'>✅ Password updated successfully in config/database.php!</div>";
        echo "<p>The password has been set to: <strong>" . ($new_password === '' ? '(empty)' : htmlspecialchars($new_password)) . "</strong></p>";
        echo "<p><a href='index.php'><button>Go to Login Page</button></a></p>";
        echo "</body></html>";
    } else {
        echo "<!DOCTYPE html><html><head><title>Update Failed</title>";
        echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
        echo ".error{color:red;padding:15px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:20px 0;}";
        echo "</style></head><body>";
        echo "<div class='error'>❌ Error: Could not write to config/database.php. Please check file permissions.</div>";
        echo "<p>You can manually update the file by changing this line:</p>";
        echo "<pre>\$password = \"" . addslashes($new_password) . "\";</pre>";
        echo "<p><a href='index.php'>Go to Login Page</a></p>";
        echo "</body></html>";
    }
} else {
    header('Location: find_password.php');
    exit();
}
?>

