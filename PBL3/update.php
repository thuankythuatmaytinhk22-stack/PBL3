<?php
// update.php - Cập nhật để lưu vào cả JSON và Database

// 1. KẾT NỐI VÀ THIẾT LẬP CSDL (Sẽ tự động tạo CSDL/Bảng nếu chưa có)
require_once 'database.php'; 

// 2. Lấy dữ liệu từ GET
$pH = $_GET["pH"] ?? null;
$turbidity = $_GET["turb"] ?? null;
$temperature = $_GET["temp"] ?? null;
$water_level = $_GET["level"] ?? null; 
$humidity = $_GET["humi"] ?? null;

// 3. Chuẩn bị dữ liệu cho file JSON (Dashboard hiển thị nhanh)
$data_json = [
    "pH" => $pH ?? "N/A",
    "turbidity" => $turbidity ?? "N/A",
    "temperature" => $temperature ?? "N/A",
    "water_level" => $water_level ?? "N/A", 
    "humidity" => $humidity ?? "N/A", 
    "time" => date("Y-m-d H:i:s")
];

// Ghi dữ liệu vào file JSON
file_put_contents("data.json", json_encode($data_json));

// 4. Lưu vào Database (chỉ lưu nếu có ít nhất 1 giá trị sensor hợp lệ)
if (is_numeric($pH) || is_numeric($turbidity) || is_numeric($temperature) || is_numeric($water_level) || is_numeric($humidity)) {
    
    $sql = "INSERT INTO sensor_data (pH, turbidity, temperature, water_level, humidity, recorded_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
            
    // Sử dụng 0 nếu giá trị null hoặc không phải là số hợp lệ
    $params = [
        floatval(is_numeric($pH) ? $pH : 0),
        floatval(is_numeric($turbidity) ? $turbidity : 0),
        floatval(is_numeric($temperature) ? $temperature : 0),
        floatval(is_numeric($water_level) ? $water_level : 0),
        floatval(is_numeric($humidity) ? $humidity : 0)
    ];
    
    // Sử dụng hàm đã định nghĩa trong database.php
    db_execute($sql, "ddddd", $params);
}

// Phản hồi lại cho thiết bị ESP32
echo "OK";
?>