<?php
include '../database.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $pump_num = (int)$_POST['pump_number'];
    $lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
    $lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT);

    if ($lat && $lng) {
        // Cập nhật TRỰC TIẾP vào current_lat và current_lng
        $stmt = $conn->prepare("UPDATE pump_locations SET current_lat = ?, current_lng = ?, updated_at = NOW() WHERE pump_number = ?");
        $stmt->bind_param("ddi", $lat, $lng, $pump_num);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error"]);
        }
    }
}
?>