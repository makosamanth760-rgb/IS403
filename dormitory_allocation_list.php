<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireUserType('student_services');

$conn = getDBConnection();

$selected_dormitory = intval($_GET['dormitory_id'] ?? 0);

$dormitories_sql = "SELECT dormitory_id, dormitory_name FROM dormitories ORDER BY dormitory_name";
$dormitories_result = $conn->query($dormitories_sql);
$dormitories = $dormitories_result->fetch_all(MYSQLI_ASSOC);

$allocations = [];

if ($selected_dormitory > 0) {
    $sql = "SELECT s.student_id, s.name, s.email, d.dormitory_name, f.floor_number, r.room_number, da.allocation_date
            FROM dormitory_allocations da
            JOIN students s ON da.student_id = s.student_id
            JOIN rooms r ON da.room_id = r.room_id
            JOIN floors f ON r.floor_id = f.floor_id
            JOIN dormitories d ON f.dormitory_id = d.dormitory_id
            WHERE d.dormitory_id = ?
            ORDER BY f.floor_number, r.room_number, s.name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $selected_dormitory);
    $stmt->execute();
    $allocations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $sql = "SELECT s.student_id, s.name, s.email, d.dormitory_name, f.floor_number, r.room_number, da.allocation_date
            FROM dormitory_allocations da
            JOIN students s ON da.student_id = s.student_id
            JOIN rooms r ON da.room_id = r.room_id
            JOIN floors f ON r.floor_id = f.floor_id
            JOIN dormitories d ON f.dormitory_id = d.dormitory_id
            ORDER BY d.dormitory_name, f.floor_number, r.room_number, s.name";
    $result = $conn->query($sql);
    $allocations = $result->fetch_all(MYSQLI_ASSOC);
}

$count_sql = "SELECT COUNT(*) AS total FROM dormitory_allocations";
$count_result = $conn->query($count_sql);
$total_allocations = intval($count_result->fetch_assoc()['total'] ?? 0);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dormitory Allocation List - WPU SMS</title>
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
                <a href="allocate_dormitory.php">Allocate Dormitory</a>
                <a href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Dormitory Allocation List Report</h1>

        <div class="card" style="margin-bottom: 20px;">
            <form method="GET" action="">
                <div class="form-group">
                    <label for="dormitory_id">Filter By Dormitory</label>
                    <select name="dormitory_id" id="dormitory_id">
                        <option value="0">All Dormitories</option>
                        <?php foreach ($dormitories as $dorm): ?>
                            <option value="<?php echo intval($dorm['dormitory_id']); ?>"
                                <?php echo $selected_dormitory === intval($dorm['dormitory_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dorm['dormitory_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Load Dormitory List</button>
            </form>
        </div>

        <div class="card">
            <h3>Allocation Summary</h3>
            <p><strong>Total Allocations:</strong> <?php echo $total_allocations; ?></p>

            <?php if (!empty($allocations)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Dormitory</th>
                            <th>Floor</th>
                            <th>Room</th>
                            <th>Allocation Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allocations as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['dormitory_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['floor_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($row['allocation_date']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No dormitory allocations found for the selected filter.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
