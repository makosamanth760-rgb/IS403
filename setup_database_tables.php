<?php
/**
 * Setup Database Tables
 * This script will create all necessary tables and insert sample data
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Database Setup</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;font-weight:bold;padding:15px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:15px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:10px 0;}";
echo "pre{background:#f4f4f4;padding:15px;border-radius:5px;overflow-x:auto;}";
echo "</style></head><body>";
echo "<h2>Database Setup - Creating Tables and Sample Data</h2>";

try {
    $conn = getDBConnection();
    
    // Read and execute the schema file
    $schema_file = 'database/schema.sql';
    
    if (!file_exists($schema_file)) {
        die("<div class='error'>Schema file not found: $schema_file</div>");
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
    
    echo "<div class='success'>✅ Database setup completed!</div>";
    echo "<p>Successfully executed: $success_count statements</p>";
    if ($error_count > 0) {
        echo "<p>Errors: $error_count (some may be expected if tables already exist)</p>";
    }
    
    echo "<hr>";
    echo "<h3>Sample Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Registrar:</strong> registrar@wpu.edu / password: <code>password</code></li>";
    echo "<li><strong>Student Services:</strong> services@wpu.edu / password: <code>password</code></li>";
    echo "<li><strong>Students:</strong> Create students through Registrar's Office</li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<p><a href='index.php'><button style='padding:10px 20px;background:#667eea;color:white;border:none;border-radius:5px;cursor:pointer;font-size:16px;'>Go to Login Page</button></a></p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>

