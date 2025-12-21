<?php
// admin/log_action.php - Hàm ghi nhật ký
function log_pump_action($pump_number, $action, $duration = null, $location_lat = null, $location_lng = null, $location_name = null) {
    include 'database.php';
    
    $stmt = $conn->prepare("INSERT INTO pump_logs (pump_number, action, duration, location_lat, location_lng, location_name) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isidds", $pump_number, $action, $duration, $location_lat, $location_lng, $location_name);
    return $stmt->execute();
}
?>