<?php
session_start();
include 'db_config.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Lấy danh sách báo cáo lớp học
$sql_reports = "SELECT r.*, u.full_name as reporter_name, u.email as reporter_email, c.class_name, c.owner_id,
                owner.full_name as owner_name
                FROM reports r
                JOIN users u ON r.user_id = u.id
                LEFT JOIN classes c ON r.target_id = c.id AND r.report_type = 'class'
                LEFT JOIN users owner ON c.owner_id = owner.id
                WHERE r.report_type = 'class'
                ORDER BY r.created_at DESC";
$result_reports = mysqli_query($conn, $sql_reports);

// Xử lý xóa báo cáo
if (isset($_GET['delete_report'])) {
    $report_id = (int)$_GET['delete_report'];
    $sql_delete = "DELETE FROM reports WHERE id = $report_id";
    if (mysqli_query($conn, $sql_delete)) {
        $_SESSION['success'] = 'Đã xóa báo cáo thành công!';
    } else {
        $_SESSION['error'] = 'Lỗi khi xóa báo cáo!';
    }
    header("Location: view_reports.php");
    exit();
}

// Xử lý đánh dấu đã xem
if (isset($_GET['mark_viewed'])) {
    $report_id = (int)$_GET['mark_viewed'];
    $sql_update = "UPDATE reports SET status = 'reviewed' WHERE id = $report_id";
    mysqli_query($conn, $sql_update);
    header("Location: view_reports.php");
    exit();
}

$style_css_ver = @filemtime(__DIR__ . '/style.css');
$admin_style_ver = @filemtime(__DIR__ . '/admin_style.css');
$xu_ly_ver = @filemtime(__DIR__ . '/xu_ly.js');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Báo cáo - Admin Dashboard</title>
    <link rel="stylesheet" href="style.css?v=<?php echo $style_css_ver ?: time(); ?>">
    <link rel="stylesheet" href="admin_style.css?v=<?php echo $admin_style_ver ?: time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="view-reports-page">
    <div class="sidebar">
        <h2><i class="fas fa-shield-alt"></i> Admin Dashboard</h2>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fas fa-home"></i> Trang chủ</a></li>
            <li><a href="view_reports.php" class="active"><i class="fas fa-flag"></i> Báo cáo lớp học</a></li>
            <li><a href="change_password.php"><i class="fas fa-key"></i> Đổi mật khẩu</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <header>
            <h1><i class="fas fa-flag"></i> Quản lý Báo cáo Lớp học</h1>
            <p class="reports-header-desc">Danh sách các báo cáo từ học viên về các lớp học</p>
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
        
        <div class="stats-banner">
            <h2><i class="fas fa-chart-line"></i> Thống kê báo cáo</h2>
            <p>Tổng số báo cáo: <strong><?php echo mysqli_num_rows($result_reports); ?></strong> báo cáo</p>
        </div>
        
        <?php if (mysqli_num_rows($result_reports) > 0): ?>
            <?php while($report = mysqli_fetch_assoc($result_reports)): ?>
                <div class="report-card">
                    <div class="report-header">
                        <div class="report-info">
                            <div class="report-title">
                                <i class="fas fa-school"></i> <?php echo htmlspecialchars($report['class_name'] ?? 'Lớp học không tồn tại'); ?>
                            </div>
                            <div class="report-meta">
                                <span><i class="fas fa-user"></i> Người báo cáo: <strong><?php echo htmlspecialchars($report['reporter_name']); ?></strong> (<?php echo htmlspecialchars($report['reporter_email']); ?>)</span>
                                <br>
                                <span><i class="fas fa-user-tie"></i> Chủ phòng: <strong><?php echo htmlspecialchars($report['owner_name'] ?? 'N/A'); ?></strong></span>
                                <br>
                                <span><i class="far fa-clock"></i> Thời gian: <?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="report-actions">
                            <?php if ($report['class_name']): ?>
                                <a href="manage_class.php?id=<?php echo $report['target_id']; ?>" class="btn-action btn-view" title="Xem lớp học">
                                    <i class="fas fa-eye"></i> Xem lớp
                                </a>
                            <?php endif; ?>
                            <a href="?mark_viewed=<?php echo $report['id']; ?>" class="btn-action btn-mark js-confirm-action" data-confirm-message="Đánh dấu báo cáo này là đã xem?" title="Đánh dấu đã xem">
                                <i class="fas fa-check"></i>
                            </a>
                            <a href="?delete_report=<?php echo $report['id']; ?>" class="btn-action btn-delete js-confirm-action" data-confirm-message="Bạn có chắc chắn muốn xóa báo cáo này?" title="Xóa báo cáo">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="report-content">
                        <strong class="report-reason-label"><i class="fas fa-comment-alt"></i> Lý do báo cáo:</strong>
                        <div class="report-reason">
                            <?php echo nl2br(htmlspecialchars($report['reason'])); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>Chưa có báo cáo nào</h3>
                <p>Hiện tại chưa có báo cáo nào từ học viên</p>
            </div>
        <?php endif; ?>
    </div>
    <script src="xu_ly.js?v=<?php echo $xu_ly_ver ?: time(); ?>"></script>
</body>
</html>
