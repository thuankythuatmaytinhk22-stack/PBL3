<?php
session_start();
// 1. Cấu hình lỗi và Kết nối
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Đường dẫn từ thư mục admin ra ngoài root để vào database.php
include '../database.php'; 

// --- Kiểm tra đăng nhập và quyền admin ---
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = "";
$message_type = "";
$current_action = $_GET['action'] ?? 'list';
$user_to_edit = null;

// --- 2. Xử lý logic nghiệp vụ (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Xử lý Thêm User
    if (isset($_POST['add_user'])) {
        $username = $conn->real_escape_string($_POST['username']);
        $full_name = $conn->real_escape_string($_POST['full_name'] ?? '');
        $role = $conn->real_escape_string($_POST['role']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Kiểm tra username đã tồn tại chưa
        $check_sql = "SELECT id FROM users WHERE username = '$username'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $message = "Lỗi: Tên đăng nhập đã tồn tại!";
            $message_type = "error";
        } else {
            $sql = "INSERT INTO users (username, full_name, role, password, created_at) 
                    VALUES ('$username', '$full_name', '$role', '$password', NOW())";
            
            if ($conn->query($sql)) {
                $message = "Thêm người dùng mới thành công!";
                $message_type = "success";
                $current_action = 'list';
            } else {
                $message = "Lỗi hệ thống: " . $conn->error;
                $message_type = "error";
            }
        }
    }
    
    // Xử lý Xóa User
    if (isset($_POST['delete_user'])) {
        $id_to_delete = intval($_POST['user_id']);
        if ($id_to_delete == $_SESSION['user_id']) {
            $message = "Bạn không thể tự xóa chính mình!";
            $message_type = "error";
        } else {
            $sql = "DELETE FROM users WHERE id = $id_to_delete";
            if ($conn->query($sql)) {
                $message = "Đã xóa người dùng thành công.";
                $message_type = "success";
            } else {
                $message = "Lỗi khi xóa: " . $conn->error;
                $message_type = "error";
            }
        }
    }
    
    // Xử lý Sửa User
    if (isset($_POST['edit_user'])) {
        $id = intval($_POST['user_id']);
        $full_name = $conn->real_escape_string($_POST['full_name'] ?? '');
        $role = $conn->real_escape_string($_POST['role']);
        
        $sql = "UPDATE users SET full_name = '$full_name', role = '$role' WHERE id = $id";
        
        if ($conn->query($sql)) {
            $message = "Cập nhật thông tin thành công!";
            $message_type = "success";
            $current_action = 'list';
        } else {
            $message = "Lỗi cập nhật: " . $conn->error;
            $message_type = "error";
        }
    }
}

// --- 3. Lấy dữ liệu hiển thị ---
if ($current_action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM users WHERE id = $id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $user_to_edit = $result->fetch_assoc();
    } else {
        $message = "Không tìm thấy người dùng!";
        $message_type = "error";
        $current_action = 'list';
    }
}

// Lấy danh sách users
$sql = "SELECT id, username, full_name, role, created_at FROM users ORDER BY id ASC";
$users_list = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #2c3e50; --accent: #3498db; --bg: #f4f7f6; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--bg); margin: 0; display: flex; }
        
        /* Sidebar dựa theo hình ảnh bạn gửi */
        .sidebar { width: 250px; background: var(--primary); color: white; height: 100vh; position: fixed; }
        .sidebar h2 { padding: 20px; font-size: 18px; border-bottom: 1px solid #3e4f5f; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar ul li a { color: #bdc3c7; text-decoration: none; padding: 15px 20px; display: block; transition: 0.3s; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background: #34495e; color: white; border-left: 4px solid var(--accent); }

        .main-content { margin-left: 250px; padding: 30px; width: 100%; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th, table td { padding: 12px; border: 1px solid #eee; text-align: left; }
        table th { background: #f8f9fa; }
        
        .btn { padding: 8px 15px; border-radius: 4px; border: none; cursor: pointer; text-decoration: none; font-size: 14px; }
        .btn-add { background: #27ae60; color: white; margin-bottom: 15px; display: inline-block; }
        .btn-edit { background: #f39c12; color: white; }
        .btn-delete { background: #e74c3c; color: white; }
        
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        
        .user-count { 
            background: #3498db; 
            color: white; 
            padding: 4px 8px; 
            border-radius: 10px; 
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2><i class="fas fa-microchip"></i> HỆ THỐNG LẤY MẪU</h2>
        <ul>
            <div class="menu-section-title">Tổng quan</div>
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            
            <div class="menu-section-title">Điều khiển</div>
            <li><a href="control.php"><i class="fas fa-toggle-on"></i> Điều khiển & Lịch</a></li>
            <li><a href="set_location.php"><i class="fas fa-map-marker-alt"></i> Vị trí lấy mẫu</a></li>
            <li><a href="logs.php"><i class="fas fa-history"></i> Nhật ký hệ thống</a></li>
            
            <div class="menu-section-title">Hệ thống</div>
            <li><a href="users.php" class="active"><i class="fas fa-users"></i> Quản lý Users <span class="user-count"><?php echo $users_list->num_rows ?? 0; ?></span></a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="card">
            <h1><i class="fas fa-users"></i> Quản lý thành viên</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($current_action === 'list'): ?>
                <a href="?action=add" class="btn btn-add"><i class="fas fa-user-plus"></i> Thêm người dùng</a>
                
                <?php if ($users_list && $users_list->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên đăng nhập</th>
                            <th>Họ tên</th>
                            <th>Quyền</th>
                            <th>Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $users_list->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td>
                                <span style="color: <?php echo $row['role']=='admin'?'red':'blue'; ?>; font-weight: bold;">
                                    <?php echo $row['role'] == 'admin' ? 'ADMIN' : 'USER'; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-edit"><i class="fas fa-edit"></i> Sửa</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này?')">
                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-delete" <?php echo $row['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p style="color: #666; padding: 20px; text-align: center;">Chưa có người dùng nào.</p>
                <?php endif; ?>

            <?php elseif ($current_action === 'add'): ?>
                <h3><i class="fas fa-user-plus"></i> Thêm người dùng mới</h3>
                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Tên đăng nhập</label>
                        <input type="text" name="username" required placeholder="Nhập tên đăng nhập">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Mật khẩu</label>
                        <input type="password" name="password" required placeholder="Nhập mật khẩu">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> Họ và tên</label>
                        <input type="text" name="full_name" placeholder="Nhập họ và tên">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user-tag"></i> Vai trò</label>
                        <select name="role">
                            <option value="user">Người dùng (User)</option>
                            <option value="admin">Quản trị viên (Admin)</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="add_user" class="btn btn-add">
                            <i class="fas fa-save"></i> Lưu thông tin
                        </button>
                        <a href="users.php" class="btn" style="background: #95a5a6; color: white;">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </form>

            <?php elseif ($current_action === 'edit' && $user_to_edit): ?>
                <h3><i class="fas fa-edit"></i> Sửa thông tin người dùng</h3>
                <form method="POST">
                    <input type="hidden" name="user_id" value="<?php echo $user_to_edit['id']; ?>">
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Tên đăng nhập</label>
                        <input type="text" value="<?php echo htmlspecialchars($user_to_edit['username']); ?>" disabled>
                        <small style="color: #666;">Tên đăng nhập không thể thay đổi</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> Họ và tên</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_to_edit['full_name']); ?>" placeholder="Nhập họ và tên">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user-tag"></i> Vai trò</label>
                        <select name="role">
                            <option value="user" <?php echo $user_to_edit['role'] == 'user' ? 'selected' : ''; ?>>Người dùng (User)</option>
                            <option value="admin" <?php echo $user_to_edit['role'] == 'admin' ? 'selected' : ''; ?>>Quản trị viên (Admin)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Ngày tạo</label>
                        <input type="text" value="<?php echo date('d/m/Y H:i:s', strtotime($user_to_edit['created_at'])); ?>" disabled>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="edit_user" class="btn btn-add">
                            <i class="fas fa-save"></i> Cập nhật
                        </button>
                        <a href="users.php" class="btn" style="background: #95a5a6; color: white;">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Xác nhận trước khi xóa
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (this.querySelector('button[name="delete_user"]')) {
                    if (!confirm('Bạn có chắc chắn muốn xóa người dùng này?')) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>