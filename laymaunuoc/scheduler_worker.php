<?php
// File: scheduler_worker.php
include 'database.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

$command_file = __DIR__ . "/command.txt";
$log_file = __DIR__ . "/command_log.txt";

while (true) { // Chạy vòng lặp vô tận (nếu dùng dòng lệnh) hoặc chạy 1 lần (nếu dùng Cron)
    $now = date('H:i:00'); // Lấy giờ hiện tại phút:giây (00)
    
    // Tìm lịch trình khớp với giờ hiện tại và đang ở trạng thái pending
    $sql = "SELECT * FROM pump_schedule WHERE start_time = '$now' AND status = 'pending'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $pump = $row['pump_number'];
            $duration = $row['duration_seconds'];
            $cmd_on = ($pump == 1) ? "R1_ON" : "R2_ON";
            $cmd_off = ($pump == 1) ? "R1_OFF" : "R2_OFF";

            // 1. Bật bơm
            file_put_contents($command_file, $cmd_on);
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Auto: $cmd_on (Duration: $duration s)\n", FILE_APPEND);
            
            // 2. Cập nhật database thành 'completed' để không chạy lại lần nữa trong cùng 1 phút
            $conn->query("UPDATE pump_schedule SET status = 'completed' WHERE id = $id");

            // 3. Đợi hết thời gian
            sleep($duration);

            // 4. Tắt bơm
            file_put_contents($command_file, $cmd_off);
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Auto: $cmd_off (End schedule)\n", FILE_APPEND);
        }
    }
    
    // Nghỉ 1 giây trước khi kiểm tra lại
    sleep(1);
    
    // Lưu ý: Nếu dùng host web thông thường, bạn nên dùng Cron Job gọi file này mỗi 1 phút 
    // thay vì dùng vòng lặp while(true).
}