<?php
// logout.php - Xử lý đăng xuất triệt để và chuyển hướng về trang chủ

// Bắt đầu Session
session_start();

// 1. Hủy tất cả các biến Session
$_SESSION = array();

// 2. Hủy cookie Session (Đảm bảo session bị hủy triệt để trên trình duyệt)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Cuối cùng, hủy session
session_destroy();

// 4. CHUYỂN HƯỚNG VỀ TRANG CHỦ (index.php)
// Để người dùng sau khi đăng xuất có thể quay lại Dashboard ngay.
header("location: index.php"); 
exit;
?>