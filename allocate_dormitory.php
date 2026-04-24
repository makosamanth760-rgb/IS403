<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireUserType('student_services');

$message = '';
$message_type = '';

$conn = getDBConnection();

// Get all students
$students_sql = "SELECT student_id, name, email FROM students ORDER BY name";
$students_result = $conn->query($students_sql);
$students = $students_result->fetch_all(MYSQLI_ASSOC);

// Get all rooms with dormitory and floor info
$rooms_sql = "SELECT r.room_id, d.dormitory_name, f.floor_number, r.room_number
              FROM rooms r
              JOIN floors f ON r.floor_id = f.floor_id
              JOIN dormitories d ON f.dormitory_id = d.dormitory_id
              ORDER BY d.dormitory_name, f.floor_number, r.room_number";
$rooms_result = $conn->query($rooms_sql);
$rooms = $rooms_result->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $room_id = intval($_POST['room_id'] ?? 0);
    
    if (empty($student_id) || $room_id <= 0) {
        $message = 'Please select both student and room.';
        $message_type = 'error';
    } else {
        // Check if student already has an allocation
        $check_sql = "SELECT * FROM dormitory_allocations WHERE student_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $student_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing allocation
            $update_sql = "UPDATE dormitory_allocations SET room_id = ? WHERE student_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("is", $room_id, $student_id);
            
            if ($update_stmt->execute()) {
                $message = 'Dormitory allocation updated successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error updating allocation.';
                $message_type = 'error';
            }
            $update_stmt->close();
        } else {
            // Insert new allocation
            $insert_sql = "INSERT INTO dormitory_allocations (student_id, room_id) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("si", $student_id, $room_id);
            
            if ($insert_stmt->execute()) {
                $message = 'Student successfully allocated to dormitory!';
                $message_type = 'success';
            } else {
                $message = 'Error allocating student.';
                $message_type = 'error';
            }
            $insert_stmt->close();
        }
        
        $check_stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocate Dormitory - WPU SMS</title>
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
        <h1>Allocate Student to Dormitory</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="student_id">Select Student *:</label>
                    <select name="student_id" id="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo htmlspecialchars($student['student_id']); ?>">
                                <?php echo htmlspecialchars($student['name'] . ' (' . $student['student_id'] . ') - ' . $student['email']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="room_id">Select Room *:</label>
                    <select name="room_id" id="room_id" required>
                        <option value="">-- Select Room --</option>
                        <?php 
                        $current_dorm = '';
                        foreach ($rooms as $room): 
                            $dorm_name = $room['dormitory_name'];
                            if ($current_dorm !== $dorm_name):
                                if ($current_dorm !== '') echo '</optgroup>';
                                echo '<optgroup label="' . htmlspecialchars($dorm_name) . '">';
                                $current_dorm = $dorm_name;
                            endif;
                        ?>
                            <option value="<?php echo $room['room_id']; ?>">
                                Floor <?php echo $room['floor_number']; ?>, Room <?php echo htmlspecialchars($room['room_number']); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if ($current_dorm !== '') echo '</optgroup>'; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Allocate</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>

