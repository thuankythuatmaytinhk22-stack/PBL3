<?php
// admin/map_display.php

$config_file = '../location_config.json';
$current_config = [];

if (file_exists($config_file)) {
    $json_data = file_get_contents($config_file);
    $current_config = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $current_config = [];
    }
}

// Lấy dữ liệu pump 1
$p1 = $current_config['pump_1'] ?? [];
$pump1_lat = $p1['current_lat'] ?? 16.0595;
$pump1_lng = $p1['current_lng'] ?? 108.2115;
$pump1_status = $p1['status'] ?? "Không rõ";
$pump1_name = $p1['name'] ?? "Bơm 1 (Lấy mẫu)";
$pump1_update = $p1['last_updated'] ?? "N/A";

// Lấy dữ liệu pump 2
$p2 = $current_config['pump_2'] ?? [];
$pump2_lat = $p2['current_lat'] ?? 16.0610;
$pump2_lng = $p2['current_lng'] ?? 108.2125;
$pump2_status = $p2['status'] ?? "Không rõ";
$pump2_name = $p2['name'] ?? "Bơm 2 (Đổ hóa chất)";
$pump2_update = $p2['last_updated'] ?? "N/A";

// Tính center
$center_lat = ($pump1_lat + $pump2_lat) / 2;
$center_lng = ($pump1_lng + $pump2_lng) / 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hiển thị vị trí Bơm – Leaflet</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <style>
        body { margin:0; padding:0; }
        #map { width:100%; height:100vh; }

        .popup-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 6px;
        }
        .status-active { color: #28a745; font-weight: bold; }
        .status-waiting { color: #ffc107; font-weight: bold; }
        .status-unknown { color: #6c757d; font-weight: bold; }
    </style>
</head>
<body>

<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
    // Dữ liệu từ PHP đưa vào JS
    let pump1 = {
        lat: <?= $pump1_lat ?>,
        lng: <?= $pump1_lng ?>,
        status: "<?= $pump1_status ?>",
        name: "<?= $pump1_name ?>",
        updated: "<?= $pump1_update ?>"
    };

    let pump2 = {
        lat: <?= $pump2_lat ?>,
        lng: <?= $pump2_lng ?>,
        status: "<?= $pump2_status ?>",
        name: "<?= $pump2_name ?>",
        updated: "<?= $pump2_update ?>"
    };

    let center = {
        lat: <?= $center_lat ?>,
        lng: <?= $center_lng ?>
    };

    // Hàm CSS theo trạng thái
    function getStatusClass(status) {
        status = status.toLowerCase();
        if (status.includes("di chuyển") || status.includes("hoạt động")) return "status-active";
        if (status.includes("chờ") || status.includes("wait")) return "status-waiting";
        return "status-unknown";
    }

    // TẠO MAP
    var map = L.map('map').setView([center.lat, center.lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map);

    // ICONs
    var icon1 = L.icon({
        iconUrl: "https://cdn-icons-png.flaticon.com/512/684/684908.png",
        iconSize: [35, 35],
        iconAnchor: [17, 34]
    });

    var icon2 = L.icon({
        iconUrl: "https://cdn-icons-png.flaticon.com/512/149/149060.png",
        iconSize: [35, 35],
        iconAnchor: [17, 34]
    });

    // Marker 1
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

    // Marker 2
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

    // Auto open popup cả 2
    setTimeout(() => { m1.openPopup(); }, 800);
    setTimeout(() => { m2.openPopup(); }, 1800);

    // Vẽ vòng tròn phạm vi
    L.circle([pump1.lat, pump1.lng], {
        radius: 50,
        color: "#0a4f46",
        fillOpacity: 0.15
    }).addTo(map);

    L.circle([pump2.lat, pump2.lng], {
        radius: 50,
        color: "#dc3545",
        fillOpacity: 0.15
    }).addTo(map);

</script>

</body>
</html>
