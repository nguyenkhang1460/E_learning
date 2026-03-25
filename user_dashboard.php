<?php
session_start();
include 'db_config.php';

// 1. Kiểm tra đăng nhập 
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin người dùng từ database
$sql_user = "SELECT full_name, username FROM users WHERE id = '$user_id'";
$result_user = mysqli_query($conn, $sql_user);
$user_data = mysqli_fetch_assoc($result_user);
$user_name = $user_data['full_name'] ? $user_data['full_name'] : $_SESSION['username']; // Hiển thị tên thật, nếu không có thì hiển thị username

// 2. Lấy danh sách lớp học mà người dùng này là Chủ phòng 
$sql = "SELECT * FROM classes WHERE owner_id = '$user_id' ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

// Đếm số yêu cầu chờ duyệt cho mỗi lớp học
$pending_requests = [];
$sql_count_requests = "SELECT c.id as class_id, COUNT(r.id) as request_count 
                       FROM classes c 
                       LEFT JOIN class_requests r ON c.id = r.class_id AND r.status = 'pending'
                       WHERE c.owner_id = '$user_id'
                       GROUP BY c.id";
$result_count = mysqli_query($conn, $sql_count_requests);
while ($row_count = mysqli_fetch_assoc($result_count)) {
    $pending_requests[$row_count['class_id']] = $row_count['request_count'];
}

// Tổng số yêu cầu chờ duyệt của tất cả các lớp
$total_pending = array_sum($pending_requests);

// Lấy danh sách lớp mà người dùng đang làm "Người tham gia"
$sql_joined = "SELECT c.* FROM classes c 
               JOIN class_members cm ON c.id = cm.class_id 
               WHERE cm.user_id = '$user_id'";
$result_joined = mysqli_query($conn, $sql_joined);
?>



<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Học tập AI</title>
    <link rel="stylesheet" href="user_style.css?v=<?php echo filemtime('user_style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="user-dashboard-page">
    <nav class="navbar">
        <div class="logo logo-dashboard">
            <i class="fas fa-graduation-cap"></i> SmartLearn
        </div>
        <div class="nav-links">
            <span><i class="fas fa-user-circle"></i> Xin chào, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
            <?php if ($total_pending > 0): ?>
                <a href="manage_requests.php" class="nav-link-alert">
                    <i class="fas fa-bell"></i> Yêu cầu
                    <span class="badge"><?php echo $total_pending; ?></span>
                </a>
            <?php endif; ?>
            <a href="change_password.php" class="nav-link-password"><i class="fas fa-key"></i> Đổi mật khẩu</a>
            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Thoát</a>
        </div>
    </nav>
    <div class="container">
        <div class="welcome-section">
            <div class="welcome-content">
                <h1><i class="fas fa-book-reader"></i> Không gian học tập</h1>
                <p class="subtitle">Khám phá và phát triển kiến thức của bạn mỗi ngày</p>
            </div>
            <div class="action-buttons">
                <button class="btn btn-create js-goto-create-class"><i class="fas fa-plus-circle"></i> Tạo lớp học mới</button>
                <button class="btn btn-join js-goto-join-class"><i class="fas fa-door-open"></i> Tham gia lớp học</button>
            </div>
        </div>

        <?php if ($total_pending > 0): ?>
        <div class="notification-section">
            <div class="notification-content">
                <div class="notification-header">
                    <div class="notification-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3>Thông báo mới</h3>
                </div>
                <div class="notification-body">
                    <div class="notification-text">
                        <p class="notification-lead">
                            <i class="fas fa-user-plus"></i> Bạn có <strong class="notification-lead-count"><?php echo $total_pending; ?></strong> yêu cầu tham gia lớp học chờ duyệt!
                        </p>
                        <p class="notification-subtext">
                            Hãy kiểm tra và phê duyệt để học viên có thể tham gia học tập.
                        </p>
                    </div>
                    <a href="manage_requests.php" class="notification-btn">
                        <i class="fas fa-tasks"></i> Duyệt ngay
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <section class="my-classes">
            <h2><i class="fas fa-chalkboard-teacher"></i> Lớp học của bạn</h2>
            <div class="class-grid">
                
                <?php 
                if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                        $class_id = $row['id'];
                        $request_count = isset($pending_requests[$class_id]) ? $pending_requests[$class_id] : 0;
                ?>
                    <div class="class-card">
                        <?php if ($request_count > 0): ?>
                        <div class="request-badge">
                            <i class="fas fa-bell"></i>
                            <?php echo $request_count; ?> yêu cầu
                        </div>
                        <?php endif; ?>
                        <div class="card-header">
                            <div class="card-icon owner-icon">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="card-tag owner">Chủ phòng</div>
                        </div>
                        <h3><?php echo htmlspecialchars($row['class_name']); ?></h3>
                        
                        <p class="class-info class-info-code">
                            <i class="fas fa-key"></i> Mã mời: <?php echo $row['invite_code']; ?>
                        </p>
                        
                        <p class="class-info">
                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($row['description']); ?>
                        </p>

                        <div class="progress-bar">
                            <div class="progress progress-zero"></div>
                        </div>
                        <p class="progress-text">Chưa có bài giảng</p>
                        
                        <button class="btn-enter owner-btn js-enter-class" data-href="manage_class.php?id=<?php echo $row['id']; ?>">
                            <i class="fas fa-cog"></i> Quản lý lớp
                        </button>
                    </div>
                <?php 
                    }
                } 
                ?>
                <div class="class-card add-card js-goto-create-class">
                    <div class="add-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h3>Thêm lớp học mới</h3>
                    <p class="class-info">Khám phá thêm nhiều khóa học thú vị</p>
                </div>
            </div>
        </section>

        <!-- Phần lớp học đã tham gia -->
        <section class="my-classes">
            <h2><i class="fas fa-users"></i> Lớp học đã tham gia</h2>
            <div class="class-grid">
                <?php 
                if (mysqli_num_rows($result_joined) > 0) {
                    while($row = mysqli_fetch_assoc($result_joined)) {
                ?>
                    <div class="class-card">
                        <div class="card-header">
                            <div class="card-icon member-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="card-tag member">Thành viên</div>
                        </div>
                        <h3><?php echo htmlspecialchars($row['class_name']); ?></h3>
                        
                        <p class="class-info">
                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($row['description']); ?>
                        </p>

                        <div class="progress-bar">
                            <div class="progress progress-zero"></div>
                        </div>
                        <p class="progress-text">Chưa có bài giảng</p>
                        
                        <button class="btn-enter member-btn js-enter-class" data-href="manage_class.php?id=<?php echo $row['id']; ?>">
                            <i class="fas fa-play-circle"></i> Vào học
                        </button>
                    </div>
                <?php 
                    }
                } else {
                ?>
                    <div class="class-card empty-card">
                        <div class="empty-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <h3>Chưa tham gia lớp nào</h3>
                        <p class="class-info">Sử dụng mã mời để tham gia lớp học</p>
                        <button class="btn btn-join js-goto-join-class btn-join-mt-15">
                            <i class="fas fa-door-open"></i> Tham gia ngay
                        </button>
                    </div>
                <?php
                }
                ?>
            </div>
        </section>
    </div>
    <script src="xu_ly.js?v=<?php echo filemtime('xu_ly.js'); ?>"></script>
</body>
</html>