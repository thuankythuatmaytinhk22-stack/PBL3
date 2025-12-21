<?php
// admin/set_location.php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
include '../database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Lấy thống kê cho sidebar (Đồng bộ với dashboard)
$stats = [];
try {
    $result = $conn->query("SELECT COUNT(*) as total FROM users");
    $stats['total_users'] = $result ? $result->fetch_assoc()['total'] : 0;
} catch (Exception $e) {
    $stats['total_users'] = 0;
}

// Xử lý khi nhấn nút Lưu thủ công
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_coords'])) {
    $lat1 = $_POST['lat1']; $lng1 = $_POST['lng1'];
    $lat2 = $_POST['lat2']; $lng2 = $_POST['lng2'];

    $conn->query("UPDATE pump_locations SET current_lat='$lat1', current_lng='$lng1', updated_at=NOW() WHERE pump_number=1");
    $conn->query("UPDATE pump_locations SET current_lat='$lat2', current_lng='$lng2', updated_at=NOW() WHERE pump_number=2");
    $message = "✅ Đã lưu vị trí thành công!";
}

// Lấy dữ liệu bơm để hiển thị
$pumps = $conn->query("SELECT * FROM pump_locations ORDER BY pump_number");
$p_data = [];
while ($row = $pumps->fetch_assoc()) { 
    $p_data[$row['pump_number']] = $row; 
}

$pump1 = $p_data[1];
$pump2 = $p_data[2];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vị trí lấy mẫu - Hệ thống quản trị</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS GỐC TỪ DASHBOARD.PHP */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f6fa; display: flex; min-height: 100vh; }

        /* SIDEBAR ĐỒNG BỘ */
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
        
        .section-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        #map-container { height: 450px; border-radius: 10px; overflow: hidden; border: 1px solid #eee; margin-bottom: 25px; background: #eee; }
        
        /* FORM LAYOUT */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .pump-box { padding: 20px; border-radius: 10px; background: #f8f9fa; border: 1px solid #eee; transition: 0.3s; }
        .pump-box:hover { border-color: #3498db; }
        .pump-box h3 { margin-bottom: 15px; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; }
        
        label { display: block; font-size: 0.85rem; color: #7f8c8d; margin-bottom: 5px; }
        input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; font-family: monospace; }
        
        .btn-save { background: #2ecc71; color: white; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-save:hover { background: #27ae60; transform: translateY(-2px); }
        
        .auto-save-notice { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: none; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .btn-web { background: #3498db; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 0.9rem; transition: 0.3s; }
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
            <li><a href="control.php"><i class="fas fa-toggle-on"></i> Điều khiển & Lịch</a></li>
            <li><a href="set_location.php" class="active"><i class="fas fa-map-marker-alt"></i> Vị trí lấy mẫu</a></li>
            <li><a href="logs.php"><i class="fas fa-history"></i> Nhật ký hệ thống</a></li>
            
            <div class="menu-section-title">Hệ thống</div>
            <li><a href="users.php"><i class="fas fa-users"></i> Quản lý Users <span class="menu-badge"><?php echo $stats['total_users']; ?></span></a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header-box">
            <div>
                <h1>Cấu hình Vị trí Thực tế</h1>
                <p style="color: #7f8c8d;">Cập nhật tọa độ GPS cho các trạm lấy mẫu</p>
            </div>
            <a href="../index.php" class="btn-web"><i class="fas fa-external-link-alt"></i> Xem Bản Đồ</a>
        </div>

        <?php if(isset($message)): ?>
            <div class="auto-save-notice" style="display: block;"><?php echo $message; ?></div>
        <?php endif; ?>

        <div id="autoNotice" class="auto-save-notice">
            <i class="fas fa-sync-alt fa-spin"></i> Hệ thống đã tự động đồng bộ tọa độ mới từ bản đồ!
        </div>

        <div class="section-card">
            <div id="map-container">
                <iframe src="map_control.php" width="100%" height="100%" frameborder="0"></iframe>
            </div>

            <form method="POST">
                <div class="form-grid">
                    <div class="pump-box">
                        <h3 style="color:#3498db"><i class="fas fa-location-dot"></i> <?= htmlspecialchars($pump1['location_name']) ?></h3>
                        <label>Vĩ độ (Latitude):</label>
                        <input type="text" id="lat1" name="lat1" value="<?= $pump1['current_lat'] ?>" readonly>
                        <label>Kinh độ (Longitude):</label>
                        <input type="text" id="lng1" name="lng1" value="<?= $pump1['current_lng'] ?>" readonly>
                    </div>

                    <div class="pump-box">
                        <h3 style="color:#9b59b6"><i class="fas fa-location-dot"></i> <?= htmlspecialchars($pump2['location_name']) ?></h3>
                        <label>Vĩ độ (Latitude):</label>
                        <input type="text" id="lat2" name="lat2" value="<?= $pump2['current_lat'] ?>" readonly>
                        <label>Kinh độ (Longitude):</label>
                        <input type="text" id="lng2" name="lng2" value="<?= $pump2['current_lng'] ?>" readonly>
                    </div>
                </div>
                
                <div style="text-align:center; margin-top: 30px;">
                    <button type="submit" name="submit_coords" class="btn-save">
                        <i class="fas fa-save"></i> XÁC NHẬN LƯU TỌA ĐỘ
                    </button>
                    <p style="font-size: 0.8rem; color: #95a5a6; margin-top: 10px;">
                        * Tọa độ sẽ tự động điền khi bạn kéo thả marker trên bản đồ phía trên.
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Lắng nghe sự kiện từ iframe bản đồ (map_control.php)
        window.addEventListener('message', function(event) {
            if (event.data.type === 'updateLocation') {
                document.getElementById('lat1').value = event.data.bom1_lat;
                document.getElementById('lng1').value = event.data.bom1_lng;
                document.getElementById('lat2').value = event.data.bom2_lat;
                document.getElementById('lng2').value = event.data.bom2_lng;
                
                // Hiển thị thông báo khi có sự thay đổi
                let notice = document.getElementById('autoNotice');
                notice.style.display = 'block';
                setTimeout(() => { notice.style.display = 'none'; }, 4000);
            }
        });
    </script>
</body>
</html>