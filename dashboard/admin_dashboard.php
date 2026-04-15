<?php
session_start();
require_once __DIR__ . '/../config/db_config.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: /E_learning/modules/auth/login.php");
    exit();
}

// Lấy thông tin admin
$admin_id = $_SESSION['user_id'];
$sql_admin = "SELECT full_name, username FROM users WHERE id = '$admin_id'";
$result_admin = mysqli_query($conn, $sql_admin);
$admin_data = mysqli_fetch_assoc($result_admin);
$admin_name = $admin_data['full_name'] ? $admin_data['full_name'] : $_SESSION['username'];

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

// 5. Tổng số báo cáo lớp học
$sql_total_reports = "SELECT COUNT(*) as total FROM reports WHERE report_type = 'class'";
$result_reports = mysqli_query($conn, $sql_total_reports);
$total_reports = mysqli_fetch_assoc($result_reports)['total'];

// 6. Lấy danh sách người dùng gần nhất
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

$style_css_ver = @filemtime(__DIR__ . '/../assets/css/style.css');
$admin_style_ver = @filemtime(__DIR__ . '/../assets/css/admin_style.css');
$xu_ly_ver = @filemtime(__DIR__ . '/../assets/js/xu_ly.js');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Quản trị Hệ thống</title>
    <link rel="stylesheet" href="/E_learning/assets/css/style.css?v=<?php echo $style_css_ver ?: time(); ?>">
    <link rel="stylesheet" href="/E_learning/assets/css/admin_style.css?v=<?php echo $admin_style_ver ?: time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="admin-dashboard-page">
    <div class="sidebar">
        <h2><i class="fas fa-shield-alt"></i> Admin Dashboard</h2>
        <ul>
            <li><a href="/E_learning/dashboard/admin_dashboard.php"><i class="fas fa-home"></i> Trang chủ</a></li>
            <li><a href="/E_learning/views/view_reports.php"><i class="fas fa-flag"></i> Báo cáo lớp học</a></li>
            <li><a href="/E_learning/modules/auth/change_password.php"><i class="fas fa-key"></i> Đổi mật khẩu</a></li>
            <li><a href="/E_learning/modules/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>
    <div class="main-content">
        <header>
            <h1><i class="fas fa-tachometer-alt"></i> Bảng điều khiển Quản trị viên</h1>
            <p class="admin-welcome-text">Xin chào, <strong><?php echo htmlspecialchars($admin_name); ?></strong></p>
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
            <a href="/E_learning/views/view_reports.php" class="stats-link-card">
                <div class="card card-report">
                    <h3><i class="fas fa-flag"></i> Báo cáo lớp học</h3>
                    <p><?php echo number_format($total_reports); ?></p>
                </div>
            </a>
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
                        <tr><td colspan="6" class="empty-cell">
                            <i class="fas fa-inbox empty-cell-icon"></i>
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
                                    <code class="invite-code-pill">
                                        <?php echo $class['invite_code']; ?>
                                    </code>
                                </td>
                                <td>
                                    <span class="member-count-pill">
                                        <i class="fas fa-users"></i> <?php echo $class['member_count'] + 1; ?>
                                    </span>
                                </td>
                                <td><i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($class['created_at'])); ?></td>
                                <td>
                                    <button class="btn-action btn-view js-admin-open-class" data-class-id="<?php echo $class['id']; ?>" title="Xem chi tiết" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-cell">
                            <i class="fas fa-inbox empty-cell-icon"></i>
                            Chưa có lớp học nào
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
    <script src="/E_learning/assets/js/xu_ly.js?v=<?php echo $xu_ly_ver ?: time(); ?>"></script>
</body>
</html>