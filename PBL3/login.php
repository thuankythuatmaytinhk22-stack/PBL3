<?php
// login.php - Xử lý đăng nhập Admin (Dùng logic mô phỏng)

session_start();

// 1. Kiểm tra nếu Admin đã đăng nhập rồi thì chuyển hướng về dashboard Admin
// Biến kiểm tra là $_SESSION['is_admin']
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("location: admin/dashboard.php");
    exit;
}

$error = '';
$username_input = ''; 

// 2. Xử lý khi người dùng gửi form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_input = trim($_POST["username"]);
    $password_input = trim($_POST["password"]);

    if (empty($username_input) || empty($password_input)) {
        $error = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.";
    } else {
        // --- LOGIC MÔ PHỎNG TẠM THỜI (Tên đăng nhập: admin, Mật khẩu: 123456) ---
        if ($username_input === 'admin' && $password_input === '123456') {
            
            // Đăng nhập thành công
            $_SESSION['is_admin'] = true; // BẮT BUỘC phải đặt biến này
            $_SESSION['username'] = $username_input;
            
            // CHUYỂN HƯỚNG
            header("location: admin/dashboard.php");
            exit;
            
        } else {
            $error = "Tên đăng nhập hoặc Mật khẩu không chính xác. (Dùng admin/123456 để mô phỏng)";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Quản Trị - SmartWater</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        /* CSS TỪ DASHBOARD (index.php) ĐỂ ĐỒNG BỘ HEADER */
        :root {
            --primary: #1a73e8;
            --primary-dark: #0d47a1;
            --secondary: #5f6368;
            --accent: #34a853;
            --bg: #f8f9fa;
            --text: #202124;
            --card: #ffffff;
            --border-radius: 10px;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Roboto', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        /* Header & Navigation Styles */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
        }
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
        }
        .logo::before {
            content: "💧";
            margin-right: 10px;
            font-size: 2rem;
        }
        nav {
            display: flex;
            gap: 2rem;
        }
        nav a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 0;
            position: relative;
            transition: all 0.3s;
        }
        .login-btn, .admin-btn { /* Style cho nút Đăng nhập / Quản trị */
            background: rgba(255, 255, 255, 0.2);
            padding: 0.6rem 1.5rem;
            border-radius: 20px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .login-btn:hover, .admin-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        /* CSS RIÊNG CHO TRANG LOGIN */
        .page-content {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 80px); /* Đảm bảo nội dung chiếm hết chiều cao còn lại */
            padding: 20px;
        }
        .login-container { 
            background: white; 
            padding: 30px; 
            border-radius: var(--border-radius); 
            box-shadow: var(--shadow); 
            width: 100%; 
            max-width: 400px; 
        }
        h2 { 
            text-align: center; 
            color: var(--primary); 
            margin-bottom: 25px; 
        }
        .form-group { 
            margin-bottom: 15px; 
        }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: 500; 
            color: var(--secondary); 
        }
        input[type="text"], input[type="password"] { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #dadce0; 
            border-radius: 5px; 
            box-sizing: border-box; 
            transition: border-color 0.3s; 
        }
        input[type="text"]:focus, input[type="password"]:focus { 
            border-color: var(--primary); 
            outline: none; 
            box-shadow: 0 0 0 1px var(--primary); 
        }
        .btn-login { 
            width: 100%; 
            padding: 12px; 
            background-color: var(--primary); 
            color: white; 
            border: none; 
            border-radius: 5px; 
            font-size: 1rem; 
            cursor: pointer; 
            transition: background-color 0.3s; 
        }
        .btn-login:hover { 
            background-color: var(--primary-dark); 
        }
        .alert-error { 
            color: #c5221f; 
            background-color: #fce8e6; 
            border: 1px solid #ea4335; 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 15px; 
            text-align: center; 
            font-weight: 500;
        }

    </style>
</head>
<body>
    <header>
        <div class="logo">SmartWater Dashboard</div>
        <nav>
            <a href="index.php#home">Trang chủ</a>
            <a href="index.php#status">Tình trạng nước</a>
            <a href="index.php#system-images">Hình ảnh hệ thống</a>
        </nav>
        <a class="login-btn" href="login.php">Đăng nhập</a>
    </header>

    <div class="page-content">
        <div class="login-container">
            <h2>Đăng Nhập Admin</h2>
            <?php if (!empty($error)) { echo '<div class="alert-error">' . $error . '</div>'; } ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label for="username">Tên đăng nhập:</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username_input); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Mật khẩu:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">Đăng nhập</button>
            </form>
            <p style="margin-top: 15px; text-align: center; font-size: 0.9em; color: var(--secondary);">
                *Sử dụng **admin / 123456** để đăng nhập thử nghiệm.
            </p>
        </div>
    </div>
</body>
</html>