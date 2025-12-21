<?php
session_start();
include 'database.php';

// Thiết lập múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');
$current_time = date('H:i:s');
$current_date = date('d/m/Y');

// Nếu đã đăng nhập thì về trang chủ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$username_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);
    $username_val = $username;

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!';
    } else {
        // Kiểm tra Database (hoặc dùng tài khoản cứng admin/admin123 nếu chưa có DB)
        $sql = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            if ($password === $user['password'] || password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                header('Location: index.php');
                exit();
            } else {
                $error = 'Mật khẩu không chính xác!';
            }
        } else {
            // Dự phòng nếu chưa tạo database thì admin/admin123 vẫn chạy được
            if ($username === 'admin' && $password === 'admin123') {
                $_SESSION['user_id'] = '1';
                $_SESSION['username'] = 'admin';
                $_SESSION['full_name'] = 'Quản trị viên';
                $_SESSION['role'] = 'admin';
                header('Location: index.php');
                exit();
            }
            $error = 'Tên đăng nhập không tồn tại!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - Hệ Thống Lấy Mẫu Nước</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS đồng bộ hoàn toàn với trang Index */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; min-height: 100vh; color: #333; }

        .header { background: #ffffff; box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1); }
        .header-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eaeaea; }
        .logo { display: flex; align-items: center; gap: 15px; }
        .logo-icon { width: 50px; height: 50px; background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.8rem; }
        .logo-text h1 { color: #2c3e50; font-size: 1.5rem; font-weight: 700; }
        .time-info { text-align: right; }
        .current-time { font-size: 1.5rem; font-weight: 700; color: #2c3e50; font-family: 'Courier New', monospace; }

        /* Navigation Menu */
        .header-main { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }
        .nav-menu { display: flex; list-style: none; gap: 10px; background: #f8f9fa; border-radius: 10px; padding: 5px; }
        .nav-menu a { display: flex; align-items: center; gap: 8px; color: #2c3e50; text-decoration: none; font-weight: 600; padding: 10px 20px; border-radius: 8px; transition: 0.3s; }
        .nav-menu a:hover { background: #e3f2fd; color: #3498db; }
        .nav-menu a.active { background: #3498db; color: white; }

        /* Login Card */
        .main-content { max-width: 450px; margin: 50px auto; padding: 0 20px; }
        .login-card { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-icon { width: 70px; height: 70px; background: #f0f7ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: #3498db; font-size: 2rem; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; }
        .input-with-icon { position: relative; }
        .input-with-icon i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #3498db; }
        .form-control { width: 100%; padding: 12px 15px 12px 45px; border: 2px solid #eee; border-radius: 8px; outline: none; transition: 0.3s; }
        .form-control:focus { border-color: #3498db; }

        .alert-error { background: #fff5f5; border-left: 4px solid #e74c3c; color: #c0392b; padding: 12px; margin-bottom: 20px; font-size: 0.9rem; border-radius: 4px; }
        .submit-btn { width: 100%; padding: 14px; background: #3498db; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .submit-btn:hover { background: #2980b9; transform: translateY(-2px); }

        .demo-info { background: #e3f2fd; border-radius: 10px; padding: 15px; margin-top: 25px; border: 1px solid #bbdefb; font-size: 0.85rem; }
        
        .footer { background: #2c3e50; color: white; padding: 30px 20px; text-align: center; margin-top: 50px; }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-container">
            <div class="header-top">
                <div class="logo">
                    <div class="logo-icon"><i class="fas fa-tint"></i></div>
                    <div class="logo-text">
                        <h1>Hệ Thống Lấy Mẫu Nước</h1>
                        <p style="font-size: 0.8rem; color: #7f8c8d;">Điều khiển & Giám sát ESP32</p>
                    </div>
                </div>
                <div class="time-info">
                    <div class="current-time" id="current-time"><?php echo $current_time; ?></div>
                    <div class="current-date" id="current-date"><?php echo $current_date; ?></div>
                </div>
            </div>
            
            <div class="header-main">
                <nav>
                    <ul class="nav-menu">
                        <li><a href="index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
                        <li><a href="#"><i class="fas fa-info-circle"></i> Giới thiệu</a></li>
                        <li><a href="login.php" class="active"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon"><i class="fas fa-user-shield"></i></div>
                <h2>ĐĂNG NHẬP</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" class="form-control" placeholder="admin" value="<?php echo htmlspecialchars($username_val); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">ĐĂNG NHẬP HỆ THỐNG</button>
            </form>

            <div class="demo-info">
                <strong><i class="fas fa-key"></i> Tài khoản mẫu:</strong><br>
                Admin: admin / admin123<br>
                User: user / user123
            </div>
        </div>
    </main>

    <footer class="footer">
        <p>© 2024 Hệ Thống Lấy Mẫu Nước Thông Minh</p>
    </footer>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('vi-VN', { hour12: false });
            document.getElementById('current-date').textContent = now.toLocaleDateString('vi-VN');
        }
        setInterval(updateClock, 1000);
    </script>
</body>
</html>