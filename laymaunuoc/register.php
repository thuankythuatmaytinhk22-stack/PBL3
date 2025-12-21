<?php
session_start();
include 'database.php';

// Kiểm tra nếu đã đăng nhập thì chuyển hướng
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Lấy thời gian hiện tại cho Header
date_default_timezone_set('Asia/Ho_Chi_Minh');
$current_time = date('H:i:s');
$current_date = date('d/m/Y');

// Xử lý đăng ký
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    
    $errors = [];
    
    if (empty($username)) $errors[] = "Vui lòng nhập tên đăng nhập";
    elseif (strlen($username) < 3) $errors[] = "Tên đăng nhập phải có ít nhất 3 ký tự";
    
    if (empty($password)) $errors[] = "Vui lòng nhập mật khẩu";
    elseif (strlen($password) < 6) $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";
    elseif ($password !== $confirm_password) $errors[] = "Mật khẩu xác nhận không khớp";
    
    if (empty($full_name)) $errors[] = "Vui lòng nhập họ và tên";
    
    if (empty($email)) $errors[] = "Vui lòng nhập email";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ";
    
    if (empty($errors)) {
        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "Tên đăng nhập hoặc email đã tồn tại!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, 'user')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssss", $username, $hashed_password, $full_name, $email);
            
            if ($insert_stmt->execute()) {
                $success_message = "Đăng ký thành công! <a href='login.php'>Đăng nhập ngay</a>";
                $_POST = array();
            } else {
                $error_message = "Có lỗi xảy ra khi đăng ký. Vui lòng thử lại!";
            }
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - Hệ Thống Lấy Mẫu Nước</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS CHUNG & HEADER (Giống trang index) */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; color: #333; }
        
        .header { background: #ffffff; box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1); position: relative; z-index: 1000; }
        .header-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eaeaea; }
        .logo { display: flex; align-items: center; gap: 15px; }
        .logo-icon-small { width: 45px; height: 45px; background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; }
        .logo-text h1 { color: #2c3e50; font-size: 1.6rem; margin-bottom: 2px; font-weight: 700; }
        .time-info { display: flex; flex-direction: column; align-items: flex-end; }
        .current-time { font-size: 1.6rem; font-weight: 700; color: #2c3e50; font-family: 'Courier New', monospace; }
        
        .header-main { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; }
        .nav-menu { display: flex; list-style: none; gap: 5px; background: #f8f9fa; border-radius: 10px; padding: 5px; }
        .nav-menu a { display: flex; align-items: center; gap: 10px; color: #2c3e50; text-decoration: none; font-weight: 600; padding: 10px 20px; border-radius: 8px; transition: 0.3s; font-size: 14px; }
        .nav-menu a:hover { background: #e3f2fd; color: #3498db; }
        
        .auth-section { display: flex; align-items: center; gap: 12px; }
        .login-link { color: #3498db; text-decoration: none; font-weight: 600; font-size: 14px; }

        /* FORM REGISTER STYLES */
        .main-content { display: flex; justify-content: center; align-items: flex-start; padding: 40px 20px; min-height: calc(100vh - 150px); }
        .register-card { background: white; border-radius: 20px; width: 100%; max-width: 550px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #eee; }
        .card-header { text-align: center; margin-bottom: 30px; }
        .card-header h2 { font-size: 1.8rem; color: #2c3e50; margin-bottom: 10px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #444; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #3498db; }
        .form-control { width: 100%; padding: 12px 15px 12px 45px; border: 2px solid #f0f0f0; border-radius: 10px; font-size: 1rem; transition: 0.3s; outline: none; }
        .form-control:focus { border-color: #3498db; box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1); }
        
        .submit-btn { width: 100%; padding: 15px; background: #27ae60; color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .submit-btn:hover { background: #219653; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(39,174,96,0.3); }

        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        
        .password-toggle { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); border: none; background: none; color: #95a5a6; cursor: pointer; }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-container">
            <div class="header-top">
                <div class="logo">
                    <div class="logo-icon-small"><i class="fas fa-tint"></i></div>
                    <div class="logo-text">
                        <h1>Smart Water</h1>
                        <p style="font-size: 0.75rem; color: #7f8c8d;">Hệ thống giám sát lấy mẫu</p>
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
                        <li><a href="#"><i class="fas fa-envelope"></i> Liên hệ</a></li>
                    </ul>
                </nav>
                <div class="auth-section">
                    <span style="font-size: 14px; color: #7f8c8d;">Đã có tài khoản?</span>
                    <a href="login.php" class="login-link">Đăng nhập ngay</a>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="register-card">
            <div class="card-header">
                <h2>Tạo Tài Khoản</h2>
                <p style="color: #7f8c8d;">Đăng ký để sử dụng đầy đủ tính năng hệ thống</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Họ và tên</label>
                    <div class="input-group">
                        <i class="fas fa-id-card"></i>
                        <input type="text" name="full_name" class="form-control" placeholder="Nhập họ và tên" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="form-control" placeholder="Địa chỉ Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Mật khẩu</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Ít nhất 6 ký tự" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Xác nhận mật khẩu</label>
                    <div class="input-group">
                        <i class="fas fa-shield-alt"></i>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại mật khẩu" required>
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-user-plus"></i> HOÀN TẤT ĐĂNG KÝ
                </button>
            </form>
        </div>
    </main>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('vi-VN', {hour12:false});
        }
        setInterval(updateClock, 1000);
    </script>
</body>
</html>