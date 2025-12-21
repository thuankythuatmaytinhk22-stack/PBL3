<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
include '../database.php';
include '../log_action.php'; // Include file ghi log

// Kiểm tra quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Lấy số lượng user để hiển thị badge ở sidebar
$user_count_query = $conn->query("SELECT COUNT(*) as total FROM users");
$user_count = $user_count_query ? $user_count_query->fetch_assoc()['total'] : 0;

$success_message = '';
$error_message = '';

// XỬ LÝ LƯU ĐẶT LỊCH (GIỮ NGUYÊN)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_schedule'])) {
    $pump_number = $_POST['pump_number'];
    $start_time = $_POST['start_time'] . ":00";
    $duration = (int)$_POST['duration'];
    $max_level = isset($_POST['max_level']) ? (int)$_POST['max_level'] : 2; 
    
    if ($pump_number == 3) {
        $stmt1 = $conn->prepare("INSERT INTO pump_schedule (pump_number, start_time, duration_seconds, status) VALUES (1, ?, ?, 'pending')");
        $stmt1->bind_param("si", $start_time, $duration);
        $stmt1->execute();
        
        $stmt2 = $conn->prepare("INSERT INTO pump_schedule (pump_number, start_time, duration_seconds, status) VALUES (2, ?, ?, 'pending')");
        $stmt2->bind_param("si", $start_time, $duration);
        $stmt2->execute();
        
        $success_message = "✅ Đã thêm lịch cho CẢ 2 BƠM. Ngưỡng ngắt an toàn: $max_level cm";
    } else {
        $stmt = $conn->prepare("INSERT INTO pump_schedule (pump_number, start_time, duration_seconds, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("isi", $pump_number, $start_time, $duration);
        
        if ($stmt->execute()) {
            $success_message = "✅ Đã thêm lịch cho Bơm $pump_number. Ngưỡng ngắt an toàn: $max_level cm";
        }
    }

    $command_file = dirname(__DIR__) . "/command.txt";
    $current_cmd = file_exists($command_file) ? trim(file_get_contents($command_file)) : "";
    file_put_contents($command_file, $current_cmd . "|MAX:" . $max_level);
}

// XỬ LÝ XÓA LỊCH (GIỮ NGUYÊN)
if (isset($_GET['del_id'])) {
    $id = (int)$_GET['del_id'];
    $conn->query("DELETE FROM pump_schedule WHERE id = $id");
    $success_message = "✅ Đã xóa lịch trình thành công.";
}

// XỬ LÝ ĐIỀU KHIỂN TRỰC TIẾP (BỔ SUNG MAX_LEVEL CHO CẢ ĐIỀU KHIỂN TAY)
if (isset($_GET['cmd'])) {
    $cmd = $_GET['cmd'];
    $command_file = dirname(__DIR__) . "/command.txt";
    
    // Mặc định ngưỡng ngắt khi điều khiển tay là 8cm như bạn muốn
    $manual_max_level = 8; 
    
    // Nếu là lệnh ON, đính kèm MAX để ESP32 cập nhật ngưỡng ngay lập tức
    if (strpos($cmd, 'ON') !== false) {
        file_put_contents($command_file, $cmd . "|MAX:" . $manual_max_level);
    } else {
        file_put_contents($command_file, $cmd);
    }
    
    // Ghi nhật ký
    if ($cmd == 'R1_ON') {
        log_pump_action(1, 'ON', null, null, null, 'Điều khiển thủ công');
        $success_message = "✅ Đã bật Bơm 1 (Ngưỡng ngắt: $manual_max_level cm)";
    } elseif ($cmd == 'R1_OFF') {
        log_pump_action(1, 'OFF', null, null, null, 'Điều khiển thủ công');
        $success_message = "✅ Đã tắt Bơm 1";
    } elseif ($cmd == 'R2_ON') {
        log_pump_action(2, 'ON', null, null, null, 'Điều khiển thủ công');
        $success_message = "✅ Đã bật Bơm 2 (Ngưỡng ngắt: $manual_max_level cm)";
    } elseif ($cmd == 'R2_OFF') {
        log_pump_action(2, 'OFF', null, null, null, 'Điều khiển thủ công');
        $success_message = "✅ Đã tắt Bơm 2";
    } elseif ($cmd == 'R1_ON|R2_ON' || $cmd == 'BOTH_ON') {
        log_pump_action(3, 'ON', null, null, null, 'Điều khiển thủ công - Cả 2 bơm');
        $success_message = "✅ Đã bật cả 2 bơm (Ngưỡng ngắt: $manual_max_level cm)";
    } elseif ($cmd == 'R1_OFF|R2_OFF' || $cmd == 'BOTH_OFF') {
        log_pump_action(3, 'OFF', null, null, null, 'Điều khiển thủ công - Cả 2 bơm');
        $success_message = "✅ Đã tắt cả 2 bơm";
    } else {
        $success_message = "✅ Đã gửi lệnh: " . str_replace('|', ' và ', $cmd);
    }
}

// ĐỌC TRẠNG THÁI HIỆN TẠI (GIỮ NGUYÊN)
$command_file = dirname(__DIR__) . "/command.txt";
$current_command = file_exists($command_file) ? trim(file_get_contents($command_file)) : "OFF";
$relay1_status = (strpos($current_command, 'R1_ON') !== false) ? 'ON' : 'OFF';
$relay2_status = (strpos($current_command, 'R2_ON') !== false) ? 'ON' : 'OFF';

$schedules = $conn->query("SELECT * FROM pump_schedule ORDER BY start_time ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Điều khiển Relay - Quản trị</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f6fa; display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #2c3e50; color: white; height: 100vh; position: fixed; overflow-y: auto; }
        .sidebar-header { padding: 25px 20px; background: #34495e; text-align: center; border-bottom: 1px solid #465a6d; }
        .sidebar-menu { list-style: none; padding: 0; }
        .menu-section-title { padding: 15px 20px 5px; color: #95a5a6; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 20px; color: #bdc3c7; text-decoration: none; transition: 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #34495e; color: white; border-left: 4px solid #3498db; }
        .sidebar-menu i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-badge { background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; margin-left: auto; }
        .main-content { margin-left: 280px; padding: 25px; width: calc(100% - 280px); }
        .header-box { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .card h3 { margin-bottom: 20px; border-bottom: 2px solid #f1f2f6; padding-bottom: 10px; color: #2c3e50; }
        .relay-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .relay-card { padding: 25px; border-radius: 15px; text-align: center; border: 2px solid #ecf0f1; transition: 0.3s; }
        .relay-card.active-on { border-color: #2ecc71; background: #f0fff4; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.3s; margin: 5px; }
        .btn-on { background: #2ecc71; color: white; }
        .btn-off { background: #e74c3c; color: white; }
        .btn-on:hover { background: #27ae60; }
        .btn-both { background: #3498db; color: white; }
        .btn-both:hover { background: #2980b9; }
        .schedule-form { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr auto; gap: 15px; align-items: flex-end; background: #f8f9fa; padding: 20px; border-radius: 10px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #7f8c8d; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .badge-pending { background: #fef9e7; color: #f1c40f; }
        .badge-completed { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-microchip"></i> HỆ THỐNG LẤY MẪU</h3>
        </div>
        <ul class="sidebar-menu">
            <div class="menu-section-title">Tổng quan</div>
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <div class="menu-section-title">Điều khiển</div>
            <li><a href="control.php" class="active"><i class="fas fa-toggle-on"></i> Điều khiển & Lịch</a></li>
            <li><a href="set_location.php"><i class="fas fa-map-marker-alt"></i> Vị trí lấy mẫu</a></li>
            <li><a href="logs.php"><i class="fas fa-history"></i> Nhật ký hệ thống</a></li>
            <div class="menu-section-title">Hệ thống</div>
            <li><a href="users.php"><i class="fas fa-users"></i> Quản lý Users <span class="menu-badge"><?php echo $user_count; ?></span></a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header-box">
            <div>
                <h2>Điều khiển & Đặt lịch</h2>
                <small id="server-time">Giờ hệ thống: <?php echo date('H:i:s'); ?></small>
            </div>
            <div style="text-align: right;">
                <span class="badge badge-completed">Online</span>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3><i class="fas fa-bolt"></i> Điều khiển bơm trực tiếp</h3>
            <div class="relay-grid">
                <div class="relay-card <?php echo $relay1_status=='ON'?'active-on':''; ?>">
                    <i class="fas fa-faucet-drip" style="font-size: 2rem; color: #3498db;"></i>
                    <h4>Bơm số 1</h4>
                    <p>Trạng thái: <strong><?php echo $relay1_status; ?></strong></p>
                    <button onclick="location.href='control.php?cmd=R1_ON'" class="btn btn-on">BẬT</button>
                    <button onclick="location.href='control.php?cmd=R1_OFF'" class="btn btn-off">TẮT</button>
                </div>
                <div class="relay-card <?php echo $relay2_status=='ON'?'active-on':''; ?>">
                    <i class="fas fa-faucet-drip" style="font-size: 2rem; color: #3498db;"></i>
                    <h4>Bơm số 2</h4>
                    <p>Trạng thái: <strong><?php echo $relay2_status; ?></strong></p>
                    <button onclick="location.href='control.php?cmd=R2_ON'" class="btn btn-on">BẬT</button>
                    <button onclick="location.href='control.php?cmd=R2_OFF'" class="btn btn-off">TẮT</button>
                </div>
                <div class="relay-card">
                    <i class="fas fa-water" style="font-size: 2rem; color: #3498db;"></i>
                    <h4>Cả 2 bơm</h4>
                    <p>Điều khiển cùng lúc</p>
                    <button onclick="location.href='control.php?cmd=R1_ON|R2_ON'" class="btn btn-both">BẬT CẢ 2</button>
                    <button onclick="location.href='control.php?cmd=R1_OFF|R2_OFF'" class="btn btn-off">TẮT CẢ 2</button>
                </div>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-calendar-plus"></i> Thiết lập lịch tự động</h3>
            <form method="POST" class="schedule-form">
                <div class="form-group">
                    <label>Chọn thiết bị</label>
                    <select name="pump_number">
                        <option value="1">Bơm số 1</option>
                        <option value="2">Bơm số 2</option>
                        <option value="3">Cả 2 bơm</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Giờ bắt đầu</label>
                    <input type="time" name="start_time" required>
                </div>
                <div class="form-group">
                    <label>Thời gian chạy (giây)</label>
                    <input type="number" name="duration" placeholder="Ví dụ: 30" required>
                </div>
                <div class="form-group">
                    <label>Ngưỡng ngắt (cm)</label>
                    <input type="number" name="max_level" value="8" title="Cách cảm biến bao nhiêu cm thì dừng?" required>
                </div>
                <button type="submit" name="add_schedule" class="btn btn-on" style="background:#3498db">THÊM MỚI</button>
            </form>

            <table>
                <tr>
                    <th>Thiết bị</th>
                    <th>Thời gian</th>
                    <th>Thời lượng</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
                <?php while($row = $schedules->fetch_assoc()): ?>
                <tr>
                    <td>Bơm <?php echo $row['pump_number']; ?></td>
                    <td><i class="far fa-clock"></i> <?php echo $row['start_time']; ?></td>
                    <td><?php echo $row['duration_seconds']; ?>s</td>
                    <td>
                        <span class="badge <?php echo $row['status']=='pending'?'badge-pending':'badge-completed'; ?>">
                            <?php echo $row['status']=='pending'?'Chờ chạy':'Hoàn thành'; ?>
                        </span>
                    </td>
                    <td>
                        <a href="control.php?del_id=<?php echo $row['id']; ?>" style="color:#e74c3c" onclick="return confirm('Xóa lịch này?')">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <script>
        setInterval(function(){
            fetch('../check_schedule.php')
            .then(res => res.text())
            .then(data => console.log("Check schedule:", data));
        }, 10000);
    </script>
</body>
</html>