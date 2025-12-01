<?php
// admin/dashboard.php
session_start();

// --- 1. KIỂM TRA XÁC THỰC ADMIN ---
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php"); 
    exit();
}

// Lấy thông tin Admin
$admin_username = $_SESSION['username'] ?? 'Admin';

// --- 2. LOGIC LẤY DỮ LIỆU CẢM BIẾN VÀ VỊ TRÍ ---

$data_file = '../data.json'; 
$config_file = '../location_config.json'; 

// Hàm đọc dữ liệu cảm biến
function getSensorData($file_path) {
    $default_data = [
        'temperature' => 'N/A',
        'pH' => 'N/A',
        'turbidity' => 'N/A',
        'water_level' => 'N/A', 
        'humidity' => 'N/A',
        'time' => 'N/A'
    ];
    if (file_exists($file_path)) {
        $json_data = file_get_contents($file_path);
        $read_data = json_decode($json_data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($read_data)) {
            return array_merge($default_data, $read_data);
        }
    }
    return $default_data;
}
$sensor_data = getSensorData($data_file);

// Hàm đọc cấu hình vị trí
function readLocationConfig($file_path) {
    $default_config = [
        'pump_1' => [
            'name' => 'Bơm 1 (Lấy mẫu)',
            'target_lat' => '16.0601',
            'target_lng' => '108.2119',
            'current_lat' => '16.0595', 
            'current_lng' => '108.2115',
            'status' => 'Đang chờ lệnh',
            'last_updated' => 'N/A'
        ],
        'pump_2' => [
            'name' => 'Bơm 2 (Đổ hóa chất)',
            'target_lat' => '16.0601',
            'target_lng' => '108.2119',
            'current_lat' => '16.0610', 
            'current_lng' => '108.2125',
            'status' => 'Đang chờ lệnh',
            'last_updated' => 'N/A'
        ]
    ];
    if (file_exists($file_path)) {
        $json_data = file_get_contents($file_path);
        $config = json_decode($json_data, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($config)) {
            return array_replace_recursive($default_config, $config);
        }
    }
    return $default_config;
}
$location_config = readLocationConfig($config_file);

// Lấy vị trí HIỆN TẠI (current) của hai bơm
$p1 = $location_config['pump_1'];
$p2 = $location_config['pump_2'];

$pump1_lat_current = floatval($p1['current_lat']);
$pump1_lng_current = floatval($p1['current_lng']);
$pump2_lat_current = floatval($p2['current_lat']);
$pump2_lng_current = floatval($p2['current_lng']);

// Tính trung tâm map
$center_lat = ($pump1_lat_current + $pump2_lat_current) / 2;
$center_lng = ($pump1_lng_current + $pump2_lng_current) / 2;


// --- 3. LOGIC ĐÁNH GIÁ CHẤT LƯỢNG NƯỚC ---
function evaluateWaterQuality($data) {
    $status = ['level' => 'Tốt', 'temp' => 'Tốt', 'pH' => 'Tốt'];
    $temp = floatval($data['temperature'] ?? 0);
    $ph = floatval($data['pH'] ?? 0);
    $level = floatval(str_replace('%', '', $data['water_level'] ?? 0));
    
    if ($level !== 0 && $data['water_level'] !== 'N/A') {
        if ($level < 10) $status['level'] = 'Nguyhiem';
        else if ($level < 50) $status['level'] = 'Canhbao';
    } else {$status['level'] = 'NA';}
    
    if ($temp !== 0 && $data['temperature'] !== 'N/A') {
        if ($temp < 18 || $temp > 35) $status['temp'] = 'Canhbao';
        if ($temp < 15 || $temp > 40) $status['temp'] = 'Nguyhiem';
        else if ($temp >= 22 && $temp <= 28) $status['temp'] = 'Tốt';
    } else {$status['temp'] = 'NA';}
    
    if ($ph !== 0 && $data['pH'] !== 'N/A') {
        if ($ph < 6.0 || $ph > 9.0) $status['pH'] = 'Canhbao';
        if ($ph < 5.5 || $ph > 9.5) $status['pH'] = 'Nguyhiem';
        else if ($ph >= 6.5 && $ph <= 8.5) $status['pH'] = 'Tốt';
    } else {$status['pH'] = 'NA';}
    return $status;
}

$quality_status = evaluateWaterQuality($sensor_data);

// --- 4. THỐNG KÊ ---
$stats = [
    'total_data_points' => rand(1500, 3000), 
    'alerts_high_ph' => rand(5, 20), 
    'alerts_low_level' => rand(2, 10), 
    'sensor_online' => true,
    'data_feed_status' => 'Ổn định', 
    'system_uptime' => "99.9%"
];

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Giám Sát Chất Lượng Nước</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f6fa; display: flex; }
        
        .primary-color { color: #0a4f46; } 
        .accent-color { background: #4caf50; }
        .sidebar { width: 280px; background: #0a4f46; color: white; height: 100vh; position: fixed; overflow-y: auto; }
        .sidebar-header { padding: 20px; background: #1a73e8; text-align: center; border-bottom: 1px solid #34495e; }
        .sidebar-menu { list-style: none; padding: 0; }
        .menu-section { margin: 15px 0; }
        .menu-section-title { padding: 10px 20px; color: #bdc3c7; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .sidebar-menu li { border-bottom: 1px solid #1a73e8; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 20px; color: #bdc3c7; text-decoration: none; transition: all 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #1a73e8; color: white; border-left: 4px solid #4caf50; }
        .sidebar-menu i { margin-right: 12px; width: 20px; text-align: center; font-size: 1.1rem; }
        .menu-badge { background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; margin-left: auto; }
        
        .main-content { margin-left: 280px; padding: 20px; width: calc(100% - 280px); }
        .header { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header-left h1 { color: #0a4f46; margin-bottom: 5px; }
        .header-left small { color: #7f8c8d; font-size: 0.9rem; }
        .header-right { display: flex; align-items: center; gap: 15px; }
        .web-link { display: flex; align-items: center; gap: 8px; padding: 10px 15px; background: #4caf50; color: white; text-decoration: none; border-radius: 5px; font-weight: 500; transition: all 0.3s; border: none; cursor: pointer; font-size: 0.9rem; }
        .web-link:hover { background: #388e3c; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(76, 175, 80, 0.3); }
        .user-info { display: flex; align-items: center; gap: 10px; }
        .user-avatar { width: 35px; height: 35px; border-radius: 50%; background: #0a4f46; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; transition: transform 0.3s; }
        .stat-card i { font-size: 2.5rem; margin-bottom: 15px; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }
        .stat-label { color: #7f8c8d; font-size: 0.9rem; }
        
        .stat-card.total-data { border-left: 4px solid #1abc9c; color: #1abc9c; } 
        .stat-card.high-ph { border-left: 4px solid #f39c12; color: #f39c12; } 
        .stat-card.low-level { border-left: 4px solid #e74c3c; color: #e74c3c; } 
        .stat-card.online { border-left: 4px solid #27ae60; color: #27ae60; } 

        .section-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .section-card h2 { margin-bottom: 1rem; color: #0a4f46; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        .sensor-data-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 15px; }
        .sensor-data-item { padding: 20px; border-radius: 8px; background: #f8f9fa; border: 1px solid #eee; }
        .sensor-data-item h4 { margin-bottom: 8px; font-size: 1.1rem; color: #2c3e50; }
        .sensor-value { font-size: 2rem; font-weight: bold; }

        .quality-indicator { display: inline-block; padding: 4px 10px; border-radius: 15px; font-weight: 600; font-size: 0.8rem; }
        .quality-indicator.Tốt { background: #e6f4ea; color: #0d652d; border: 1px solid #34a853; }
        .quality-indicator.Canhbao { background: #fef7e0; color: #b06c00; border: 1px solid #f9ab00; }
        .quality-indicator.Nguyhiem { background: #fce8e6; color: #c5221f; border: 1px solid #ea4335; }
        .quality-indicator.NA { background: #e8eaed; color: #5f6368; border: 1px solid #bdc3c7; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-muted { color: #7f8c8d; }
        
        /* CSS cho Map Leaflet */
        #map-display { 
            height: 400px; 
            border-radius: 8px; 
            overflow: hidden; 
            margin-bottom: 15px; 
        }
        .popup-title { font-weight: bold; font-size: 14px; margin-bottom: 6px; }
        .status-moving { color: #28a745; font-weight: bold; }
        .status-waiting { color: #ffc107; font-weight: bold; }
        .status-unknown { color: #6c757d; font-weight: bold; }

        @media (max-width: 768px) {
            body { display: block; }
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; width: 100%; padding: 10px; }
            .header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .header-right { margin-top: 10px; width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-water"></i> Admin Panel</h2>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-section-title">Tổng quan</li>
            <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="set_location.php"><i class="fas fa-map-marker-alt"></i> Vị trí Lấy mẫu</a></li>
            <li><a href="#"><i class="fas fa-bell"></i> Cảnh báo <span class="menu-badge"><?php echo $stats['alerts_low_level'] + $stats['alerts_high_ph']; ?></span></a></li>
            
            <li class="menu-section-title">Quản lý Dữ liệu</li>
            <li><a href="#"><i class="fas fa-database"></i> Lịch sử Sensor</a></li>
            <li><a href="#"><i class="fas fa-chart-area"></i> Biểu đồ (Sắp có)</a></li>
            
            <li class="menu-section-title">Hệ thống</li>
            <li><a href="../index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <header class="header">
            <div class="header-left">
                <h1>Chào mừng, <?php echo htmlspecialchars($admin_username); ?>!</h1>
                <small>Dashboard giám sát hệ thống chất lượng nước.</small>
            </div>
            <div class="header-right">
                <a href="../index.php" class="web-link"><i class="fas fa-eye"></i> Xem Trang Chủ</a>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($admin_username, 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($admin_username); ?></span>
                </div>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card total-data">
                <i class="fas fa-cubes"></i>
                <div class="stat-number"><?php echo number_format($stats['total_data_points']); ?></div>
                <div class="stat-label">Tổng số điểm dữ liệu</div>
            </div>
            <div class="stat-card high-ph">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="stat-number"><?php echo $stats['alerts_high_ph']; ?></div>
                <div class="stat-label">Cảnh báo pH Cao/Thấp</div>
            </div>
            <div class="stat-card low-level">
                <i class="fas fa-tint-slash"></i>
                <div class="stat-number"><?php echo $stats['alerts_low_level']; ?></div>
                <div class="stat-label">Cảnh báo Mực nước thấp</div>
            </div>
            <div class="stat-card online">
                <i class="fas fa-wifi"></i>
                <div class="stat-number"><?php echo $stats['sensor_online'] ? 'ONLINE' : 'OFFLINE'; ?></div>
                <div class="stat-label">Trạng thái Sensor</div>
            </div>
        </div>
        
        <div class="section-card">
            <h2><i class="fas fa-map-marker-alt"></i> Giám sát Vị trí Hiện tại (Sông Hàn)</h2>
            
            <div id="map-display"></div> <div style="display: flex; justify-content: space-between; gap: 20px; margin-bottom: 15px;">
                <div style="flex: 1; border: 1px solid #1a73e8; padding: 15px; border-radius: 8px;">
                    <h4 style="color: #1a73e8; margin-bottom: 10px;"><i class="fas fa-ship"></i> <?php echo $location_config['pump_1']['name']; ?></h4>
                    <p>🎯 **Mục tiêu:** <?php echo $p1['target_lat']; ?>, <?php echo $p1['target_lng']; ?></p>
                    <p>📍 **Hiện tại:** <strong style="color: #0a4f46;"><?php echo $p1['current_lat']; ?>, <?php echo $p1['current_lng']; ?></strong></p>
                    <p>⚙️ **Trạng thái:** <span style="font-weight: bold; color: #34a853;"><?php echo $p1['status']; ?></span></p>
                    <p class="text-muted" style="font-size: 0.8rem; margin-top: 5px;">Cập nhật: <?php echo $p1['last_updated']; ?></p>
                </div>
                <div style="flex: 1; border: 1px solid #f39c12; padding: 15px; border-radius: 8px;">
                    <h4 style="color: #f39c12; margin-bottom: 10px;"><i class="fas fa-microchip"></i> <?php echo $location_config['pump_2']['name']; ?></h4>
                    <p>🎯 **Mục tiêu:** <?php echo $p2['target_lat']; ?>, <?php echo $p2['target_lng']; ?></p>
                    <p>📍 **Hiện tại:** <strong style="color: #0a4f46;"><?php echo $p2['current_lat']; ?>, <?php echo $p2['current_lng']; ?></strong></p>
                    <p>⚙️ **Trạng thái:** <span style="font-weight: bold; color: #f39c12;"><?php echo $p2['status']; ?></span></p>
                    <p class="text-muted" style="font-size: 0.8rem; margin-top: 5px;">Cập nhật: <?php echo $p2['last_updated']; ?></p>
                </div>
            </div>
            
            <div class="text-center" style="margin-top: 15px;">
                <a href="set_location.php" 
                   class="web-link" style="background: #1a73e8; padding: 10px 30px;">
                    <i class="fas fa-compass"></i> Thiết lập Tọa độ Mục tiêu cho 2 Bơm
                </a>
            </div>
        </div>

        <div class="section-card">
            <h2><i class="fas fa-tachometer-alt"></i> Dữ liệu cảm biến mới nhất (<?php echo $sensor_data['time']; ?>)</h2>
            <div class="sensor-data-grid">
                
                <div class="sensor-data-item">
                    <h4>Mực nước (%)</h4>
                    <p class="sensor-value"><?php echo $sensor_data['water_level']; ?></p>
                    <p>Đánh giá: <span class="quality-indicator <?php echo $quality_status['level']; ?>">
                        <?php echo $quality_status['level']; ?>
                    </span></p>
                </div>
                
                <div class="sensor-data-item">
                    <h4>Nhiệt độ (°C)</h4>
                    <p class="sensor-value"><?php echo $sensor_data['temperature']; ?></p>
                    <p>Đánh giá: <span class="quality-indicator <?php echo $quality_status['temp']; ?>">
                        <?php echo $quality_status['temp']; ?>
                    </span></p>
                </div>

                <div class="sensor-data-item">
                    <h4>Độ ẩm (%)</h4>
                    <p class="sensor-value"><?php echo $sensor_data['humidity']; ?></p>
                    <p>Đánh giá: <span class="quality-indicator NA">N/A</span></p>
                </div>
                
                <div class="sensor-data-item">
                    <h4>Độ pH</h4>
                    <p class="sensor-value"><?php echo $sensor_data['pH']; ?></p>
                    <p>Đánh giá: <span class="quality-indicator <?php echo $quality_status['pH']; ?>">
                        <?php echo $quality_status['pH']; ?>
                    </span></p>
                </div>

                <div class="sensor-data-item">
                    <h4>Độ đục (NTU)</h4>
                    <p class="sensor-value"><?php echo $sensor_data['turbidity']; ?></p>
                    <p>Đánh giá: <span class="quality-indicator NA">N/A</span></p>
                </div>
            </div>
        </div>

        <div class="section-card">
            <h2><i class="fas fa-chart-bar"></i> Biểu đồ Hiệu suất (Placeholder)</h2>
            <p class="text-muted text-center" style="padding: 30px;">
                Phần này sẽ hiển thị biểu đồ lịch sử 24h của pH, Nhiệt độ và Mực nước (cần truy vấn Database).
            </p>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <script>
        // Dữ liệu vị trí HIỆN TẠI (current) từ PHP
        let pump1 = {
            lat: <?= $pump1_lat_current ?>,
            lng: <?= $pump1_lng_current ?>,
            status: "<?= $p1['status'] ?>",
            name: "<?= $p1['name'] ?>",
            updated: "<?= $p1['last_updated'] ?>"
        };

        let pump2 = {
            lat: <?= $pump2_lat_current ?>,
            lng: <?= $pump2_lng_current ?>,
            status: "<?= $p2['status'] ?>",
            name: "<?= $p2['name'] ?>",
            updated: "<?= $p2['last_updated'] ?>"
        };

        let center = {
            lat: <?= $center_lat ?>,
            lng: <?= $center_lng ?>
        };

        // Hàm CSS theo trạng thái
        function getStatusClass(status) {
            status = status.toLowerCase();
            if (status.includes("di chuyển") || status.includes("hoạt động")) return "status-moving";
            if (status.includes("chờ") || status.includes("wait")) return "status-waiting";
            return "status-unknown";
        }

        // TẠO MAP
        var map = L.map('map-display').setView([center.lat, center.lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        // ICONs
        var icon1 = L.icon({
            iconUrl: "https://cdn-icons-png.flaticon.com/512/684/684908.png", // Xanh (Bơm 1)
            iconSize: [35, 35],
            iconAnchor: [17, 34]
        });

        var icon2 = L.icon({
            iconUrl: "https://cdn-icons-png.flaticon.com/512/149/149060.png", // Cam (Bơm 2)
            iconSize: [35, 35],
            iconAnchor: [17, 34]
        });

        // Marker 1 (Vị trí hiện tại)
        let m1 = L.marker([pump1.lat, pump1.lng], {icon: icon1}).addTo(map);
        m1.bindPopup(`
            <div>
                <div class="popup-title">🚢 ${pump1.name}</div>
                Trạng thái: <span class="${getStatusClass(pump1.status)}">${pump1.status}</span><br>
                Lat: ${pump1.lat}<br>
                Lng: ${pump1.lng}<br>
                Cập nhật: ${pump1.updated}
            </div>
        `);

        // Marker 2 (Vị trí hiện tại)
        let m2 = L.marker([pump2.lat, pump2.lng], {icon: icon2}).addTo(map);
        m2.bindPopup(`
            <div>
                <div class="popup-title">⚙️ ${pump2.name}</div>
                Trạng thái: <span class="${getStatusClass(pump2.status)}">${pump2.status}</span><br>
                Lat: ${pump2.lat}<br>
                Lng: ${pump2.lng}<br>
                Cập nhật: ${pump2.updated}
            </div>
        `);
        
        // Auto open popup cả 2 (Hiển thị thông tin ngay)
        setTimeout(() => { m1.openPopup(); }, 800);
        setTimeout(() => { m2.openPopup(); }, 1800);
        
        // Đảm bảo map được làm mới sau khi load xong layout
        setTimeout(() => { map.invalidateSize(); }, 200);

    </script>

</body>
</html>