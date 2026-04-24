<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';
    
    if (empty($email) || empty($password) || empty($user_type)) {
        $error = 'Please fill in all fields.';
    } else {
        $conn = getDBConnection();
        
        if ($user_type === 'student') {
            $sql = "SELECT student_id, name, email, password FROM students WHERE email = ?";
        } else {
            $sql = "SELECT staff_id, name, email, password, role FROM staff WHERE email = ? AND role = ?";
        }
        
        $stmt = $conn->prepare($sql);
        
        if ($user_type === 'student') {
            $stmt->bind_param("s", $email);
        } else {
            $role = ($user_type === 'registrar') ? 'registrar' : 'student_services';
            $stmt->bind_param("ss", $email, $role);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user_type === 'student') {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['student_id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = 'student';
                    header('Location: dashboard.php');
                    exit();
                }
            } else {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['staff_id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = $user_type;
                    header('Location: dashboard.php');
                    exit();
                }
            }
        }
        
        $error = 'Invalid email or password.';
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WPU Student Management System - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <div class="logo-container">
                <img src="assets/image/WPU-Logo-Main.webp" alt="Western Pacific University Logo" class="university-logo">
            </div>

            <h2>Student Management System</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="user_type">Login As:</label>
                    <select name="user_type" id="user_type" required>
                        <option value="">Select User Type</option>
                        <option value="student">Student</option>
                        <option value="registrar">Registrar's Office</option>
                        <option value="student_services">Student Services Office</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <div class="login-footer">
                <p>© <?php echo date('Y'); ?> Western Pacific University. All rights reserved.</p>
                <p class="motto">Pro Deo et Patria</p>
            </div>
        </div>
    </div>
</body>
</html>

