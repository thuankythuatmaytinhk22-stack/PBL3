<?php
// database.php

// 1. CẤU HÌNH KẾT NỐI (SERVER)
// Đã thêm cổng 3307 theo cấu hình XAMPP của bạn
define('DB_SERVER', 'localhost:3307'); 
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', ''); 
define('DB_NAME', 'laymaunuocthongminh');

$conn = null;

// Hàm kết nối và thiết lập CSDL/Bảng
function setup_database() {
    global $conn;

    try {
        // A. KẾT NỐI VÀO MYSQL SERVER
        $temp_conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);

        if ($temp_conn->connect_error) {
            // Không sử dụng die() để ứng dụng có thể tiếp tục
            // throw new Exception("Lỗi kết nối MySQL Server: " . $temp_conn->connect_error);
            return false; // Trả về false nếu lỗi
        }
        
        // B. TẠO CSDL NẾU CHƯA TỒN TẠI
        $create_db_sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
        if (!$temp_conn->query($create_db_sql)) {
            // throw new Exception("Lỗi tạo CSDL: " . $temp_conn->error);
            $temp_conn->close();
            return false;
        }
        
        // Đóng kết nối tạm thời
        $temp_conn->close();

        // C. KẾT NỐI LẠI VỚI CSDL ĐÃ TẠO
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($conn->connect_error) {
            // throw new Exception("Lỗi kết nối CSDL " . DB_NAME . ": " . $conn->connect_error);
            return false;
        }
        $conn->set_charset("utf8mb4");

        // D. TẠO CÁC BẢNG (users và sensor_data)
        $create_users_table = "
            CREATE TABLE IF NOT EXISTS users (
                id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'user',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        if (!$conn->query($create_users_table)) {
            // throw new Exception("Lỗi tạo bảng users: " . $conn->error);
            return false;
        }
        
        // THÊM ADMIN MẪU
        $check_admin_sql = "SELECT id FROM users WHERE username = 'admin'";
        $result = $conn->query($check_admin_sql);
        if ($result->num_rows == 0) {
            $admin_password_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $insert_admin_sql = "INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')";
            
            $stmt = $conn->prepare($insert_admin_sql);
            $stmt->bind_param("s", $admin_password_hash);
            $stmt->execute();
            $stmt->close();
        }

        // Tạo bảng sensor_data (với 5 chỉ số mới)
        $create_sensor_data_table = "
            CREATE TABLE IF NOT EXISTS sensor_data (
                id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                pH DECIMAL(4,2) NULL,
                turbidity DECIMAL(6,2) NULL,
                temperature DECIMAL(5,2) NULL,
                water_level DECIMAL(5,2) NULL, 
                humidity DECIMAL(5,2) NULL,
                recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        if (!$conn->query($create_sensor_data_table)) {
            // throw new Exception("Lỗi tạo bảng sensor_data: " . $conn->error);
            return false;
        }
        
    } catch (Exception $e) {
        // Log lỗi hoặc xử lý ngoại lệ
        return false;
    }
    
    return true;
}

// ----------------------------------------------------
// CÁC HÀM THỰC THI CHÍNH
// ----------------------------------------------------

function db_connect() {
    global $conn;
    
    if (!$conn) {
        $conn = @new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        
        // Nếu kết nối lỗi (thường là lỗi database chưa tồn tại)
        if ($conn->connect_error) {
            // THỬ CHẠY SETUP DATABASE
            if (!setup_database()) {
                // Nếu setup thất bại, trả về false
                return false;
            }
        }
        
        // Nếu kết nối thành công (sau setup hoặc ban đầu)
        if ($conn) {
            $conn->set_charset("utf8mb4");
        } else {
             return false;
        }
    }
    
    return $conn;
}

function db_execute($sql, $types = null, $params = []) {
    $conn = db_connect();
    
    if (!$conn) {
        return false;
    }
    
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        return false;
    }

    if ($types && count($params) > 0) {
        // Hàm này yêu cầu truyền tham số dưới dạng tham chiếu.
        // PHP 5.6+ và PHP 7+ cho phép dùng call_user_func_array, 
        // nhưng cách đơn giản nhất là dùng cú pháp spread (...) trong PHP 5.6+
        $stmt->bind_param($types, ...$params);
    }
    
    $success = $stmt->execute();
    
    $stmt->close();
    return $success;
}

function db_query($sql, $types = null, $params = []) {
    $conn = db_connect();
    
    if (!$conn) {
        return false;
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        return false;
    }

    if ($types && count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        return false;
    }
    
    $result = $stmt->get_result();
    
    if ($result === false) {
        return false;
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    return $data;
}

// Hàm để chạy setup thủ công khi cần (đã không cần thiết vì db_connect() tự gọi)
function initialize_database() {
    return setup_database();
}
?>