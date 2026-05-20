<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectAfterLogin();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $login_password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? '';

    if (empty($email) || empty($login_password) || empty($user_type)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $conn = getDBConnection();

            if (!databaseTablesReady($conn)) {
                $error = 'Database tables are missing. Please run setup_database_tables.php first.';
            } else {
                if ($user_type === 'student') {
                    $sql = 'SELECT student_id, name, email, password FROM students WHERE email = ? LIMIT 1';
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('s', $email);
                } else {
                    $role = ($user_type === 'registrar') ? 'registrar' : 'student_services';
                    $sql = 'SELECT staff_id, name, email, password, role FROM staff WHERE email = ? AND role = ? LIMIT 1';
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('ss', $email, $role);
                }

                if (!$stmt) {
                    $error = 'Login failed. Please try again or run setup_database_tables.php.';
                } else {
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();

                        if (verifyLoginPassword($login_password, $user['password'])) {
                            if ($user_type === 'student') {
                                if (shouldUpgradePasswordHash($user['password'])) {
                                    upgradeAccountPassword(
                                        $conn,
                                        'students',
                                        'student_id',
                                        $user['student_id'],
                                        $login_password
                                    );
                                }
                                $_SESSION['user_id'] = $user['student_id'];
                                $_SESSION['user_name'] = $user['name'];
                                $_SESSION['user_email'] = $user['email'];
                                $_SESSION['user_type'] = 'student';
                            } else {
                                if (shouldUpgradePasswordHash($user['password'])) {
                                    upgradeAccountPassword(
                                        $conn,
                                        'staff',
                                        'staff_id',
                                        $user['staff_id'],
                                        $login_password
                                    );
                                }
                                $_SESSION['user_id'] = $user['staff_id'];
                                $_SESSION['user_name'] = $user['name'];
                                $_SESSION['user_email'] = $user['email'];
                                $_SESSION['user_type'] = $user_type;
                            }

                            $stmt->close();
                            $conn->close();
                            redirectAfterLogin();
                        }
                    }

                    $error = 'Invalid email or password. Check your Login As role matches the account.';
                    $stmt->close();
                }
            }

            $conn->close();
        } catch (Throwable $e) {
            $error = 'Could not connect to the wpu database. Check MySQL is running and config/database.php settings.';
        }
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
                <img src="assets/image/WPU-Logo-Main.png" alt="Western Pacific University Logo" class="university-logo">
            </div>

            <h2>Student Management System</h2>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="index.php" class="login-form">
                <div class="form-group">
                    <label for="user_type">Login As:</label>
                    <select name="user_type" id="user_type" required>
                        <option value="">Select User Type</option>
                        <option value="student" <?php echo (($_POST['user_type'] ?? '') === 'student') ? 'selected' : ''; ?>>Student</option>
                        <option value="registrar" <?php echo (($_POST['user_type'] ?? '') === 'registrar') ? 'selected' : ''; ?>>Registrar's Office</option>
                        <option value="student_services" <?php echo (($_POST['user_type'] ?? '') === 'student_services') ? 'selected' : ''; ?>>Student Services Office</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>

            <div class="login-footer">
                <p class="text-muted" style="font-size:12px;margin-bottom:12px;">
                    <strong>wpu</strong> database accounts (run setup once):<br>
                    Registrar — <code>registrar@wpu.edu</code><br>
                    Student Services — <code>services@wpu.edu</code><br>
                    Student — <code>student@wpu.edu</code><br>
                    Password for all: <code>password</code>
                </p>
                <p style="font-size:12px;">
                    <a href="setup_database_tables.php">Set up database tables</a>
                    &nbsp;|&nbsp;
                    <a href="fix_passwords.php">Reset demo passwords</a>
                </p>
                <p>© <?php echo date('Y'); ?> Western Pacific University. All rights reserved.</p>
                <p class="motto">Pro Deo et Patria</p>
            </div>
        </div>
    </div>
</body>
</html>
