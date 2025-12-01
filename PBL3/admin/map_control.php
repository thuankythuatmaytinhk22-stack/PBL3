<?php
// admin/map_control.php - Dùng Leaflet cho map điều khiển.

// Đường dẫn đến file cấu hình (Cần ../ vì file này nằm trong thư mục admin)
$config_file = '../location_config.json';
$current_config = [];

// Thiết lập tọa độ mặc định (Sông Hàn, Đà Nẵng)
$default_lat = 16.0601;
$default_lng = 108.2119;

// Đọc dữ liệu cấu hình
if (file_exists($config_file)) {
    $json_data = file_get_contents($config_file);
    $config = json_decode($json_data, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($config)) {
        $current_config = $config;
    }
}

// Lấy tọa độ MỤC TIÊU (target) hiện tại từ JSON
// Tọa độ này sẽ dùng để khởi tạo marker
$pump1_lat_target = floatval($current_config['pump_1']['target_lat'] ?? $default_lat);
$pump1_lng_target = floatval($current_config['pump_1']['target_lng'] ?? $default_lng);
$pump2_lat_target = floatval($current_config['pump_2']['target_lat'] ?? $default_lat);
$pump2_lng_target = floatval($current_config['pump_2']['target_lng'] ?? $default_lng);

// Tính toán vị trí trung tâm để map có thể hiển thị cả hai marker
$center_lat = ($pump1_lat_target + $pump2_lat_target) / 2;
$center_lng = ($pump1_lng_target + $pump2_lng_target) / 2;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaflet Map Control</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <style>
        /* FIX LỖI KHÔNG HIỂN THỊ MAP: Đặt chiều cao 100% cho html và body */
        html, body { 
            height: 100%; 
            margin: 0; 
            padding: 0; 
        }
        
        #map {
            width: 100%;
            /* Thay đổi từ 500px sang 100% để fill đúng kích thước của iframe */
            height: 100%; 
        }
    </style>
</head>

<body>
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    // Dữ liệu Tọa độ MỤC TIÊU được lấy từ PHP/JSON
    var bom1Lat = <?= $pump1_lat_target ?>;
    var bom1Lng = <?= $pump1_lng_target ?>;

    var bom2Lat = <?= $pump2_lat_target ?>;
    var bom2Lng = <?= $pump2_lng_target ?>;

    var centerLat = <?= $center_lat ?>;
    var centerLng = <?= $center_lng ?>;

    // Khởi tạo bản đồ
    // Sử dụng tọa độ trung tâm đã tính toán
    var map = L.map('map').setView([centerLat, centerLng], 15); 

    // Thêm tile layer (OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Định nghĩa Icons
    var blueIcon = L.icon({
        iconUrl: "https://cdn-icons-png.flaticon.com/512/684/684908.png",
        iconSize: [35, 35],
        iconAnchor: [17, 34]
    });

    var orangeIcon = L.icon({
        iconUrl: "https://cdn-icons-png.flaticon.com/512/149/149060.png",
        iconSize: [35, 35],
        iconAnchor: [17, 34]
    });

    // Marker bơm 1 (Dùng tọa độ từ PHP)
    var markerBom1 = L.marker([bom1Lat, bom1Lng], {
        icon: blueIcon,
        draggable: true
    }).addTo(map);
    markerBom1.bindPopup("Marker Bom 1 (Lấy mẫu)");

    // Marker bơm 2 (Dùng tọa độ từ PHP)
    var markerBom2 = L.marker([bom2Lat, bom2Lng], {
        icon: orangeIcon,
        draggable: true
    }).addTo(map);
    markerBom2.bindPopup("Marker Bom 2 (Đổ hóa chất)");

    // Hàm gửi dữ liệu lên parent (set_location.php)
    function sendToParent(b1_lat, b1_lng, b2_lat, b2_lng) {
        window.parent.postMessage({
            type: "updateLocation",
            bom1_lat: b1_lat,
            bom1_lng: b1_lng,
            bom2_lat: b2_lat,
            bom2_lng: b2_lng
        }, "*");
    }

    // 💡 Gửi tọa độ ban đầu lên form ngay khi map load xong để đồng bộ form và marker
    sendToParent(
        bom1Lat.toFixed(6),
        bom1Lng.toFixed(6),
        bom2Lat.toFixed(6),
        bom2Lng.toFixed(6)
    );

    // Sự kiện kéo marker 1
    markerBom1.on("dragend", function () {
        var pos = markerBom1.getLatLng();

        sendToParent(
            pos.lat.toFixed(6),
            pos.lng.toFixed(6),
            markerBom2.getLatLng().lat.toFixed(6),
            markerBom2.getLatLng().lng.toFixed(6)
        );
    });

    // Sự kiện kéo marker 2
    markerBom2.on("dragend", function () {
        var pos = markerBom2.getLatLng();

        sendToParent(
            markerBom1.getLatLng().lat.toFixed(6),
            markerBom1.getLatLng().lng.toFixed(6),
            pos.lat.toFixed(6),
            pos.lng.toFixed(6)
        );
    });
</script>

</body>
</html>