<?php
session_start();
include 'database.php';

// Lấy thời gian hiện tại
date_default_timezone_set('Asia/Ho_Chi_Minh');
$current_time = date('H:i:s');
$current_date = date('d/m/Y');

// Xử lý khi người dùng gửi Form
$message_status = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['fullname']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);
    
    // Ở đây bạn có thể thêm code lưu vào database hoặc gửi mail
    // Tạm thời mình giả định gửi thành công
    $message_status = "success";
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên Hệ - Smart Water System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; color: #333; }
        
        /* HEADER (Đồng bộ) */
        .header { background: #ffffff; box-shadow: 0 3px 15px rgba(0, 0, 0, 0.1); position: relative; z-index: 1000; }
        .header-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .header-top { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eaeaea; }
        .logo { display: flex; align-items: center; gap: 15px; }
        .logo-icon { width: 50px; height: 50px; background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.8rem; }
        .logo-text h1 { color: #2c3e50; font-size: 1.8rem; margin-bottom: 5px; font-weight: 700; }
        
        .header-main { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; }
        .nav-menu { display: flex; list-style: none; gap: 5px; background: #f8f9fa; border-radius: 10px; padding: 5px; }
        .nav-menu a { display: flex; align-items: center; gap: 10px; color: #2c3e50; text-decoration: none; font-weight: 600; padding: 12px 25px; border-radius: 8px; transition: 0.3s; }
        .nav-menu a.active { background: #3498db; color: white; }

        /* CONTACT CONTENT */
        .main-content { max-width: 1100px; margin: 50px auto; padding: 0 20px; }
        .contact-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; }

        /* CỘT TRÁI: THÔNG TIN */
        .contact-info { background: #2c3e50; color: white; padding: 40px; border-radius: 15px; display: flex; flex-direction: column; gap: 30px; }
        .info-box { display: flex; align-items: flex-start; gap: 15px; }
        .info-box i { font-size: 1.5rem; color: #3498db; margin-top: 5px; }
        .info-box h3 { margin-bottom: 5px; font-size: 1.1rem; }
        .info-box p { opacity: 0.8; font-size: 0.9rem; }

        /* CỘT PHẢI: FORM */
        .contact-form { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; transition: 0.3s; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #3498db; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1); }
        
        .submit-btn { background: #3498db; color: white; border: none; padding: 15px 30px; border-radius: 8px; font-weight: 700; cursor: pointer; width: 100%; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .submit-btn:hover { background: #2980b9; transform: translateY(-2px); }

        .success-alert { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb; }

        @media (max-width: 768px) {
            .contact-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-container">
            <div class="header-top">
                <div class="logo">
                    <div class="logo-icon"><i class="fas fa-tint"></i></div>
                    <div class="logo-text"><h1>Smart Water</h1><p style="font-size: 0.8rem; color: #7f8c8d;">Giám sát lấy mẫu tự động</p></div>
                </div>
                <div class="time-info" style="text-align: right;">
                    <div style="font-weight: 700; color: #2c3e50;"><?php echo $current_time; ?></div>
                    <div style="font-size: 0.8rem; color: #7f8c8d;"><?php echo $current_date; ?></div>
                </div>
            </div>
            
            <div class="header-main">
                <nav>
                    <ul class="nav-menu">
                        <li><a href="index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
                        <li><a href="gioi-thieu.php"><i class="fas fa-info-circle"></i> Giới thiệu</a></li>
                        <li><a href="lien-he.php" class="active"><i class="fas fa-envelope"></i> Liên hệ</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="contact-grid">
            <div class="contact-info">
                <div>
                    <h2 style="margin-bottom: 10px;">Liên Hệ Với Chúng Tôi</h2>
                    <p style="opacity: 0.7; font-size: 0.9rem;">Bạn có câu hỏi về hệ thống hoặc cần hỗ trợ kỹ thuật? Đừng ngần ngại để lại lời nhắn.</p>
                </div>

                <div class="info-box">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h3>Địa chỉ</h3>
                        <p>Khu Công nghệ cao, Quận Liên Chiểu,<br>TP. Đà Nẵng, Việt Nam</p>
                    </div>
                </div>

                <div class="info-box">
                    <i class="fas fa-phone-alt"></i>
                    <div>
                        <h3>Điện thoại</h3>
                        <p>+84 123 456 789</p>
                    </div>
                </div>

                <div class="info-box">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h3>Email</h3>
                        <p>support@smartwater.vn</p>
                    </div>
                </div>

                <div style="margin-top: auto; display: flex; gap: 15px; font-size: 1.2rem;">
                    <i class="fab fa-facebook" style="cursor: pointer;"></i>
                    <i class="fab fa-linkedin" style="cursor: pointer;"></i>
                    <i class="fab fa-github" style="cursor: pointer;"></i>
                </div>
            </div>

            <div class="contact-form">
                <?php if ($message_status == "success"): ?>
                    <div class="success-alert">
                        <i class="fas fa-check-circle"></i> Cảm ơn bạn! Tin nhắn đã được gửi đi thành công.
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <div class="form-group">
                        <label>Họ và Tên</label>
                        <input type="text" name="fullname" placeholder="Nhập tên của bạn..." required>
                    </div>
                    
                    <div class="form-group">
                        <label>Địa chỉ Email</label>
                        <input type="email" name="email" placeholder="email@example.com" required>
                    </div>

                    <div class="form-group">
                        <label>Lời nhắn / Câu hỏi</label>
                        <textarea name="message" rows="5" placeholder="Viết nội dung tại đây..." required></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                        Gửi Tin Nhắn <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <footer style="text-align: center; padding: 40px; color: #7f8c8d;">
        <p>&copy; 2024 Smart Water Project. Hệ thống quản lý quan trắc thông minh.</p>
    </footer>

</body>
</html>