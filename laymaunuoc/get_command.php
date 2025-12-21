<?php
$command_file = "command.txt";

if (!file_exists($command_file)) {
    echo "OFF";
    exit();
}

$cmd = trim(file_get_contents($command_file));
if (empty($cmd)) {
    echo "OFF";
    exit();
}

// Chuyển đổi định dạng từ | sang , để ESP32 dễ tách
$output = str_replace('|', ',', $cmd);

// Xử lý các lệnh gộp
$output = str_replace('BOTH_ON', 'R1_ON,R2_ON', $output);
$output = str_replace('BOTH_OFF', 'R1_OFF,R2_OFF', $output);

echo $output;

// QUAN TRỌNG: Sau khi ESP32 đã đọc, ta nên xóa để tránh lặp lệnh
// Nếu bạn muốn lưu trạng thái thì không unlink, nhưng ở đây nên xóa để nhận lệnh mới
unlink($command_file); 
?>