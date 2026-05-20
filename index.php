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

      <!-- Static error message placeholder -->
      <div class="error-message">Please fill in all fields.</div>

      <form class="login-form">
        <div class="form-group">
          <label for="user_type">Login As:</label>
          <select id="user_type" required>
            <option value="">Select User Type</option>
            <option value="student">Student</option>
            <option value="registrar">Registrar's Office</option>
            <option value="student_services">Student Services Office</option>
          </select>
        </div>

        <div class="form-group">
          <label for="email">Email:</label>
          <input type="email" id="email" required>
        </div>

        <div class="form-group">
          <label for="password">Password:</label>
          <input type="password" id="password" required>
        </div>

        <button type="submit" class="btn btn-primary">Login</button>
      </form>

      <div class="login-footer">
        <p>© 2026 Western Pacific University. All rights reserved.</p>
        <p class="motto">Pro Deo et Patria</p>
      </div>
    </div>
  </div>
</body>
</html>
