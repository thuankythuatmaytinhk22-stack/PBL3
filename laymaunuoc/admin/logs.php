<?php
// admin/logs.php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
include '../database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Biến thông báo
$success_message = '';
$error_message = '';

// XỬ LÝ XÓA TỪNG NHẬT KÝ
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Xóa nhật ký
    $stmt = $conn->prepare("DELETE FROM pump_logs WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $success_message = "✅ Đã xóa nhật ký #$delete_id thành công";
        // Redirect để tránh F5 xóa lại
        header("Location: logs.php?success=" . urlencode($success_message));
        exit();
    } else {
        $error_message = "❌ Lỗi khi xóa nhật ký #$delete_id";
    }
}

// XỬ LÝ XÓA NHIỀU NHẬT KÝ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Xóa các nhật ký được chọn
    if (isset($_POST['delete_selected'])) {
        $selected_ids = isset($_POST['selected_logs']) ? $_POST['selected_logs'] : [];
        
        if (!empty($selected_ids)) {
            // Tạo chuỗi placeholders
            $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
            $types = str_repeat('i', count($selected_ids));
            
            $stmt = $conn->prepare("DELETE FROM pump_logs WHERE id IN ($placeholders)");
            $stmt->bind_param($types, ...$selected_ids);
            
            if ($stmt->execute()) {
                $deleted_count = $stmt->affected_rows;
                $success_message = "✅ Đã xóa $deleted_count nhật ký được chọn";
                header("Location: logs.php?success=" . urlencode($success_message));
                exit();
            } else {
                $error_message = "❌ Lỗi khi xóa nhật ký được chọn";
            }
        } else {
            $error_message = "❌ Vui lòng chọn ít nhất một nhật ký để xóa";
        }
    }
    
    // 2. Xóa theo bộ lọc hiện tại
    elseif (isset($_POST['delete_filtered'])) {
        $filter_pump = isset($_POST['filter_pump']) ? (int)$_POST['filter_pump'] : 0;
        $filter_action = isset($_POST['filter_action']) ? $_POST['filter_action'] : '';
        $filter_date = isset($_POST['filter_date']) ? $_POST['filter_date'] : '';
        
        // Xây dựng WHERE clause
        $where_conditions = [];
        $params = [];
        $types = '';
        
        if ($filter_pump > 0) {
            $where_conditions[] = "pump_number = ?";
            $params[] = $filter_pump;
            $types .= 'i';
        }
        
        if ($filter_action !== '') {
            $where_conditions[] = "action = ?";
            $params[] = $filter_action;
            $types .= 's';
        }
        
        if ($filter_date !== '') {
            $where_conditions[] = "DATE(created_at) = ?";
            $params[] = $filter_date;
            $types .= 's';
        }
        
        $where_sql = '';
        if (!empty($where_conditions)) {
            $where_sql = "WHERE " . implode(' AND ', $where_conditions);
        }
        
        // Thực hiện xóa
        $delete_sql = "DELETE FROM pump_logs $where_sql";
        $stmt = $conn->prepare($delete_sql);
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if ($stmt->execute()) {
            $deleted_rows = $stmt->affected_rows;
            $success_message = "✅ Đã xóa $deleted_rows nhật ký theo bộ lọc hiện tại";
            header("Location: logs.php?success=" . urlencode($success_message));
            exit();
        } else {
            $error_message = "❌ Lỗi khi xóa nhật ký theo bộ lọc";
        }
    }
    
    // 3. Xóa tất cả
    elseif (isset($_POST['delete_all'])) {
        $confirm = isset($_POST['confirm_delete_all']) ? $_POST['confirm_delete_all'] : '';
        
        if ($confirm === 'DELETE_ALL_LOGS') {
            $result = $conn->query("DELETE FROM pump_logs");
            if ($result) {
                $success_message = "✅ Đã xóa TẤT CẢ nhật ký hệ thống";
                header("Location: logs.php?success=" . urlencode($success_message));
                exit();
            } else {
                $error_message = "❌ Lỗi khi xóa tất cả nhật ký";
            }
        } else {
            $error_message = "❌ Vui lòng nhập đúng xác nhận 'DELETE_ALL_LOGS'";
        }
    }
    
    // 4. Xóa nhật ký cũ (trước ngày chỉ định)
    elseif (isset($_POST['delete_old'])) {
        $old_date = isset($_POST['old_date']) ? $_POST['old_date'] : '';
        $confirm = isset($_POST['confirm_delete_old']) ? $_POST['confirm_delete_old'] : '';
        
        if (empty($old_date)) {
            $error_message = "❌ Vui lòng chọn ngày";
        } elseif ($confirm !== 'CONFIRM') {
            $error_message = "❌ Vui lòng nhập 'CONFIRM' để xác nhận";
        } else {
            $stmt = $conn->prepare("DELETE FROM pump_logs WHERE DATE(created_at) < ?");
            $stmt->bind_param("s", $old_date);
            
            if ($stmt->execute()) {
                $deleted_rows = $stmt->affected_rows;
                $success_message = "✅ Đã xóa $deleted_rows nhật ký trước ngày $old_date";
                header("Location: logs.php?success=" . urlencode($success_message));
                exit();
            } else {
                $error_message = "❌ Lỗi khi xóa nhật ký cũ";
            }
        }
    }
}

// Kiểm tra thông báo từ URL
if (isset($_GET['success'])) {
    $success_message = urldecode($_GET['success']);
}

// Lấy thống kê cho sidebar badge
$total_users = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM users");
if ($result) {
    $total_users = $result->fetch_assoc()['total'];
}

// Xử lý bộ lọc
$filter_pump = isset($_GET['pump']) ? (int)$_GET['pump'] : 0;
$filter_action = isset($_GET['action']) ? $_GET['action'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// Xây dựng truy vấn với bộ lọc - JOIN với pump_locations để lấy tên vị trí
$where_conditions = [];
$params = [];
$types = '';

if ($filter_pump > 0) {
    $where_conditions[] = "l.pump_number = ?";
    $params[] = $filter_pump;
    $types .= 'i';
}

if ($filter_action !== '') {
    $where_conditions[] = "l.action = ?";
    $params[] = $filter_action;
    $types .= 's';
}

if ($filter_date !== '') {
    $where_conditions[] = "DATE(l.created_at) = ?";
    $params[] = $filter_date;
    $types .= 's';
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(' AND ', $where_conditions);
}

// Truy vấn logs với phân trang - JOIN với pump_locations
$items_per_page = 20;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Đếm tổng số bản ghi
$count_sql = "SELECT COUNT(*) as total FROM pump_logs l $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_count = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_count / $items_per_page);

// Lấy dữ liệu logs với tên vị trí từ pump_locations
$sql = "SELECT l.*, 
               p.location_name as pump_location_name,
               p.current_lat as location_lat,
               p.current_lng as location_lng
        FROM pump_logs l
        LEFT JOIN pump_locations p ON l.pump_number = p.pump_number
        $where_sql 
        ORDER BY l.created_at DESC 
        LIMIT ? OFFSET ?";
        
$params[] = $items_per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs_result = $stmt->get_result();
$logs = [];
while ($row = $logs_result->fetch_assoc()) {
    // Nếu không có tên vị trí từ pump_locations, dùng location_name từ pump_logs
    if (empty($row['pump_location_name']) && !empty($row['location_name'])) {
        $row['pump_location_name'] = $row['location_name'];
    }
    // Nếu không có tọa độ từ pump_locations, dùng từ pump_logs
    if (empty($row['location_lat']) && !empty($row['location_lat_from_logs'])) {
        $row['location_lat'] = $row['location_lat_from_logs'];
    }
    if (empty($row['location_lng']) && !empty($row['location_lng_from_logs'])) {
        $row['location_lng'] = $row['location_lng_from_logs'];
    }
    $logs[] = $row;
}

// Lấy thống kê
$stats_sql = "SELECT 
    COUNT(*) as total_logs,
    COUNT(DISTINCT DATE(created_at)) as total_days,
    SUM(CASE WHEN action = 'ON' THEN 1 ELSE 0 END) as total_on,
    SUM(CASE WHEN action = 'OFF' THEN 1 ELSE 0 END) as total_off
FROM pump_logs";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Lấy thống kê dung lượng database (ước tính)
$size_sql = "SELECT 
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
    AND table_name = 'pump_logs'";
$size_result = $conn->query($size_sql);
$db_size = $size_result ? $size_result->fetch_assoc()['size_mb'] : 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhật ký hệ thống - Hệ thống quản trị</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f6fa; display: flex; min-height: 100vh; }

        /* SIDEBAR ĐỒNG BỘ */
        .sidebar { width: 280px; background: #2c3e50; color: white; height: 100vh; position: fixed; overflow-y: auto; }
        .sidebar-header { padding: 25px 20px; background: #34495e; text-align: center; border-bottom: 1px solid #465a6d; }
        .sidebar-menu { list-style: none; padding: 0; }
        .menu-section-title { padding: 15px 20px 5px; color: #95a5a6; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 20px; color: #bdc3c7; text-decoration: none; transition: 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #34495e; color: white; border-left: 4px solid #3498db; }
        .sidebar-menu i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-badge { background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; margin-left: auto; }

        /* MAIN CONTENT */
        .main-content { margin-left: 280px; padding: 25px; width: calc(100% - 280px); }
        .header-box { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }

        /* THÔNG BÁO */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* STATS CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .stat-card h4 { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 10px; }
        .stat-number { font-size: 1.8rem; font-weight: bold; color: #2c3e50; }
        .stat-size { color: #e74c3c; font-weight: bold; }

        /* FILTER FORM */
        .filter-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: flex-end; }
        .form-group label { display: block; margin-bottom: 5px; color: #2c3e50; font-weight: 600; font-size: 0.9rem; }
        .form-group select, .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem; }
        .btn-filter { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-reset { background: #95a5a6; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; text-align: center; }

        /* DELETE PANEL */
        .delete-panel { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; border-left: 4px solid #e74c3c; }
        .delete-panel h3 { color: #e74c3c; margin-bottom: 15px; }
        .delete-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .delete-option { padding: 15px; border: 1px solid #eee; border-radius: 8px; }
        .delete-option h4 { margin-bottom: 10px; color: #2c3e50; }
        .delete-option p { color: #666; font-size: 0.9rem; margin-bottom: 15px; }
        .btn-delete { background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; width: 100%; }
        .btn-delete:hover { background: #c0392b; }
        .confirm-input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; }
        .confirm-note { font-size: 0.8rem; color: #888; margin-top: 5px; }

        /* LOGS TABLE */
        .logs-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f8f9fa; color: #7f8c8d; font-weight: 600; text-align: left; padding: 15px; border-bottom: 2px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: top; }
        tr:hover { background: #f9f9f9; }
        
        /* Checkbox và hàng được chọn */
        .select-all-container { margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-radius: 6px; }
        .row-selected { background: #fff3cd !important; }
        .checkbox-cell { width: 40px; text-align: center; }
        
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; }
        .badge-pump1 { background: #e3f2fd; color: #1976d2; }
        .badge-pump2 { background: #f3e5f5; color: #7b1fa2; }
        .badge-both { background: #e8f5e8; color: #388e3c; }
        .badge-on { background: #d4edda; color: #155724; }
        .badge-off { background: #f8d7da; color: #721c24; }
        .badge-auto { background: #fff3cd; color: #856404; }
        
        .location-cell { max-width: 200px; }
        .location-coords { font-size: 0.8rem; color: #666; margin-top: 3px; }
        
        /* NÚT XÓA TỪNG DÒNG */
        .action-cell { width: 100px; }
        .btn-delete-single { 
            background: #e74c3c; 
            color: white; 
            padding: 6px 12px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
        }
        .btn-delete-single:hover { 
            background: #c0392b;
            transform: scale(1.05);
        }
        
        /* NÚT XÓA NHIỀU */
        .bulk-actions { 
            display: flex; 
            gap: 10px; 
            margin-bottom: 15px; 
            padding: 15px; 
            background: #f8f9fa; 
            border-radius: 8px; 
            align-items: center;
        }
        .btn-bulk-delete { 
            background: #e74c3c; 
            color: white; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-bulk-delete:hover { background: #c0392b; }
        .btn-bulk-delete:disabled { 
            background: #95a5a6; 
            cursor: not-allowed; 
            opacity: 0.6;
        }
        .selected-count { 
            color: #e74c3c; 
            font-weight: bold; 
            margin-left: auto;
        }
        
        /* PAGINATION */
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 25px; }
        .pagination a, .pagination span { padding: 8px 12px; border-radius: 6px; text-decoration: none; }
        .pagination a { background: #3498db; color: white; }
        .pagination a:hover { background: #2980b9; }
        .pagination span.current { background: #2c3e50; color: white; }
        .pagination span.disabled { background: #ecf0f1; color: #95a5a6; cursor: not-allowed; }
        
        .btn-web { background: #3498db; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 0.9rem; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-web:hover { background: #2980b9; }
        .no-data { text-align: center; padding: 40px; color: #95a5a6; }
        
        .location-source { 
            font-size: 0.7rem; 
            color: #888; 
            background: #f0f0f0; 
            padding: 2px 6px; 
            border-radius: 3px;
            margin-top: 3px;
            display: inline-block;
        }
        
        .coord-display {
            font-family: monospace;
            font-size: 0.8rem;
            color: #444;
        }
        
        /* MODAL XÁC NHẬN XÓA */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-confirm { background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; }
        .btn-cancel { background: #95a5a6; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-microchip"></i> HỆ THỐNG LẤY MẪU</h3>
        </div>
        <ul class="sidebar-menu">
            <div class="menu-section-title">Tổng quan</div>
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            
            <div class="menu-section-title">Điều khiển</div>
            <li><a href="control.php"><i class="fas fa-toggle-on"></i> Điều khiển & Lịch</a></li>
            <li><a href="set_location.php"><i class="fas fa-map-marker-alt"></i> Vị trí lấy mẫu</a></li>
            <li><a href="logs.php" class="active"><i class="fas fa-history"></i> Nhật ký hệ thống</a></li>
            
            <div class="menu-section-title">Hệ thống</div>
            <li><a href="users.php"><i class="fas fa-users"></i> Quản lý Users <span class="menu-badge"><?php echo $total_users; ?></span></a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header-box">
            <div>
                <h1><i class="fas fa-history"></i> Nhật ký hệ thống</h1>
                <small>Chào mừng trở lại, <strong><?php echo $_SESSION['username']; ?></strong></small>
            </div>
            <a href="../index.php" class="btn-web"><i class="fas fa-external-link-alt"></i> Xem Trang Web</a>
        </div>

        <!-- THÔNG BÁO -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- THỐNG KÊ -->
        <div class="stats-grid">
            <div class="stat-card">
                <h4><i class="fas fa-list"></i> Tổng nhật ký</h4>
                <div class="stat-number"><?php echo number_format($stats['total_logs']); ?></div>
            </div>
            <div class="stat-card">
                <h4><i class="fas fa-calendar"></i> Số ngày ghi nhận</h4>
                <div class="stat-number"><?php echo number_format($stats['total_days']); ?></div>
            </div>
            <div class="stat-card">
                <h4><i class="fas fa-play"></i> Lệnh BẬT</h4>
                <div class="stat-number"><?php echo number_format($stats['total_on']); ?></div>
            </div>
            <div class="stat-card">
                <h4><i class="fas fa-stop"></i> Lệnh TẮT</h4>
                <div class="stat-number"><?php echo number_format($stats['total_off']); ?></div>
            </div>
            <div class="stat-card">
                <h4><i class="fas fa-database"></i> Dung lượng</h4>
                <div class="stat-number stat-size"><?php echo $db_size; ?> MB</div>
            </div>
        </div>

        <!-- PANEL XÓA NHẬT KÝ -->
        <div class="delete-panel">
            <h3><i class="fas fa-trash-alt"></i> Quản lý xóa nhật ký</h3>
            <div class="delete-options">
                <!-- Xóa theo bộ lọc -->
                <div class="delete-option">
                    <h4><i class="fas fa-filter"></i> Xóa theo bộ lọc hiện tại</h4>
                    <p>Xóa các nhật ký đang được hiển thị theo bộ lọc hiện tại</p>
                    <form method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa <?php echo $total_count; ?> nhật ký theo bộ lọc hiện tại?');">
                        <input type="hidden" name="filter_pump" value="<?php echo $filter_pump; ?>">
                        <input type="hidden" name="filter_action" value="<?php echo $filter_action; ?>">
                        <input type="hidden" name="filter_date" value="<?php echo $filter_date; ?>">
                        <button type="submit" name="delete_filtered" class="btn-delete">
                            <i class="fas fa-trash"></i> XÓA (<?php echo $total_count; ?> bản ghi)
                        </button>
                        <p class="confirm-note">Hành động này không thể hoàn tác!</p>
                    </form>
                </div>

                <!-- Xóa nhật ký cũ -->
                <div class="delete-option">
                    <h4><i class="fas fa-calendar-times"></i> Xóa nhật ký cũ</h4>
                    <p>Xóa nhật ký trước một ngày cụ thể</p>
                    <form method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa nhật ký trước ngày này?');">
                        <label>Chọn ngày:</label>
                        <input type="date" name="old_date" class="confirm-input" required>
                        <label>Nhập "CONFIRM" để xác nhận:</label>
                        <input type="text" name="confirm_delete_old" class="confirm-input" placeholder="CONFIRM" required>
                        <button type="submit" name="delete_old" class="btn-delete">
                            <i class="fas fa-trash"></i> XÓA NHẬT KÝ CŨ
                        </button>
                        <p class="confirm-note">Ví dụ: chọn 2024-01-01 để xóa nhật ký trước 01/01/2024</p>
                    </form>
                </div>

                <!-- Xóa tất cả -->
                <div class="delete-option">
                    <h4><i class="fas fa-skull-crossbones"></i> Xóa TẤT CẢ nhật ký</h4>
                    <p>Xóa toàn bộ nhật ký trong hệ thống (<?php echo number_format($stats['total_logs']); ?> bản ghi)</p>
                    <form method="POST" onsubmit="return confirm('Bạn có CHẮC CHẮN muốn xóa TẤT CẢ nhật ký? Hành động này KHÔNG THỂ HOÀN TÁC!');">
                        <label>Nhập "DELETE_ALL_LOGS" để xác nhận:</label>
                        <input type="text" name="confirm_delete_all" class="confirm-input" placeholder="DELETE_ALL_LOGS" required>
                        <button type="submit" name="delete_all" class="btn-delete" style="background: #c0392b;">
                            <i class="fas fa-bomb"></i> XÓA TẤT CẢ
                        </button>
                        <p class="confirm-note" style="color: #c0392b; font-weight: bold;">
                            <i class="fas fa-exclamation-triangle"></i> CẢNH BÁO: Hành động này sẽ xóa vĩnh viễn tất cả nhật ký!
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <!-- BỘ LỌC -->
        <div class="filter-card">
            <h3 style="margin-bottom: 15px;"><i class="fas fa-filter"></i> Bộ lọc</h3>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label><i class="fas fa-faucet-drip"></i> Bơm</label>
                    <select name="pump">
                        <option value="0">Tất cả bơm</option>
                        <option value="1" <?php echo $filter_pump == 1 ? 'selected' : ''; ?>>Bơm 1</option>
                        <option value="2" <?php echo $filter_pump == 2 ? 'selected' : ''; ?>>Bơm 2</option>
                        <option value="3" <?php echo $filter_pump == 3 ? 'selected' : ''; ?>>Cả 2 bơm</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-bolt"></i> Hành động</label>
                    <select name="action">
                        <option value="">Tất cả hành động</option>
                        <option value="ON" <?php echo $filter_action == 'ON' ? 'selected' : ''; ?>>Bật bơm</option>
                        <option value="OFF" <?php echo $filter_action == 'OFF' ? 'selected' : ''; ?>>Tắt bơm</option>
                        <option value="AUTO" <?php echo $filter_action == 'AUTO' ? 'selected' : ''; ?>>Tự động</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Ngày</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i> Lọc kết quả
                    </button>
                </div>
                
                <div class="form-group">
                    <a href="logs.php" class="btn-reset">
                        <i class="fas fa-redo"></i> Đặt lại
                    </a>
                </div>
            </form>
        </div>

        <!-- BẢNG NHẬT KÝ -->
        <div class="logs-card">
            <h3 style="margin-bottom: 15px;"><i class="fas fa-list-alt"></i> Danh sách nhật ký (<?php echo number_format($total_count); ?> bản ghi)</h3>
            
            <!-- BULK ACTIONS -->
            <form id="bulkForm" method="POST" onsubmit="return confirmBulkDelete()">
                <div class="bulk-actions">
                    <div class="select-all-container">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        <label for="selectAll" style="margin-left: 5px; font-weight: 600;">Chọn tất cả</label>
                    </div>
                    
                    <button type="submit" name="delete_selected" class="btn-bulk-delete" id="bulkDeleteBtn" disabled>
                        <i class="fas fa-trash"></i> Xóa nhật ký đã chọn (<span id="selectedCount">0</span>)
                    </button>
                    
                    <div class="selected-count" id="selectedCountText">
                        Đã chọn: <span id="selectedCountNum">0</span> nhật ký
                    </div>
                </div>
            
            <?php if (empty($logs)): ?>
                <div class="no-data">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                    <h3>Không có dữ liệu nhật ký</h3>
                    <p>Chưa có hành động nào được ghi nhận</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th class="checkbox-cell">Chọn</th>
                            <th>Thời gian</th>
                            <th>Bơm</th>
                            <th>Hành động</th>
                            <th>Thời lượng</th>
                            <th>Vị trí lấy mẫu</th>
                            <th>Tọa độ hiện tại</th>
                            <th class="action-cell">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr id="row-<?php echo $log['id']; ?>">
                            <td class="checkbox-cell">
                                <input type="checkbox" name="selected_logs[]" value="<?php echo $log['id']; ?>" 
                                       class="row-checkbox" onchange="updateSelectedCount()">
                            </td>
                            <td>
                                <strong><?php echo date('H:i:s', strtotime($log['created_at'])); ?></strong><br>
                                <small style="color: #666;"><?php echo date('d/m/Y', strtotime($log['created_at'])); ?></small>
                            </td>
                            <td>
                                <?php if ($log['pump_number'] == 1): ?>
                                    <span class="badge badge-pump1">Bơm 1</span>
                                <?php elseif ($log['pump_number'] == 2): ?>
                                    <span class="badge badge-pump2">Bơm 2</span>
                                <?php else: ?>
                                    <span class="badge badge-both">Cả 2 bơm</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['action'] == 'ON'): ?>
                                    <span class="badge badge-on">
                                        <i class="fas fa-play"></i> BẬT
                                    </span>
                                <?php elseif ($log['action'] == 'OFF'): ?>
                                    <span class="badge badge-off">
                                        <i class="fas fa-stop"></i> TẮT
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-auto">
                                        <i class="fas fa-robot"></i> TỰ ĐỘNG
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['duration']): ?>
                                    <strong><?php echo $log['duration']; ?></strong> giây
                                <?php else: ?>
                                    <span style="color: #95a5a6;">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="location-cell">
                                <?php 
                                // Hiển thị tên vị trí từ pump_locations hoặc từ pump_logs
                                $location_name = !empty($log['pump_location_name']) ? $log['pump_location_name'] : 
                                               (!empty($log['location_name']) ? $log['location_name'] : '');
                                
                                if (!empty($location_name)): ?>
                                    <strong><?php echo htmlspecialchars($location_name); ?></strong>
                                    <?php if (!empty($log['pump_location_name'])): ?>
                                        <span class="location-source">Vị trí hiện tại</span>
                                    <?php else: ?>
                                        <span class="location-source">Từ nhật ký</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #95a5a6;">Không có thông tin</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($log['location_lat']) && !empty($log['location_lng'])): ?>
                                    <a href="https://maps.google.com/?q=<?php echo $log['location_lat']; ?>,<?php echo $log['location_lng']; ?>" 
                                       target="_blank" 
                                       style="color: #3498db; text-decoration: none;">
                                        <i class="fas fa-external-link-alt"></i> Xem bản đồ
                                    </a><br>
                                    <div class="coord-display">
                                        <small>Lat: <?php echo number_format($log['location_lat'], 6); ?></small><br>
                                        <small>Lng: <?php echo number_format($log['location_lng'], 6); ?></small>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #95a5a6;">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-cell">
                                <button type="button" class="btn-delete-single" 
                                        onclick="confirmDeleteSingle(<?php echo $log['id']; ?>, '<?php echo date('H:i:s d/m/Y', strtotime($log['created_at'])); ?>')">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </form> <!-- Kết thúc form bulk -->

                <!-- PHÂN TRANG -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <!-- Nút Previous -->
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?>&pump=<?php echo $filter_pump; ?>&action=<?php echo $filter_action; ?>&date=<?php echo $filter_date; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>

                    <!-- Các trang -->
                    <?php 
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="?page=1&pump=<?php echo $filter_pump; ?>&action=<?php echo $filter_action; ?>&date=<?php echo $filter_date; ?>">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="disabled">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&pump=<?php echo $filter_pump; ?>&action=<?php echo $filter_action; ?>&date=<?php echo $filter_date; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="disabled">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?>&pump=<?php echo $filter_pump; ?>&action=<?php echo $filter_action; ?>&date=<?php echo $filter_date; ?>">
                            <?php echo $total_pages; ?>
                        </a>
                    <?php endif; ?>

                    <!-- Nút Next -->
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?>&pump=<?php echo $filter_pump; ?>&action=<?php echo $filter_action; ?>&date=<?php echo $filter_date; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL XÁC NHẬN XÓA TỪNG NHẬT KÝ -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-exclamation-triangle" style="color: #e74c3c;"></i> Xác nhận xóa nhật ký</h3>
            <p id="modalMessage">Bạn có chắc muốn xóa nhật ký này?</p>
            <div class="modal-buttons">
                <button type="button" class="btn-cancel" onclick="closeModal()">Hủy</button>
                <button type="button" class="btn-confirm" id="confirmDeleteBtn">Xóa</button>
            </div>
        </div>
    </div>

    <script>
        // Biến toàn cục
        let deleteLogId = null;
        
        // Mở modal xác nhận xóa từng nhật ký
        function confirmDeleteSingle(logId, logTime) {
            deleteLogId = logId;
            document.getElementById('modalMessage').innerHTML = 
                `Bạn có chắc muốn xóa nhật ký lúc <strong>${logTime}</strong>?`;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        // Đóng modal
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteLogId = null;
        }
        
        // Xác nhận xóa từng nhật ký
        document.getElementById('confirmDeleteBtn').onclick = function() {
            if (deleteLogId) {
                window.location.href = `logs.php?delete_id=${deleteLogId}`;
            }
        };
        
        // Đóng modal khi click bên ngoài
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                closeModal();
            }
        };
        
        // QUẢN LÝ CHECKBOX
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const rows = document.querySelectorAll('tbody tr');
            
            checkboxes.forEach((checkbox, index) => {
                checkbox.checked = selectAll.checked;
                // Highlight hàng được chọn
                if (selectAll.checked) {
                    rows[index].classList.add('row-selected');
                } else {
                    rows[index].classList.remove('row-selected');
                }
            });
            
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const selectedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
            const rows = document.querySelectorAll('tbody tr');
            
            // Cập nhật số lượng đã chọn
            document.getElementById('selectedCount').textContent = selectedCount;
            document.getElementById('selectedCountNum').textContent = selectedCount;
            
            // Enable/disable nút xóa nhiều
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            bulkDeleteBtn.disabled = selectedCount === 0;
            
            // Highlight hàng được chọn
            checkboxes.forEach((checkbox, index) => {
                if (checkbox.checked) {
                    rows[index].classList.add('row-selected');
                } else {
                    rows[index].classList.remove('row-selected');
                }
            });
            
            // Cập nhật checkbox "Chọn tất cả"
            const selectAll = document.getElementById('selectAll');
            selectAll.checked = selectedCount === checkboxes.length && checkboxes.length > 0;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
        }
        
        // Xác nhận xóa nhiều
        function confirmBulkDelete() {
            const selectedCount = document.getElementById('selectedCountNum').textContent;
            return confirm(`Bạn có chắc muốn xóa ${selectedCount} nhật ký đã chọn?`);
        }
        
        // Tự động reload logs mỗi 30 giây để cập nhật mới nhất
        setInterval(function() {
            if (!document.hidden) {
                // Chỉ reload nếu đang ở trang đầu tiên và không có bộ lọc
                if (<?php echo $current_page == 1 && empty($where_conditions) ? 'true' : 'false'; ?>) {
                    window.location.reload();
                }
            }
        }, 30000);
        
        // Hiển thị thông báo khi có log mới
        let lastLogId = <?php echo !empty($logs) ? $logs[0]['id'] : 0; ?>;
        
        function checkNewLogs() {
            fetch('check_new_logs.php?last_id=' + lastLogId)
                .then(response => response.json())
                .then(data => {
                    if (data.count > 0) {
                        // Hiển thị thông báo
                        if (Notification.permission === "granted") {
                            new Notification("Có " + data.count + " log mới", {
                                body: "Nhật ký hệ thống vừa được cập nhật",
                                icon: "/favicon.ico"
                            });
                        } else if (Notification.permission !== "denied") {
                            Notification.requestPermission().then(permission => {
                                if (permission === "granted") {
                                    new Notification("Có " + data.count + " log mới", {
                                        body: "Nhật ký hệ thống vừa được cập nhật",
                                        icon: "/favicon.ico"
                                    });
                                }
                            });
                        }
                        
                        // Reload page nếu đang ở trang đầu
                        if (<?php echo $current_page == 1 ? 'true' : 'false'; ?>) {
                            window.location.reload();
                        }
                    }
                });
        }
        
        // Kiểm tra log mới mỗi 10 giây
        setInterval(checkNewLogs, 10000);
        
        // Xử lý checkbox khi click vào hàng
        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('click', function(e) {
                // Không bắt sự kiện khi click vào checkbox hoặc nút xóa
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'BUTTON' || 
                    e.target.closest('button') || e.target.closest('a')) {
                    return;
                }
                
                const checkbox = this.querySelector('.row-checkbox');
                if (checkbox) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>