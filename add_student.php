<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireUserType('registrar');

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($student_id) || empty($name) || empty($gender) || empty($email) || empty($password)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
    } else {
        $conn = getDBConnection();
        
        // Check if student ID or email already exists
        $check_sql = "SELECT * FROM students WHERE student_id = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $student_id, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = 'Student ID or email already exists.';
            $message_type = 'error';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new student
            $insert_sql = "INSERT INTO students (student_id, name, gender, address, contact_number, email, password) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssssss", $student_id, $name, $gender, $address, $contact_number, $email, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $message = 'Student successfully added! Student ID: ' . htmlspecialchars($student_id) . ', Email: ' . htmlspecialchars($email) . ', Password: ' . htmlspecialchars($password);
                $message_type = 'success';
                
                // Clear form data
                $_POST = array();
            } else {
                $message = 'Error adding student.';
                $message_type = 'error';
            }
            
            $insert_stmt->close();
        }
        
        $check_stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Student - WPU SMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="../assets/image/WPU-Logo-Main.webp" alt="WPU Logo" class="navbar-logo">
            </div>
            <h2>WPU Student Management System</h2>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="dashboard.php">Dashboard</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Add New Student</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="student_id">Student ID *:</label>
                    <input type="text" name="student_id" id="student_id" 
                           value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="name">Name *:</label>
                    <input type="text" name="name" id="name" 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="gender">Gender *:</label>
                    <select name="gender" id="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="address">Address:</label>
                    <textarea name="address" id="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="contact_number">Contact Number:</label>
                    <input type="text" name="contact_number" id="contact_number" 
                           value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email *:</label>
                    <input type="email" name="email" id="email" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *:</label>
                    <input type="password" name="password" id="password" required>
                    <small>This password will be provided to the student for login.</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Add Student</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>

