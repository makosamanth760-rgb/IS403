<?php
/**
 * Find MySQL Root Password
 * This script tests common passwords to find the correct one
 */

echo "<!DOCTYPE html><html><head><title>Find MySQL Password</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;padding:10px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:10px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:10px 0;}";
echo ".info{color:blue;padding:10px;background:#d1ecf1;border:1px solid #bee5eb;border-radius:5px;margin:10px 0;}";
echo "code{background:#f4f4f4;padding:2px 6px;border-radius:3px;}</style></head><body>";
echo "<h2>MySQL Password Finder</h2>";

$host = "localhost:3307";
$username = "root";

// Common passwords to try
$passwords_to_try = [
    '' => '(empty - no password)',
    'root' => 'root',
    'password' => 'password',
    '123456' => '123456',
    'admin' => 'admin',
    '12345' => '12345',
    'mysql' => 'mysql',
    'xampp' => 'xampp'
];

$found_password = null;
$working_passwords = [];

echo "<h3>Testing passwords on port 3307...</h3>";

foreach ($passwords_to_try as $pass => $label) {
    $conn = @new mysqli($host, $username, $pass);
    
    if (!$conn->connect_error) {
        $working_passwords[] = ['password' => $pass, 'label' => $label];
        echo "<div class='success'>✅ SUCCESS! Password works: <strong>" . htmlspecialchars($label) . "</strong></div>";
        
        if ($found_password === null) {
            $found_password = $pass;
        }
        $conn->close();
    } else {
        echo "<div class='error'>❌ Failed with: " . htmlspecialchars($label) . " - " . $conn->connect_error . "</div>";
    }
}

if ($found_password !== null) {
    echo "<div class='info'>";
    echo "<h3>✅ Password Found!</h3>";
    echo "<p><strong>Update your config/database.php file:</strong></p>";
    echo "<pre style='background:#f4f4f4;padding:15px;border-radius:5px;overflow-x:auto;'>";
    echo "\$password = \"" . addslashes($found_password) . "\";";
    echo "</pre>";
    echo "<p>Or click the button below to auto-update:</p>";
    echo "<form method='POST' action='update_password.php'>";
    echo "<input type='hidden' name='password' value='" . htmlspecialchars($found_password) . "'>";
    echo "<button type='submit' style='padding:10px 20px;background:#28a745;color:white;border:none;border-radius:5px;cursor:pointer;'>Update config/database.php</button>";
    echo "</form>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ No working password found</h3>";
    echo "<p>None of the common passwords worked. You need to:</p>";
    echo "<ol>";
    echo "<li><strong>Find your MySQL root password</strong> - Check XAMPP documentation or your MySQL configuration</li>";
    echo "<li><strong>Reset MySQL password</strong> - Use the instructions below</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>How to Reset MySQL Root Password:</h3>";
echo "<ol>";
echo "<li>Open <strong>XAMPP Control Panel</strong></li>";
echo "<li>Click the <strong>'Shell'</strong> button</li>";
echo "<li>Run these commands:</li>";
echo "</ol>";
echo "<pre style='background:#f4f4f4;padding:15px;border-radius:5px;overflow-x:auto;'>";
echo "# Stop MySQL first\n";
echo "# Then in XAMPP Shell, run:\n";
echo "mysql -u root -p\n";
echo "# (Try common passwords or press Enter if no password)\n";
echo "# Once connected, run:\n";
echo "ALTER USER 'root'@'localhost' IDENTIFIED BY '';\n";
echo "FLUSH PRIVILEGES;\n";
echo "EXIT;\n";
echo "</pre>";

echo "<p><a href='index.php'>Go to Login Page</a></p>";
echo "</body></html>";
?>

