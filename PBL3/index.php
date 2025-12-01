<?php
// index.php - Smart Water Sampling Dashboard
// File này sẽ đọc dữ liệu từ data.json (được update bởi update.php)

// 1. BẮT ĐẦU SESSION ĐỂ QUẢN LÝ TRẠNG THÁI ĐĂNG NHẬP
session_start();

// Thiết lập một biến kiểm tra trạng thái Admin
$is_admin_logged_in = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

$data_file = 'data.json';

// Hàm lấy dữ liệu từ file JSON
function getSensorData($file_path) {
    if (file_exists($file_path)) {
        $json_data = file_get_contents($file_path);
        $data = json_decode($json_data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
    }
    // Dữ liệu mẫu (Sample data) - Đảm bảo có đủ 5 trường dữ liệu mới
    return [
        'temperature' => '25.0',
        'pH' => '7.0',
        'turbidity' => '0.0',
        'water_level' => '75.0', // Dữ liệu mới
        'humidity' => '70.0', // Dữ liệu mới
        'time' => 'N/A'
    ];
}

// Lấy dữ liệu thực tế (hoặc mẫu)
$sensor_data = getSensorData($data_file);

// Xử lý dữ liệu để hiển thị
$temperature = isset($sensor_data['temperature']) ? floatval($sensor_data['temperature']) : 0;
$ph = isset($sensor_data['pH']) ? floatval($sensor_data['pH']) : 0;
$turbidity = isset($sensor_data['turbidity']) ? floatval($sensor_data['turbidity']) : 0;
$water_level = isset($sensor_data['water_level']) ? floatval($sensor_data['water_level']) : 0;
$humidity = isset($sensor_data['humidity']) ? floatval($sensor_data['humidity']) : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Smart Water Sampling Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Palette màu chuyên nghiệp cho dashboard */
            --primary: #0066cc;
            --primary-dark: #004d99;
            --primary-light: #e6f2ff;
            --secondary: #5c6bc0;
            --accent: #00b894;
            --bg: #f5f7fa;
            --text: #2c3e50;
            --text-light: #7f8c8d;
            --card: #ffffff;
            --safe: #d4edda;
            --warning: #fff3cd;
            --danger: #f8d7da;
            --border-radius: 12px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 10px 15px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
            --color-safe: #28a745;
            --color-warning: #ffc107;
            --color-danger: #dc3545;
            --transition: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        /* Header & Navigation */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 0.5px;
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
            transition: var(--transition);
        }
        
        nav a:hover {
            color: #e8f0fe;
        }
        
        nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: #fff;
            transition: width 0.3s;
        }
        
        nav a:hover::after {
            width: 100%;
        }
        
        .auth-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .login-btn, .admin-btn {
            padding: 0.6rem 1.5rem;
            border-radius: 20px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .login-btn {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .login-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .admin-btn {
            background: var(--accent);
            border-color: var(--accent);
        }
        
        .admin-btn:hover {
            background: #00a085;
            transform: translateY(-2px);
        }
        
        .logout-btn {
            background: #e74c3c;
            border-color: #e74c3c;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }

        /* Main Content Layout */
        main {
            max-width: 1400px;
            margin: auto;
            padding: 2rem 1.5rem;
        }

        /* Hero/Dashboard Intro */
        .hero {
            display: flex;
            gap: 2.5rem;
            background: var(--card);
            padding: 2.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            align-items: center;
        }
        
        .hero:hover {
            box-shadow: var(--shadow-hover);
        }
        
        .hero-image-container {
            max-width: 40%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .hero-image {
            width: 100%;
            height: 100%;
            min-height: 250px;
            border-radius: var(--border-radius);
            object-fit: cover;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .hero-text h1 {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
            line-height: 1.2;
        }
        
        .hero-text p {
            margin-bottom: 1.2rem;
            color: var(--text-light);
        }

        /* Status & Cards Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: var(--card);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }
        
        .card h2 {
            margin-bottom: 1.5rem;
            color: var(--primary);
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.8rem;
            display: flex;
            align-items: center;
            font-weight: 600;
        }
        
        .card h2::before {
            margin-right: 10px;
            font-size: 1.5rem;
        }

        /* Sensor Data Display */
        .sensor-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .sensor {
            background: var(--primary-light);
            padding: 1.5rem;
            text-align: center;
            border-radius: var(--border-radius);
            border-left: 5px solid var(--primary);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .sensor::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary);
            transform: scaleX(0);
            transition: transform 0.3s;
        }
        
        .sensor:hover::before {
            transform: scaleX(1);
        }
        
        .sensor:hover {
            transform: translateY(-3px);
        }
        
        .sensor h3 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
            color: var(--text-light);
            font-weight: 500;
        }
        
        .sensor-value {
            font-size: 2.5rem;
            font-weight: 700;
            transition: color 0.5s;
            margin-bottom: 0.5rem;
        }
        
        .sensor-unit {
            font-size: 1rem;
            color: var(--text-light);
            font-weight: 500;
        }

        /* Indicators/Thresholds */
        .indicator-box {
            display: flex;
            gap: 1.2rem;
            margin-top: 1.5rem;
        }
        
        .indicator {
            flex: 1;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid rgba(0, 0, 0, 0.05);
            font-size: 0.9rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .indicator::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            transform: scaleX(0);
            transition: transform 0.3s;
        }
        
        .indicator:hover::before {
            transform: scaleX(1);
        }
        
        .indicator:hover {
            transform: translateY(-3px);
        }
        
        .indicator h3 {
            font-size: 1.1rem;
            margin-bottom: 0.8rem;
            font-weight: 700;
            border-bottom: none;
            padding-bottom: 0;
            display: flex;
            align-items: center;
        }
        
        .indicator h3::before {
            margin-right: 8px;
            font-size: 1.2rem;
        }
        
        .indicator.safe { 
            background: var(--safe); 
            color: #155724; 
            border-left: 4px solid var(--color-safe);
        }
        
        .indicator.safe::before {
            background: var(--color-safe);
        }
        
        .indicator.safe h3::before {
            content: "✅";
        }
        
        .indicator.warning { 
            background: var(--warning); 
            color: #856404; 
            border-left: 4px solid var(--color-warning);
        }
        
        .indicator.warning::before {
            background: var(--color-warning);
        }
        
        .indicator.warning h3::before {
            content: "⚠️";
        }
        
        .indicator.danger { 
            background: var(--danger); 
            color: #721c24; 
            border-left: 4px solid var(--color-danger);
        }
        
        .indicator.danger::before {
            background: var(--color-danger);
        }
        
        .indicator.danger h3::before {
            content: "🚨";
        }

        /* System Images Section */
        .system-images {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .image-item {
            text-align: center;
            transition: var(--transition);
        }

        .image-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .image-item:hover img {
            transform: scale(1.05);
        }

        .image-item p {
            margin-top: 0.8rem;
            color: var(--text-light);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Footer */
        footer {
            background: var(--text);
            color: #fff;
            margin-top: 3rem;
            padding: 3rem 1rem 1.5rem;
        }
        
        .footer-info {
            max-width: 1200px;
            margin: auto;
            display: flex;
            flex-wrap: wrap;
            gap: 3rem;
            justify-content: space-between;
        }
        
        .footer-section {
            flex: 1;
            min-width: 250px;
        }
        
        .footer-info h3 {
            margin-bottom: 1rem;
            color: var(--accent);
            font-weight: 600;
        }
        
        .footer-info p {
            margin-bottom: 0.8rem;
            color: #e8eaed;
        }
        
        .socials {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .socials a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: #fff;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .socials a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }
        
        .copyright {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #9aa0a6;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            nav {
                gap: 1rem;
            }
            
            .hero {
                flex-direction: column;
            }
            
            .hero-image-container {
                max-width: 100%;
            }
            
            .indicator-box {
                flex-direction: column;
            }
            
            .sensor-box {
                grid-template-columns: 1fr 1fr;
            }
            
            .auth-buttons {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .sensor-box {
                grid-template-columns: 1fr;
            }
            
            .hero-text h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">Smart Water Sampling</div>
        <nav>
            <a href="#home">Trang chủ</a>
            <a href="#status">Tình trạng nước</a>
            <a href="#system-images">Hình ảnh hệ thống</a>
        </nav>
        
        <div class="auth-buttons">
            <?php if ($is_admin_logged_in): ?>
                <a class="admin-btn" href="admin/dashboard.php">Quản trị</a> 
                <a class="login-btn logout-btn" href="logout.php">Đăng xuất</a> 
            <?php else: ?>
                <a class="login-btn" href="login.php">Đăng nhập</a>
            <?php endif; ?>
        </div>
    </header>

    <main>
        <section id="home" class="hero">
            <div class="hero-text">
                <h1>Hệ Thống Lấy Mẫu Nước Thông Minh</h1>
                <p>
                    Giải pháp giám sát và lấy mẫu nước tự động với công nghệ IoT tiên tiến, 
                    cung cấp dữ liệu thời gian thực về chất lượng nước.
                </p>
                <p>
                    Hệ thống tích hợp cảm biến đa thông số, module lấy mẫu tự động và 
                    nền tảng giám sát từ xa, giúp tối ưu hóa quy trình giám sát chất lượng nước.
                </p>
                <p style="margin-top: 1.5rem; font-weight: 600; color: var(--primary);">
                    Ứng dụng: Giám sát nguồn nước sinh hoạt, nuôi trồng thủy sản, 
                    kiểm soát chất lượng nước công nghiệp và nghiên cứu môi trường.
                </p>
            </div>
            <div class="hero-image-container">
                <img src="images/ploine.jpg" alt="Hệ thống lấy mẫu nước thông minh" class="hero-image">
            </div>
        </section>

        <div class="dashboard-grid">
            <section id="status" class="card">
                <h2>📊 Thông Số Chất Lượng Nước</h2>

                <div class="sensor-box">
                    <div class="sensor" style="border-left-color: var(--primary);">
                        <h3>Mực nước</h3>
                        <p id="level" class="sensor-value">
                            <?php echo number_format($water_level, 1); ?>
                        </p>
                        <span class="sensor-unit">%</span>
                    </div>
                    <div class="sensor">
                        <h3>Nhiệt độ</h3>
                        <p id="temp" class="sensor-value">
                            <?php echo number_format($temperature, 1); ?>
                        </p>
                        <span class="sensor-unit">°C</span>
                    </div>
                    <div class="sensor">
                        <h3>Độ ẩm</h3>
                        <p id="humidity" class="sensor-value">
                            <?php echo number_format($humidity, 1); ?>
                        </p>
                        <span class="sensor-unit">%</span>
                    </div>
                    <div class="sensor">
                        <h3>Độ pH</h3>
                        <p id="ph" class="sensor-value">
                            <?php echo number_format($ph, 2); ?>
                        </p>
                        <span class="sensor-unit">pH</span>
                    </div>
                    <div class="sensor">
                        <h3>Độ đục</h3>
                        <p id="turbidity" class="sensor-value">
                            <?php echo number_format($turbidity, 1); ?>
                        </p>
                        <span class="sensor-unit">NTU</span>
                    </div>
                </div>
                
                <h3>🎯 Ngưỡng Đánh Giá Chất Lượng</h3>
                <div class="indicator-box">
                    <div class="indicator safe">
                        <h3>An toàn</h3>
                        <p><strong>Mực nước 50% - 100%</strong></p>
                        <p><strong>pH 6.5 - 8.5 | Nhiệt độ 22 - 28°C</strong></p>
                        <p>Hệ thống ổn định. Nguồn nước phù hợp sinh hoạt.</p>
                    </div>
                    <div class="indicator warning">
                        <h3>Cảnh báo</h3>
                        <p><strong>Mực nước 10% - 50%</strong></p>
                        <p><strong>pH ngoài 6.0 - 9.0 | Nhiệt độ ngoài 18 - 35°C</strong></p>
                        <p>Cần chuẩn bị bơm (nếu Mực nước thấp). Khuyến cáo hạn chế dùng trong sinh hoạt.</p>
                    </div>
                    <div class="indicator danger">
                        <h3>Nguy hiểm</h3>
                        <p><strong>Mực nước < 10%</strong></p>
                        <p><strong>pH < 5.5 hoặc > 9.5 | Nhiệt độ < 15°C hoặc > 40°C</strong></p>
                        <p>Mực nước cạn, cần xử lý khẩn cấp.</p>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2>💡 Ý Nghĩa Chỉ Số</h2>
                <div style="font-size: 0.95rem;">
                    <h3 style="color: var(--secondary); margin-top: 0; margin-bottom: 0.5rem;">Mực nước (Level)</h3>
                    <p>
                        Thông số chính được đo bằng cảm biến siêu âm, dùng để điều khiển tự động hệ thống lấy mẫu và bơm.
                    </p>
                    <h3 style="color: var(--secondary); margin-top: 1rem; margin-bottom: 0.5rem;">Nhiệt độ (T)</h3>
                    <p>
                        Ảnh hưởng trực tiếp đến khả năng hòa tan <strong>oxy</strong> trong nước. Nhiệt độ quá cao làm giảm oxy hòa tan.
                    </p>
                    <h3 style="color: var(--secondary); margin-top: 1rem; margin-bottom: 0.5rem;">Độ pH</h3>
                    <p>
                        Thể hiện tính axit hoặc kiềm của nước. <strong>pH thấp</strong> gây ăn mòn, <strong>pH cao</strong> ảnh hưởng sức khỏe.
                    </p>
                    <h3 style="color: var(--secondary); margin-top: 1rem; margin-bottom: 0.5rem;">Độ đục (Turbidity)</h3>
                    <p>
                        Đo lượng chất rắn lơ lửng trong nước. Độ đục cao cho thấy nước bị ô nhiễm hoặc có cặn.
                    </p>
                </div>
            </section>
        </div>

        <section id="system-images" class="card">
            <h2>🖼️ Hình Ảnh Hệ Thống</h2>
            <div class="system-images">
                <div class="image-item">
                    <img src="images/ploine.jpg" alt="Hệ thống lấy mẫu nước thông minh">
                    <p>Hệ thống lấy mẫu thực tế</p>
                </div>
                <div class="image-item">
                    <img src="images/pblne.jpg" alt="Cảm biến đo lường">
                    <p>Cảm biến đa thông số</p>
                </div>
                <div class="image-item">
                    <img src="https://via.placeholder.com/500x300/1a73e8/ffffff?text=Module+L%E1%BA%A5y+M%E1%BA%ABu" alt="Module lấy mẫu">
                    <p>Module lấy mẫu tự động</p>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="footer-info">
            <div class="footer-section">
                <h3>Smart Water Sampling</h3>
                <p>Giải pháp lấy mẫu và giám sát chất lượng nước thông minh, tin cậy.</p>
                <p>Hướng tới một tương lai với nguồn nước sạch và an toàn cho mọi người.</p>
            </div>
            <div class="footer-section">
                <h3>Thông tin Liên hệ</h3>
                <p>Điện thoại: <strong>(84) 855-894-446</strong></p>
                <p>Email: <strong>info@smartwatersampling.vn</strong></p>
                <p>Địa chỉ: <strong>Hà Nội, Việt Nam</strong></p>
            </div>
            <div class="footer-section">
                <h3>Kết nối với chúng tôi</h3>
                <div class="socials">
                    <a href="#" title="Facebook">f</a>
                    <a href="#" title="Zalo">z</a>
                    <a href="#" title="Email">@</a>
                    <a href="#" title="Website">🌐</a>
                </div>
            </div>
        </div>
        <p class="copyright">© <?php echo date('Y'); ?> Smart Water Sampling - All rights reserved.</p>
    </footer>

    <script>
        // JavaScript để cập nhật dữ liệu real-time
        const COLOR_SAFE = '#28a745'; // Xanh
        const COLOR_WARNING = '#ffc107'; // Vàng
        const COLOR_DANGER = '#dc3545'; // Đỏ

        async function fetchData(){
            try {
                // Fetch trực tiếp từ data.json (được update.php ghi đè)
                const response = await fetch('data.json'); 
                if (!response.ok) throw new Error('Không thể tải dữ liệu');
                
                const data = await response.json();
                
                // Lấy giá trị từ JSON
                const tempValue = parseFloat(data.temperature) || 0;
                const phValue = parseFloat(data.pH) || 0;
                const levelValue = parseFloat(data.water_level) || 0;
                const humidityValue = parseFloat(data.humidity) || 0;
                const turbidityValue = parseFloat(data.turbidity) || 0;

                // Cập nhật giá trị hiển thị
                document.getElementById('temp').textContent = tempValue.toFixed(1);
                document.getElementById('ph').textContent = phValue.toFixed(2);
                document.getElementById('turbidity').textContent = turbidityValue.toFixed(1);
                document.getElementById('level').textContent = levelValue.toFixed(1);
                document.getElementById('humidity').textContent = humidityValue.toFixed(1);
                
                // Cập nhật màu sắc
                updateSensorColors({
                    temperature: tempValue,
                    pH: phValue,
                    water_level: levelValue
                });
                
            } catch (error) {
                console.error("Lỗi khi fetch dữ liệu:", error);
            }
        }
        
        function updateSensorColors(data) {
            // 1. Màu cho Nhiệt độ
            const tempElement = document.getElementById('temp');
            if (data.temperature >= 22 && data.temperature <= 28) {
                tempElement.style.color = COLOR_SAFE;
            } else if (data.temperature >= 18 && data.temperature <= 35) {
                tempElement.style.color = COLOR_WARNING;
            } else {
                tempElement.style.color = COLOR_DANGER;
            }
            
            // 2. Màu cho pH
            const phElement = document.getElementById('ph');
            if (data.pH >= 6.5 && data.pH <= 8.5) {
                phElement.style.color = COLOR_SAFE;
            } else if (data.pH >= 6.0 && data.pH <= 9.0) {
                phElement.style.color = COLOR_WARNING;
            } else {
                phElement.style.color = COLOR_DANGER;
            }

            // 3. Màu cho Mực nước
            const levelElement = document.getElementById('level');
            if (data.water_level >= 50.0 && data.water_level <= 100.0) {
                levelElement.style.color = COLOR_SAFE;
            } else if (data.water_level >= 10.0) {
                levelElement.style.color = COLOR_WARNING;
            } else {
                levelElement.style.color = COLOR_DANGER;
            }
        }
        
        // Xử lý lỗi ảnh
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img');
            images.forEach(img => {
                img.onerror = function() {
                    this.src = 'https://via.placeholder.com/500x300/1a73e8/ffffff?text=Hệ+thống+Lấy+Mẫu+Nước';
                    this.alt = 'Hình ảnh minh họa hệ thống';
                };
            });
        });
        
        // Cập nhật dữ liệu mỗi 3 giây
        setInterval(fetchData, 3000);
        fetchData();
    </script>
</body>
</html>