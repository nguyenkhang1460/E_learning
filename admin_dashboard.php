<?php
session_start();
include 'db_config.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Lấy thống kê
// 1. Tổng số người dùng (không tính admin)
$sql_total_users = "SELECT COUNT(*) as total FROM users WHERE role != 'admin'";
$result_users = mysqli_query($conn, $sql_total_users);
$total_users = mysqli_fetch_assoc($result_users)['total'];

// 2. Tổng số lớp học
$sql_total_classes = "SELECT COUNT(*) as total FROM classes";
$result_classes = mysqli_query($conn, $sql_total_classes);
$total_classes = mysqli_fetch_assoc($result_classes)['total'];

// 3. Tổng số bài tập
$sql_total_assignments = "SELECT COUNT(*) as total FROM assignments";
$result_assignments = mysqli_query($conn, $sql_total_assignments);
$total_assignments = mysqli_fetch_assoc($result_assignments)['total'];

// 4. Tổng số bài nộp
$sql_total_submissions = "SELECT COUNT(*) as total FROM submissions";
$result_submissions = mysqli_query($conn, $sql_total_submissions);
$total_submissions = mysqli_fetch_assoc($result_submissions)['total'];

// 5. Lấy danh sách người dùng gần nhất
$sql_recent_users = "SELECT id, username, full_name, email, role, created_at 
                     FROM users 
                     WHERE role != 'admin' 
                     ORDER BY created_at DESC 
                     LIMIT 10";
$result_recent_users = mysqli_query($conn, $sql_recent_users);

// 6. Lấy danh sách lớp học gần nhất
$sql_recent_classes = "SELECT c.*, u.full_name as owner_name, 
                       (SELECT COUNT(*) FROM class_members WHERE class_id = c.id) as member_count
                       FROM classes c
                       JOIN users u ON c.owner_id = u.id
                       ORDER BY c.created_at DESC
                       LIMIT 10";
$result_recent_classes = mysqli_query($conn, $sql_recent_classes);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Quản trị Hệ thống</title>
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; transition: 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .card.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .card.green { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .card.orange { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
        .card.purple { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }
        .card h3 { margin: 0 0 10px 0; font-size: 0.9em; opacity: 0.9; }
        .card p { margin: 0; font-size: 2.5em; font-weight: bold; }
        .table-container { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 20px 0; }
        .table-container h2 { margin-top: 0; color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        thead { background: #f8f9fa; }
        th { padding: 12px; text-align: left; color: #7f8c8d; font-weight: 600; border-bottom: 2px solid #dee2e6; }
        td { padding: 12px; border-bottom: 1px solid #f0f0f0; }
        tr:hover { background: #f8f9fa; }
        .badge { padding: 4px 10px; border-radius: 15px; font-size: 0.85em; font-weight: bold; }
        .badge-teacher { background: #3498db; color: white; }
        .badge-student { background: #95a5a6; color: white; }
        .btn-action { padding: 6px 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; margin: 0 2px; transition: 0.3s; }
        .btn-view { background: #3498db; color: white; }
        .btn-view:hover { background: #2980b9; }
        .btn-delete { background: #e74c3c; color: white; }
        .btn-delete:hover { background: #c0392b; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 8px; font-weight: bold; animation: slideDown 0.3s ease-out; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .sidebar h2 { display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Trang chủ</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>
    <div class="main-content">
        <header>
            <h1><i class="fas fa-tachometer-alt"></i> Bảng điều khiển Quản trị viên</h1>
            <p style="color: #7f8c8d; margin-top: 10px;">Xin chào, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
        </header>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <section class="stats">
            <div class="card blue">
                <h3><i class="fas fa-users"></i> Tổng người dùng</h3>
                <p><?php echo number_format($total_users); ?></p>
            </div>
            <div class="card green">
                <h3><i class="fas fa-school"></i> Lớp học hiện có</h3>
                <p><?php echo number_format($total_classes); ?></p>
            </div>
            <div class="card orange">
                <h3><i class="fas fa-tasks"></i> Bài tập đã tạo</h3>
                <p><?php echo number_format($total_assignments); ?></p>
            </div>
            <div class="card purple">
                <h3><i class="fas fa-file-upload"></i> Bài nộp</h3>
                <p><?php echo number_format($total_submissions); ?></p>
            </div>
        </section>

        <section class="table-container">
            <h2><i class="fas fa-user-friends"></i> Người dùng mới nhất</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên đăng nhập</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Vai trò</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result_recent_users) > 0): ?>
                        <?php while($user = mysqli_fetch_assoc($result_recent_users)): ?>
                            <tr>
                                <td><strong>#<?php echo $user['id']; ?></strong></td>
                                <td><i class="fas fa-user"></i> <?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo ($user['role'] == 'teacher') ? 'badge-teacher' : 'badge-student'; ?>">
                                        <i class="fas fa-<?php echo ($user['role'] == 'teacher') ? 'chalkboard-teacher' : 'user-graduate'; ?>"></i>
                                        <?php echo ($user['role'] == 'teacher') ? 'Giáo viên' : 'Học viên'; ?>
                                    </span>
                                </td>
                                <td><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: #95a5a6; padding: 30px;">
                            <i class="fas fa-inbox" style="font-size: 3em; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
                            Chưa có người dùng nào
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="table-container">
            <h2><i class="fas fa-chalkboard-teacher"></i> Lớp học gần nhất</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên lớp</th>
                        <th>Chủ phòng</th>
                        <th>Mã mời</th>
                        <th>Số thành viên</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result_recent_classes) > 0): ?>
                        <?php while($class = mysqli_fetch_assoc($result_recent_classes)): ?>
                            <tr>
                                <td><strong>#<?php echo $class['id']; ?></strong></td>
                                <td><i class="fas fa-school"></i> <strong><?php echo htmlspecialchars($class['class_name']); ?></strong></td>
                                <td><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($class['owner_name']); ?></td>
                                <td>
                                    <code style="background: #f8f9fa; padding: 4px 8px; border-radius: 4px; color: #e74c3c; font-weight: bold;">
                                        <?php echo $class['invite_code']; ?>
                                    </code>
                                </td>
                                <td>
                                    <span style="background: #e3f2fd; color: #1976d2; padding: 4px 10px; border-radius: 12px; font-weight: bold;">
                                        <i class="fas fa-users"></i> <?php echo $class['member_count'] + 1; ?>
                                    </span>
                                </td>
                                <td><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($class['created_at'])); ?></td>
                                <td>
                                    <button class="btn-action btn-view" onclick="location.href='manage_class.php?id=<?php echo $class['id']; ?>'" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; color: #95a5a6; padding: 30px;">
                            <i class="fas fa-inbox" style="font-size: 3em; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
                            Chưa có lớp học nào
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>