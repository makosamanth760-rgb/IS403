<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireUserType('student_services');

$conn = getDBConnection();

// Get all dormitories
$dormitories_sql = "SELECT dormitory_id, dormitory_name FROM dormitories ORDER BY dormitory_name";
$dormitories_result = $conn->query($dormitories_sql);
$dormitories = $dormitories_result->fetch_all(MYSQLI_ASSOC);

$selected_dormitory = $_GET['dormitory_id'] ?? null;
$students_in_dormitory = array();

if ($selected_dormitory) {
    $sql = "SELECT s.student_id, s.name, s.email, d.dormitory_name, f.floor_number, r.room_number
            FROM dormitory_allocations da
            JOIN students s ON da.student_id = s.student_id
            JOIN rooms r ON da.room_id = r.room_id
            JOIN floors f ON r.floor_id = f.floor_id
            JOIN dormitories d ON f.dormitory_id = d.dormitory_id
            WHERE d.dormitory_id = ?
            ORDER BY d.dormitory_name, f.floor_number, r.room_number, s.name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_dormitory);
    $stmt->execute();
    $students_in_dormitory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Services Dashboard - WPU SMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="../assets/image/WPU-Logo-Main.webp" alt="WPU Logo" class="navbar-logo">
            </div>
            <h2>WPU Student Management System</h2>
            <div class="nav-links">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="allocate_dormitory.php">Allocate Dormitory</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h1>Student Services Office Dashboard</h1>
        </div>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>Quick Actions</h3>
                <div class="card-content">
                    <ul class="action-list">
                        <li><a href="allocate_dormitory.php" class="btn btn-primary">Allocate Student to Dormitory</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <h3>View Students by Dormitory</h3>
                <div class="card-content">
                    <form method="GET" action="">
                    <div class="form-group">
                        <label for="dormitory_id">Select Dormitory:</label>
                        <select name="dormitory_id" id="dormitory_id" onchange="this.form.submit()">
                            <option value="">-- Select Dormitory --</option>
                            <?php foreach ($dormitories as $dorm): ?>
                                <option value="<?php echo $dorm['dormitory_id']; ?>" 
                                        <?php echo ($selected_dormitory == $dorm['dormitory_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dorm['dormitory_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                
                <?php if ($selected_dormitory && count($students_in_dormitory) > 0): ?>
                    <h4>Students in <?php echo htmlspecialchars($students_in_dormitory[0]['dormitory_name']); ?>:</h4>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Floor</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students_in_dormitory as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['floor_number']); ?></td>
                                    <td><?php echo htmlspecialchars($student['room_number']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php elseif ($selected_dormitory): ?>
                    <p>No students are currently allocated to this dormitory.</p>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

