<?php
// test_database_setup.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Database Setup</h2>";

require_once 'database.php';

echo "<h3>1. Testing database setup...</h3>";
$result = initialize_database();
if ($result) {
    echo "✅ Database setup thành công!<br>";
} else {
    echo "❌ Database setup thất bại!<br>";
}

echo "<h3>2. Testing connection...</h3>";
if (check_database_connection()) {
    echo "✅ Kết nối database OK<br>";
} else {
    echo "❌ Lỗi kết nối database<br>";
}

echo "<h3>3. Testing query...</h3>";
$users = db_query("SELECT * FROM users");
if ($users !== false) {
    echo "✅ Query users thành công. Số user: " . count($users) . "<br>";
    foreach ($users as $user) {
        echo " - " . $user['username'] . " (" . $user['role'] . ")<br>";
    }
} else {
    echo "❌ Lỗi query users<br>";
}

echo "<h3>4. Testing admin login...</h3>";
$admin = db_query("SELECT * FROM users WHERE username = 'admin'");
if ($admin && count($admin) > 0) {
    echo "✅ Tìm thấy admin user<br>";
    if (password_verify('admin123', $admin[0]['password'])) {
        echo "✅ Mật khẩu admin chính xác<br>";
    } else {
        echo "❌ Mật khẩu admin không chính xác<br>";
    }
} else {
    echo "❌ Không tìm thấy admin user<br>";
}
?>