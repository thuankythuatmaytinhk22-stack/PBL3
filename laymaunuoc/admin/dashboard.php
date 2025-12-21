<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
include '../database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Lấy thống kê
$stats = [];
try {
    // Tổng số users
    $result = $conn->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Tổng số admin
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='admin'");
    $stats['total_admins'] = $result ? $result->fetch_assoc()['total'] : 0;
    
    // Đọc trạng thái relay từ file command.txt ở thư mục gốc
    $command_file = dirname(__DIR__) . "/command.txt";
    $current_command = file_exists($command_file) ? trim(file_get_contents($command_file)) : "OFF";
    $relay1_status = (strpos($current_command, 'R1_ON') !== false) ? 'ON' : 'OFF';
    $relay2_status = (strpos($current_command, 'R2_ON') !== false) ? 'ON' : 'OFF';
    
    $stats['relay1_status'] = $relay1_status;
    $stats['relay2_status'] = $relay2_status;
    $stats['active_controls'] = ($relay1_status === 'ON' ? 1 : 0) + ($relay2_status === 'ON' ? 1 : 0);
    
} catch (Exception $e) {
    $stats['total_users'] = 0;
    $stats['relay1_status'] = 'OFF';
    $stats['relay2_status'] = 'OFF';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hệ thống quản trị</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f6fa; display: flex; min-height: 100vh; }

        /* SIDEBAR ĐỒNG BỘ VỚI CONTROL.PHP */
        .sidebar { width: 280px; background: #2c3e50; color: white; height: 100vh; position: fixed; overflow-y: auto; }
        .sidebar-header { padding: 25px 20px; background: #34495e; text-align: center; border-bottom: 1px solid #465a6d; }
        .sidebar-menu { list-style: none; padding: 0; }
        .menu-section-title { padding: 15px 20px 5px; color: #95a5a6; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 20px; color: #bdc3c7; text-decoration: none; transition: 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #34495e; color: white; border-left: 4px solid #3498db; }
        .sidebar-menu i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-badge { background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; margin-left: auto; }

        /* MAIN CONTENT */
        .main-content { margin-left: 280px; padding: 25px; width: calc(100% - 280px); }
        .header-box { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        
        /* STAT CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center; border-bottom: 4px solid #3498db; transition: 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card i { font-size: 2rem; color: #3498db; margin-bottom: 10px; }
        .stat-number { font-size: 1.8rem; font-weight: bold; color: #2c3e50; }
        .stat-label { color: #7f8c8d; font-size: 0.9rem; margin-top: 5px; }

        /* RELAY STATUS */
        .status-container { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .status-container h3 { margin-bottom: 20px; color: #2c3e50; display: flex; align-items: center; gap: 10px; }
        .relay-list { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .relay-box { padding: 20px; background: #f8f9fa; border-radius: 10px; text-align: center; border: 1px solid #eee; }
        .badge-on { background: #2ecc71; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; }
        .badge-off { background: #95a5a6; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; }

        .btn-web { background: #3498db; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 0.9rem; transition: 0.3s; }
        .btn-web:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-microchip"></i> HỆ THỐNG LẤY MẪU</h3>
        </div>
        <ul class="sidebar-menu">
            <div class="menu-section-title">Tổng quan</div>
            <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            
            <div class="menu-section-title">Điều khiển</div>
            <li><a href="control.php"><i class="fas fa-toggle-on"></i> Điều khiển & Lịch</a></li>
            <li><a href="set_location.php"><i class="fas fa-map-marker-alt"></i> Vị trí lấy mẫu</a></li>
            <li><a href="logs.php"><i class="fas fa-history"></i> Nhật ký hệ thống</a></li>
            
            <div class="menu-section-title">Hệ thống</div>
            <li><a href="users.php"><i class="fas fa-users"></i> Quản lý Users <span class="menu-badge"><?php echo $stats['total_users']; ?></span></a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header-box">
            <div>
                <h1>Dashboard Quản Trị</h1>
                <small>Chào mừng trở lại, <strong><?php echo $_SESSION['username']; ?></strong></small>
            </div>
            <a href="../index.php" class="btn-web"><i class="fas fa-external-link-alt"></i> Xem Trang Web</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Tổng số người dùng</div>
            </div>
            <div class="stat-card" style="border-color: #f1c40f;">
                <i class="fas fa-user-shield" style="color: #f1c40f;"></i>
                <div class="stat-number"><?php echo $stats['total_admins']; ?></div>
                <div class="stat-label">Quản trị viên</div>
            </div>
            <div class="stat-card" style="border-color: #2ecc71;">
                <i class="fas fa-plug" style="color: #2ecc71;"></i>
                <div class="stat-number"><?php echo $stats['active_controls']; ?>/2</div>
                <div class="stat-label">Bơm đang chạy</div>
            </div>
            <div class="stat-card" style="border-color: #e74c3c;">
                <i class="fas fa-heartbeat" style="color: #e74c3c;"></i>
                <div class="stat-number">Online</div>
                <div class="stat-label">Trạng thái hệ thống</div>
            </div>
        </div>

        <div class="status-container">
            <h3><i class="fas fa-bolt"></i> Giám sát thiết bị thời gian thực</h3>
            <div class="relay-list">
                <div class="relay-box">
                    <h4 style="margin-bottom: 10px;">Bơm số 1</h4>
                    <span class="<?php echo $stats['relay1_status']=='ON'?'badge-on':'badge-off'; ?>">
                        <?php echo $stats['relay1_status']=='ON'?'ĐANG HOẠT ĐỘNG':'ĐANG TẮT'; ?>
                    </span>
                </div>
                <div class="relay-box">
                    <h4 style="margin-bottom: 10px;">Bơm số 2</h4>
                    <span class="<?php echo $stats['relay2_status']=='ON'?'badge-on':'badge-off'; ?>">
                        <?php echo $stats['relay2_status']=='ON'?'ĐANG HOẠT ĐỘNG':'ĐANG TẮT'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // TỰ ĐỘNG KIỂM TRA LỊCH TRÌNH KỂ CẢ KHI Ở DASHBOARD
        setInterval(function(){
            fetch('../check_schedule.php')
            .then(res => res.text())
            .then(data => {
                // Nếu trạng thái thay đổi, có thể reload nhẹ để cập nhật badge ON/OFF
                console.log("Check schedule running...");
            });
        }, 10000); // 10 giây một lần
    </script>
</body>
</html>
<?php $conn->close(); ?>