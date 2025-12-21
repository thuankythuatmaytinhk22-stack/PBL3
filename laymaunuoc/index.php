<?php
session_start();
include 'database.php';

// Lấy thời gian hiện tại
date_default_timezone_set('Asia/Ho_Chi_Minh');
$current_time = date('H:i:s');
$current_date = date('d/m/Y');

/** * ĐỒNG BỘ VỚI LOGS.PHP: 
 * Lấy 10 nhật ký mới nhất, JOIN với pump_locations để lấy tên vị trí thực tế
 */
$logs = [];
try {
    $sql = "SELECT l.*, 
                   p.location_name as pump_location_name,
                   p.current_lat as location_lat,
                   p.current_lng as location_lng
            FROM pump_logs l
            LEFT JOIN pump_locations p ON l.pump_number = p.pump_number
            ORDER BY l.created_at DESC 
            LIMIT 10";
            
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Nếu không có tên từ bảng locations, dùng tạm từ logs (nếu có)
            if (empty($row['pump_location_name']) && !empty($row['location_name'])) {
                $row['pump_location_name'] = $row['location_name'];
            }
            // Ưu tiên tọa độ từ bảng locations
            if (empty($row['location_lat']) && !empty($row['location_lat_from_logs'])) {
                $row['location_lat'] = $row['location_lat_from_logs'];
                $row['location_lng'] = $row['location_lng_from_logs'];
            }
            $logs[] = $row;
        }
    }
} catch (Exception $e) {
    // Xử lý lỗi nếu cần
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Lấy Mẫu Nước - Trang Chủ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; color: #333; }
        
        /* HEADER SECTION */
        .header { background: #ffffff; box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1); position: relative; z-index: 1000; }
        .header-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eaeaea; }
        .logo { display: flex; align-items: center; gap: 15px; }
        .logo-icon { width: 50px; height: 50px; background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.8rem; }
        .logo-text h1 { color: #2c3e50; font-size: 1.8rem; margin-bottom: 5px; font-weight: 700; }
        .time-info { display: flex; flex-direction: column; align-items: flex-end; }
        .current-time { font-size: 1.8rem; font-weight: 700; color: #2c3e50; font-family: 'Courier New', monospace; }
        
        .header-main { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }
        .nav-menu { display: flex; list-style: none; gap: 5px; background: #f8f9fa; border-radius: 10px; padding: 5px; }
        .nav-menu a { display: flex; align-items: center; gap: 10px; color: #2c3e50; text-decoration: none; font-weight: 600; padding: 12px 25px; border-radius: 8px; transition: 0.3s; }
        .nav-menu a.active { background: #3498db; color: white; }
        .nav-menu a:hover:not(.active) { background: #e3f2fd; }

        .auth-section { display: flex; align-items: center; gap: 12px; }
        .auth-btn { padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .login-btn { background: #3498db; color: white; border: 1px solid #3498db; }
        .register-btn { background: white; color: #3498db; border: 2px solid #3498db; }

        .admin-link { background: #27ae60; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; }

        /* MAIN CONTENT */
        .main-content { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .section-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .section-card h2 { margin-bottom: 20px; color: #2c3e50; display: flex; align-items: center; gap: 12px; }

        /* MAP */
        .map-wrapper { position: relative; height: 450px; border-radius: 12px; overflow: hidden; border: 2px solid #edf2f7; }
        .map-lock-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 5; background: rgba(0,0,0,0); }

        /* ĐỒNG BỘ BADGE TỪ LOGS.PHP */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { background: #f8f9fa; color: #7f8c8d; padding: 15px; text-align: left; font-size: 0.85rem; border-bottom: 2px solid #eee; }
        .data-table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; display: inline-block; }
        .badge-pump1 { background: #e3f2fd; color: #1976d2; }
        .badge-pump2 { background: #f3e5f5; color: #7b1fa2; }
        .badge-on { background: #d4edda; color: #155724; }
        .badge-off { background: #f8d7da; color: #721c24; }
        
        .coord-link { color: #3498db; text-decoration: none; font-family: monospace; font-size: 0.9rem; }
        .coord-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-container">
            <div class="header-top">
                <div class="logo">
                    <div class="logo-icon"><i class="fas fa-tint"></i></div>
                    <div class="logo-text">
                        <h1>Smart Water</h1>
                        <p style="font-size: 0.8rem; color: #7f8c8d;">Giám sát lấy mẫu tự động</p>
                    </div>
                </div>
                <div class="time-info">
                    <div class="current-time" id="current-time"><?php echo $current_time; ?></div>
                    <div class="current-date" id="current-date"><?php echo $current_date; ?></div>
                </div>
            </div>
            
            <div class="header-main">
                <nav>
                    <ul class="nav-menu">
                        <li><a href="index.php" class="active"><i class="fas fa-home"></i> Trang chủ</a></li>
                        <li><a href="gioi-thieu.php"><i class="fas fa-info-circle"></i> Giới thiệu</a></li>
                        <li><a href="lien-he.php"><i class="fas fa-envelope"></i> Liên hệ</a></li>
                    </ul>
                </nav>
                
                <div class="auth-section">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="user-info">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="admin/dashboard.php" class="admin-link"><i class="fas fa-user-shield"></i> Quản trị</a>
                        <?php endif; ?>
                        <a href="logout.php" style="color: #e74c3c; text-decoration: none; font-weight: 600; margin-left: 10px;"><i class="fas fa-sign-out-alt"></i> Thoát</a>
                    <?php else: ?>
                        <a href="login.php" class="auth-btn login-btn"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
                        <a href="register.php" class="auth-btn register-btn"><i class="fas fa-user-plus"></i> Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="section-card">
            <h2><i class="fas fa-map-marked-alt" style="color: #3498db;"></i> Vị trí trạm lấy mẫu thực tế</h2>
            <div class="map-wrapper">
                <div class="map-lock-overlay"></div>
                <iframe src="admin/map_control.php" width="100%" height="100%"></iframe>
            </div>
            <p style="margin-top: 15px; color: #95a5a6; font-size: 0.85rem; text-align: center;">
                <i class="fas fa-info-circle"></i> Vị trí được cập nhật theo thời gian thực từ GPS.
            </p>
        </div>

        <div class="section-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fas fa-history" style="color: #2ecc71;"></i> Nhật ký lấy mẫu mới nhất</h2>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin/logs.php" style="color: #3498db; text-decoration: none; font-size: 0.9rem; font-weight: 600;">Xem tất cả nhật ký <i class="fas fa-arrow-right"></i></a>
                <?php endif; ?>
            </div>
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Bơm</th>
                            <th>Hành động</th>
                            <th>Vị trí lấy mẫu</th>
                            <th>Tọa độ GPS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $row): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('H:i:s', strtotime($row['created_at'])); ?></strong><br>
                                        <small style="color: #888;"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($row['pump_number'] == 1): ?>
                                            <span class="badge badge-pump1">Bơm 1</span>
                                        <?php elseif ($row['pump_number'] == 2): ?>
                                            <span class="badge badge-pump2">Bơm 2</span>
                                        <?php else: ?>
                                            <span class="badge badge-pump1">Cả 2 bơm</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['action'] == 'ON'): ?>
                                            <span class="badge badge-on"><i class="fas fa-play"></i> BẬT</span>
                                        <?php else: ?>
                                            <span class="badge badge-off"><i class="fas fa-stop"></i> TẮT</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-map-marker-alt" style="color: #e74c3c;"></i> 
                                        <strong><?php echo htmlspecialchars($row['pump_location_name'] ?? 'Chưa xác định'); ?></strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['location_lat'])): ?>
                                            <a href="https://www.google.com/maps?q=<?php echo $row['location_lat']; ?>,<?php echo $row['location_lng']; ?>" target="_blank" class="coord-link">
                                                <i class="fas fa-external-link-alt"></i> <?php echo round($row['location_lat'], 5) . ', ' . round($row['location_lng'], 5); ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #ccc;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; color: #bdc3c7; padding: 40px;">Chưa có dữ liệu nhật ký mới.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function updateClock() {
            const now = new Date();
            const timeStr = now.getHours().toString().padStart(2, '0') + ':' + 
                          now.getMinutes().toString().padStart(2, '0') + ':' + 
                          now.getSeconds().toString().padStart(2, '0');
            document.getElementById('current-time').textContent = timeStr;
        }
        setInterval(updateClock, 1000);
    </script>
</body>
</html>