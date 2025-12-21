<?php
// File: admin/send_command.php
if (isset($_GET['cmd'])) {
    $cmd = $_GET['cmd'];
    file_put_contents('../command.txt', $cmd);
    
    // Ghi log
    $log_file = dirname(__DIR__) . "/command_log.txt";
    file_put_contents($log_file, "\n[" . date('Y-m-d H:i:s') . "] Lệnh thủ công: $cmd\n", FILE_APPEND);
    
    echo "✅ Đã gửi lệnh: $cmd";
} else {
    echo "❌ Không có lệnh";
}
?>