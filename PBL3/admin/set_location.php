<?php
// admin/set_location.php
session_start();

// Kiểm tra xác thực Admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../login.php"); 
    exit();
}

$config_file = '../location_config.json';
$message = '';
$message_class = '';

// Hàm đọc cấu hình hiện tại
function readConfig($file) {
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

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return array_replace_recursive($default_config, $data);
        }
    }
    return $default_config;
}


// Xử lý form gửi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_config = readConfig($config_file);

    $lat1 = filter_input(INPUT_POST, 'lat1', FILTER_VALIDATE_FLOAT);
    $lng1 = filter_input(INPUT_POST, 'lng1', FILTER_VALIDATE_FLOAT);
    $lat2 = filter_input(INPUT_POST, 'lat2', FILTER_VALIDATE_FLOAT);
    $lng2 = filter_input(INPUT_POST, 'lng2', FILTER_VALIDATE_FLOAT);

    if ($lat1 !== false && $lng1 !== false && $lat2 !== false && $lng2 !== false) {
        
        $new_config = [
            'pump_1' => [
                'name' => 'Bơm 1 (Lấy mẫu)',
                'target_lat' => (string)$lat1,
                'target_lng' => (string)$lng1,
                'current_lat' => $current_config['pump_1']['current_lat'],
                'current_lng' => $current_config['pump_1']['current_lng'],
                'status' => 'Đang di chuyển...',
                'last_updated' => date("Y-m-d H:i:s")
            ],
            'pump_2' => [
                'name' => 'Bơm 2 (Đổ hóa chất)',
                'target_lat' => (string)$lat2,
                'target_lng' => (string)$lng2,
                'current_lat' => $current_config['pump_2']['current_lat'],
                'current_lng' => $current_config['pump_2']['current_lng'],
                'status' => 'Đang di chuyển...',
                'last_updated' => date("Y-m-d H:i:s")
            ]
        ];
        
        if (file_put_contents($config_file, json_encode($new_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            $message = "✅ Lệnh tọa độ mục tiêu mới đã được gửi thành công!";
            $message_class = 'success';
        } else {
            $message = "❌ Không thể ghi vào file cấu hình. Kiểm tra quyền CHMOD 777 cho file `location_config.json`.";
            $message_class = 'error';
        }

    } else {
        $message = "❌ Lỗi: Tọa độ không hợp lệ!";
        $message_class = 'error';
    }
}

$current_config = readConfig($config_file);
$pump1 = $current_config['pump_1'];
$pump2 = $current_config['pump_2'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thiết lập vị trí bơm</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f6fa; display: flex; }

        .sidebar {
            width: 280px; background: #0a4f46; color: white; height: 100vh; position: fixed;
        }
        .sidebar-header { padding: 20px; background: #1a73e8; text-align: center; }
        .sidebar-menu { list-style: none; padding: 0; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 20px; color: #bdc3c7; text-decoration: none; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #1a73e8; color: white; border-left: 4px solid #4caf50; }

        .main-content {
            margin-left: 280px; padding: 20px; width: calc(100% - 280px);
        }

        .section-card {
            background: white; padding: 25px; border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;
        }

        #map-container { height: 400px; border-radius: 8px; overflow: hidden; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: bold; margin-bottom: 5px; display: block; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        button { margin-top: 20px; padding: 10px 20px; background: #4caf50; color: white; border: none; border-radius: 6px; cursor: pointer; }

        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; }
        .message.success { background: #e6f4ea; color: #0d652d; border: 1px solid #34a853; }
        .message.error { background: #fdecea; color: #c62828; border: 1px solid #e53935; }
    </style>

    <script>
    // ----------- NHẬN TỌA ĐỘ TỪ IFRAME MAP -------------
    window.addEventListener("message", function(event) {
        if (event.data.type === "updateLocation") {
            document.getElementById("lat1").value = event.data.bom1_lat;
            document.getElementById("lng1").value = event.data.bom1_lng;
            document.getElementById("lat2").value = event.data.bom2_lat;
            document.getElementById("lng2").value = event.data.bom2_lng;
        }
    });
    </script>
</head>

<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header"><h2>Admin Panel</h2></div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a class="active" href="set_location.php"><i class="fas fa-map-marker-alt"></i> Vị trí Lấy mẫu</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <h1>Thiết lập Tọa độ cho 2 Bơm</h1>
        <p>Sử dụng bản đồ để kéo thả marker hoặc chỉnh trực tiếp tọa độ.</p>
        <hr><br>

        <?php if ($message): ?>
        <div class="message <?= $message_class ?>"><?= $message ?></div>
        <?php endif; ?>

        <div class="section-card">
            <h2>Bản đồ tương tác</h2>

            <div id="map-container">
                <iframe src="map_control.php" width="100%" height="100%" frameborder="0"></iframe>
            </div>

            <form method="POST" action="set_location.php">
                <div class="form-grid">

                    <!-- Pump 1 -->
                    <div style="border: 1px solid #1a73e8; padding: 20px; border-radius: 10px;">
                        <h3 style="color:#1a73e8;">Bơm 1 (Lấy mẫu)</h3>

                        <div class="form-group">
                            <label>Latitude:</label>
                            <input id="lat1" name="lat1" value="<?= $pump1['target_lat'] ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Longitude:</label>
                            <input id="lng1" name="lng1" value="<?= $pump1['target_lng'] ?>" required>
                        </div>
                    </div>

                    <!-- Pump 2 -->
                    <div style="border: 1px solid #f39c12; padding: 20px; border-radius: 10px;">
                        <h3 style="color:#f39c12;">Bơm 2 (Đổ hóa chất)</h3>

                        <div class="form-group">
                            <label>Latitude:</label>
                            <input id="lat2" name="lat2" value="<?= $pump2['target_lat'] ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Longitude:</label>
                            <input id="lng2" name="lng2" value="<?= $pump2['target_lng'] ?>" required>
                        </div>
                    </div>
                </div>

                <div style="text-align:center;">
                    <button type="submit"><i class="fas fa-save"></i> Gửi Tọa độ</button>
                </div>
            </form>

        </div>
    </div>

</body>
</html>
