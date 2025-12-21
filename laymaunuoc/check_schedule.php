<?php
// File: LAYMAUNUOC/check_schedule.php
include 'database.php';
include 'log_action.php'; // Include file ghi log
date_default_timezone_set('Asia/Ho_Chi_Minh');

$command_file = __DIR__ . "/command.txt";
$log_file = __DIR__ . "/command_log.txt";

// Lấy giờ hiện tại (HH:mm:00)
$now = date('H:i:00');

// Tìm lịch 'pending' mà giờ bắt đầu khớp với hiện tại
$sql = "SELECT * FROM pump_schedule WHERE start_time = '$now' AND status = 'pending'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $pump1_duration = 0;
    $pump2_duration = 0;
    $pump1_id = 0;
    $pump2_id = 0;
    
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $pump = $row['pump_number'];
        $duration = $row['duration_seconds'];
        
        if ($pump == 1) {
            $pump1_duration = $duration;
            $pump1_id = $id;
        } elseif ($pump == 2) {
            $pump2_duration = $duration;
            $pump2_id = $id;
        }
    }
    
    // Xử lý bơm 1
    if ($pump1_duration > 0) {
        $conn->query("UPDATE pump_schedule SET status = 'completed' WHERE id = $pump1_id");
        
        // Nếu có cả bơm 2, chạy cùng lúc
        if ($pump2_duration > 0) {
            // Cập nhật trạng thái bơm 2
            $conn->query("UPDATE pump_schedule SET status = 'completed' WHERE id = $pump2_id");
            
            // GHI NHẬT KÝ: BẬT CẢ 2 BƠM
            log_pump_action(3, 'AUTO', max($pump1_duration, $pump2_duration), null, null, 'Lịch tự động - Cả 2 bơm');
            
            // Bật cả 2 bơm cùng lúc
            file_put_contents($command_file, "R1_ON|R2_ON");
            file_put_contents($log_file, "[$now] TỰ ĐỘNG: Bật CẢ 2 BƠM\n", FILE_APPEND);
            
            // Chờ thời gian dài nhất
            $max_duration = max($pump1_duration, $pump2_duration);
            sleep($max_duration);
            
            // Tắt cả 2 bơm cùng lúc
            file_put_contents($command_file, "R1_OFF|R2_OFF");
            file_put_contents($log_file, "[" . date('H:i:s') . "] TỰ ĐỘNG: Tắt CẢ 2 BƠM\n", FILE_APPEND);
            
            echo "Đã chạy cả 2 bơm trong $max_duration giây (đã ghi nhật ký)";
        } else {
            // Chỉ có bơm 1
            // GHI NHẬT KÝ: BẬT BƠM 1
            log_pump_action(1, 'AUTO', $pump1_duration, null, null, 'Lịch tự động - Bơm 1');
            
            file_put_contents($command_file, "R1_ON");
            file_put_contents($log_file, "[$now] TỰ ĐỘNG: Bật bơm 1 ($pump1_duration s)\n", FILE_APPEND);
            
            sleep($pump1_duration);
            
            file_put_contents($command_file, "R1_OFF");
            file_put_contents($log_file, "[" . date('H:i:s') . "] TỰ ĐỘNG: Tắt bơm 1\n", FILE_APPEND);
            
            echo "Đã chạy bơm 1 trong $pump1_duration giây (đã ghi nhật ký)";
        }
    }
    // Chỉ có bơm 2
    elseif ($pump2_duration > 0) {
        $conn->query("UPDATE pump_schedule SET status = 'completed' WHERE id = $pump2_id");
        
        // GHI NHẬT KÝ: BẬT BƠM 2
        log_pump_action(2, 'AUTO', $pump2_duration, null, null, 'Lịch tự động - Bơm 2');
        
        file_put_contents($command_file, "R2_ON");
        file_put_contents($log_file, "[$now] TỰ ĐỘNG: Bật bơm 2 ($pump2_duration s)\n", FILE_APPEND);
        
        sleep($pump2_duration);
        
        file_put_contents($command_file, "R2_OFF");
        file_put_contents($log_file, "[" . date('H:i:s') . "] TỰ ĐỘNG: Tắt bơm 2\n", FILE_APPEND);
        
        echo "Đã chạy bơm 2 trong $pump2_duration giây (đã ghi nhật ký)";
    }
} else {
    echo "Không có lịch trình nào lúc $now";
}
?>