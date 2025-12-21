<?php
// admin/map_control.php
include '../database.php';

// Chỉ lấy tọa độ HIỆN TẠI từ database
$result = $conn->query("SELECT pump_number, current_lat, current_lng FROM pump_locations WHERE pump_number IN (1, 2)");
$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[$row['pump_number']] = $row;
}

$default_lat = 16.0601;
$default_lng = 108.2119;

$p1_lat = $locations[1]['current_lat'] ?? $default_lat;
$p1_lng = $locations[1]['current_lng'] ?? $default_lng;
$p2_lat = $locations[2]['current_lat'] ?? $default_lat;
$p2_lng = $locations[2]['current_lng'] ?? $default_lng;

$center_lat = ($p1_lat + $p2_lat) / 2;
$center_lng = ($p1_lng + $p2_lng) / 2;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>html, body { height: 100%; margin: 0; } #map { width: 100%; height: 100%; }</style>
</head>
<body>
    <div id="map"></div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        var map = L.map('map').setView([<?= $center_lat ?>, <?= $center_lng ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        var icon1 = L.icon({ iconUrl: "https://cdn-icons-png.flaticon.com/512/684/684908.png", iconSize: [35, 35], iconAnchor: [17, 34] });
        var icon2 = L.icon({ iconUrl: "https://cdn-icons-png.flaticon.com/512/149/149060.png", iconSize: [35, 35], iconAnchor: [17, 34] });

        var marker1 = L.marker([<?= $p1_lat ?>, <?= $p1_lng ?>], {icon: icon1, draggable: true}).addTo(map);
        var marker2 = L.marker([<?= $p2_lat ?>, <?= $p2_lng ?>], {icon: icon2, draggable: true}).addTo(map);

        // Hàm cập nhật Database ngay lập tức khi thả marker
        function updateDB(pumpNum, lat, lng) {
            let formData = new FormData();
            formData.append('pump_number', pumpNum);
            formData.append('lat', lat);
            formData.append('lng', lng);
            
            fetch('update_coords.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => console.log("Đã cập nhật Bơm " + pumpNum));
        }

        function syncToParent() {
            window.parent.postMessage({
                type: "updateLocation",
                bom1_lat: marker1.getLatLng().lat.toFixed(6),
                bom1_lng: marker1.getLatLng().lng.toFixed(6),
                bom2_lat: marker2.getLatLng().lat.toFixed(6),
                bom2_lng: marker2.getLatLng().lng.toFixed(6)
            }, "*");
        }

        marker1.on("dragend", function() {
            let pos = marker1.getLatLng();
            updateDB(1, pos.lat.toFixed(6), pos.lng.toFixed(6));
            syncToParent();
        });

        marker2.on("dragend", function() {
            let pos = marker2.getLatLng();
            updateDB(2, pos.lat.toFixed(6), pos.lng.toFixed(6));
            syncToParent();
        });
    </script>
</body>
</html>