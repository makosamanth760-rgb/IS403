<?php
/**
 * Database Setup and Configuration Helper
 * This script helps you configure the database connection
 */

$step = $_GET['step'] ?? '1';
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'];
    
    // Test connection with provided password
    $test_conn = @new mysqli('localhost', 'root', $password);
    
    if (!$test_conn->connect_error) {
        // Update config file
        $config_file = 'config/database.php';
        $config_content = file_get_contents($config_file);
        
        // Replace the password line
        $old_pattern = "/define\('DB_PASS',\s*'[^']*'\);/";
        $new_line = "define('DB_PASS', '" . addslashes($password) . "');";
        $config_content = preg_replace($old_pattern, $new_line, $config_content);
        
        if (file_put_contents($config_file, $config_content)) {
            $message = "✅ Configuration updated successfully! Password has been saved.";
            $success = true;
            
            // Test if we can now connect
            require_once 'config/database.php';
            $conn = getDBConnection();
            if ($conn) {
                $message .= "<br>✅ Database connection successful!";
                $conn->close();
            }
        } else {
            $message = "❌ Error: Could not write to config file. Please check file permissions.";
        }
        
        $test_conn->close();
    } else {
        $message = "❌ Connection failed: " . $test_conn->connect_error . "<br>Please try a different password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - WPU SMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #667eea; }
        .step {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .step h3 { margin-top: 0; color: #667eea; }
        input[type="password"], input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #5568d3; }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        a { color: #667eea; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Configuration Setup</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($step == '1'): ?>
            <div class="step">
                <h3>Step 1: Enter Your MySQL Root Password</h3>
                <p>Your MySQL root user requires a password. Please enter it below:</p>
                
                <form method="POST" action="">
                    <label><strong>MySQL Root Password:</strong></label>
                    <input type="password" name="password" placeholder="Enter password (leave empty if no password)" autofocus>
                    <br>
                    <button type="submit">Test & Save Configuration</button>
                </form>
                
                <p style="margin-top: 20px;"><strong>Don't know your password?</strong></p>
                <p>Try these common passwords:</p>
                <ul>
                    <li>Leave empty (no password)</li>
                    <li><code>root</code></li>
                    <li><code>password</code></li>
                    <li><code>123456</code></li>
                </ul>
            </div>
            
            <div class="step">
                <h3>Step 2: Reset MySQL Password (If Needed)</h3>
                <p>If you don't know your MySQL root password, you can reset it:</p>
                <ol>
                    <li>Open <strong>XAMPP Control Panel</strong></li>
                    <li>Click the <strong>"Shell"</strong> button</li>
                    <li>Run these commands:</li>
                </ol>
                <pre style="background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto;">
# Stop MySQL first (if running)
# Then start MySQL in safe mode:
mysqld --skip-grant-tables

# In another shell window, connect:
mysql -u root

# Then run these SQL commands:
USE mysql;
UPDATE user SET authentication_string=PASSWORD('') WHERE User='root';
FLUSH PRIVILEGES;
EXIT;
                </pre>
                <p><strong>Or simpler method:</strong></p>
                <ol>
                    <li>Open phpMyAdmin: <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a></li>
                    <li>If you can access it, go to "User accounts" → "root" → "Change password"</li>
                    <li>Set password to empty or your preferred password</li>
                </ol>
            </div>
            
        <?php elseif ($success): ?>
            <div class="step">
                <h3>✅ Setup Complete!</h3>
                <p>Your database configuration has been saved successfully.</p>
                <p><strong>Next steps:</strong></p>
                <ol>
                    <li>Make sure the <strong>WPU</strong> database exists</li>
                    <li>Import the database schema from <code>database/schema.sql</code> using phpMyAdmin</li>
                    <li>Or the system will create the database automatically on first use</li>
                </ol>
                <p><a href="index.php"><button>Go to Login Page</button></a></p>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p><a href="test_connection.php">Test Connection</a> | <a href="index.php">Go to Login</a></p>
        </div>
    </div>
</body>
</html>

