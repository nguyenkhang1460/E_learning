<?php
session_start();
include 'db_config.php';

// 1. Kiểm tra đăng nhập 
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username']; // Lấy tên để hiển thị lên Navbar

// 2. Lấy danh sách lớp học mà người dùng này là Chủ phòng 
$sql = "SELECT * FROM classes WHERE owner_id = '$user_id' ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

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
    <link rel="stylesheet" href="user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i> AI-Learning
        </div>
        <div class="nav-links">
            <span><i class="fas fa-user-circle"></i> Xin chào, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
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
                <button class="btn btn-create" onclick="window.location.href='create_class.html'"><i class="fas fa-plus-circle"></i> Tạo lớp học mới</button>
                <button class="btn btn-join" onclick="window.location.href='join_class.html'"><i class="fas fa-door-open"></i> Tham gia lớp học</button>
            </div>
        </div>

        <section class="my-classes">
            <h2><i class="fas fa-chalkboard-teacher"></i> Lớp học của bạn</h2>
            <div class="class-grid">
                
                <?php 
                if (mysqli_num_rows($result) > 0) {
                    while($row = mysqli_fetch_assoc($result)) {
                ?>
                    <div class="class-card">
                        <div class="card-header">
                            <div class="card-icon owner-icon">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="card-tag owner">Chủ phòng</div>
                        </div>
                        <h3><?php echo htmlspecialchars($row['class_name']); ?></h3>
                        
                        <p class="class-info" style="color: #007bff; font-weight: bold;">
                            <i class="fas fa-key"></i> Mã mời: <?php echo $row['invite_code']; ?>
                        </p>
                        
                        <p class="class-info">
                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($row['description']); ?>
                        </p>

                        <div class="progress-bar">
                            <div class="progress" style="width: 0%;"></div>
                        </div>
                        <p class="progress-text">Chưa có bài giảng</p>
                        
                        <button class="btn-enter owner-btn" onclick="window.location.href='manage_class.php?id=<?php echo $row['id']; ?>'">
                            <i class="fas fa-cog"></i> Quản lý lớp
                        </button>
                    </div>
                <?php 
                    }
                } 
                ?>
                <div class="class-card add-card" onclick="window.location.href='create_class.html'" style="cursor: pointer;">
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
                            <div class="progress" style="width: 0%;"></div>
                        </div>
                        <p class="progress-text">Chưa có bài giảng</p>
                        
                        <button class="btn-enter member-btn" onclick="window.location.href='manage_class.php?id=<?php echo $row['id']; ?>'">
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
                        <button class="btn btn-join" onclick="window.location.href='join_class.html'" style="margin-top: 15px;">
                            <i class="fas fa-door-open"></i> Tham gia ngay
                        </button>
                    </div>
                <?php
                }
                ?>
            </div>
        </section>
    </div>
</body>
</html>