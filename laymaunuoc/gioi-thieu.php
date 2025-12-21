<?php
session_start();
include 'database.php';

// Lấy thời gian hiện tại
date_default_timezone_set('Asia/Ho_Chi_Minh');
$current_time = date('H:i:s');
$current_date = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giới Thiệu - Smart Water System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; color: #333; line-height: 1.6; }
        
        /* HEADER (Giống trang chủ) */
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
        .nav-menu a:hover:not(.active) { background: #e3f2fd; }

        /* INTRO SECTION */
        .intro-hero { background: linear-gradient(rgba(44, 62, 80, 0.8), rgba(44, 62, 80, 0.8)), url('https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&q=80&w=1000'); background-size: cover; background-position: center; color: white; padding: 80px 20px; text-align: center; }
        .intro-hero h2 { font-size: 2.5rem; margin-bottom: 15px; }
        .intro-hero p { font-size: 1.1rem; max-width: 800px; margin: 0 auto; opacity: 0.9; }

        /* CONTENT SECTION */
        .main-content { max-width: 1100px; margin: -50px auto 40px; padding: 0 20px; position: relative; z-index: 10; }
        .info-card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-bottom: 30px; }
        
        .grid-features { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-top: 40px; }
        .feature-item { text-align: center; padding: 20px; }
        .feature-item i { font-size: 2.5rem; color: #3498db; margin-bottom: 15px; }
        .feature-item h3 { margin-bottom: 10px; color: #2c3e50; }

        .team-section { text-align: center; margin-top: 50px; }
        .team-grid { display: flex; justify-content: center; gap: 40px; flex-wrap: wrap; margin-top: 30px; }
        .member { background: white; padding: 20px; border-radius: 15px; width: 250px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .member img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 3px solid #3498db; }

        footer { text-align: center; padding: 30px; color: #7f8c8d; font-size: 0.9rem; }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-container">
            <div class="header-top">
                <div class="logo">
                    <div class="logo-icon"><i class="fas fa-tint"></i></div>
                    <div class="logo-text">
                        <h1>Smart Water</h1>
                        <p style="font-size: 0.8rem; color: #7f8c8d;">Giám sát lấy mẫu tự động</p>
                    </div>
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
                        <li><a href="gioi-thieu.php" class="active"><i class="fas fa-info-circle"></i> Giới thiệu</a></li>
                        <li><a href="#"><i class="fas fa-envelope"></i> Liên hệ</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <section class="intro-hero">
        <h2>Giải Pháp Quan Trắc Nước Thông Minh</h2>
        <p>Ứng dụng công nghệ IoT và tự động hóa để bảo vệ nguồn nước bền vững.</p>
    </section>

    <main class="main-content">
        <div class="info-card">
            <h2 style="color: #2c3e50; border-left: 5px solid #3498db; padding-left: 15px; margin-bottom: 20px;">Về Hệ Thống</h2>
            <p>
                <strong>Smart Water System</strong> là hệ thống lấy mẫu nước tự động dựa trên tọa độ GPS, 
                được thiết kế để hỗ trợ các nhà khoa học và đơn vị quản lý môi trường thu thập dữ liệu nước một cách chính xác, 
                tiết kiệm thời gian và giảm thiểu rủi ro con người.
            </p>

            <div class="grid-features">
                <div class="feature-item">
                    <i class="fas fa-satellite-dish"></i>
                    <h3>Giám Sát GPS</h3>
                    <p>Mọi mẫu nước được lấy đều đi kèm tọa độ vị trí thực tế chính xác tuyệt đối.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-robot"></i>
                    <h3>Tự Động Hóa</h3>
                    <p>Điều khiển hệ thống bơm lấy mẫu từ xa qua internet mà không cần có mặt tại hiện trường.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-chart-line"></i>
                    <h3>Dữ Liệu Thời Gian Thực</h3>
                    <p>Nhật ký lấy mẫu được lưu trữ trực tuyến, giúp dễ dàng tra cứu và phân tích bất cứ lúc nào.</p>
                </div>
            </div>
        </div>

        <div class="team-section">
            <h2 style="color: #2c3e50;">Đội Ngũ Phát Triển</h2>
            <div class="team-grid">
                <div class="member">
                    <img src="https://ui-avatars.com/api/?name=Admin+User&background=3498db&color=fff" alt="Team Lead">
                    <h4>Nguyễn Văn A</h4>
                    <p style="color: #7f8c8d; font-size: 0.8rem;">Kỹ sư Hệ thống</p>
                </div>
                <div class="member">
                    <img src="https://ui-avatars.com/api/?name=Dev+Team&background=2ecc71&color=fff" alt="Developer">
                    <h4>Trần Thị B</h4>
                    <p style="color: #7f8c8d; font-size: 0.8rem;">Lập trình viên IoT</p>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Smart Water Project - Công nghệ vì môi trường.</p>
    </footer>

</body>
</html>