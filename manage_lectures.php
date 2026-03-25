<?php
session_start();
include 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$class_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

// Lấy thông tin lớp học
$sql_class = "SELECT * FROM classes WHERE id = '$class_id'";
$result_class = mysqli_query($conn, $sql_class);
$class_data = mysqli_fetch_assoc($result_class);

if (!$class_data) {
    die("Lớp học không tồn tại!");
}

// Kiểm tra quyền chủ phòng
if ($class_data['owner_id'] != $current_user_id) {
    die("Bạn không có quyền quản lý lớp học này!");
}

// Xử lý xóa bài giảng
if (isset($_GET['delete_id'])) {
    $delete_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $sql_delete = "DELETE FROM lectures WHERE id = '$delete_id' AND class_id = '$class_id'";
    if (mysqli_query($conn, $sql_delete)) {
        $_SESSION['success'] = 'Đã xóa bài giảng thành công!';
    } else {
        $_SESSION['error'] = 'Lỗi khi xóa bài giảng!';
    }
    header("Location: manage_lectures.php?id=$class_id");
    exit();
}

// Lấy danh sách bài giảng
$sql_lectures = "SELECT * FROM lectures WHERE class_id = '$class_id' ORDER BY created_at DESC";
$result_lectures = mysqli_query($conn, $sql_lectures);

// Đếm số lượng bài giảng
$total_lectures = mysqli_num_rows($result_lectures);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý bài giảng - <?php echo htmlspecialchars($class_data['class_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="manage-lectures-page">
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i> AI-Learning
        </div>
        <div class="nav-links">
            <a href="manage_class.php?id=<?php echo $class_id; ?>" class="manage-lectures-back-link">
                <i class="fas fa-arrow-left"></i> Quay lại lớp học
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-section">
            <h1><i class="fas fa-folder-open"></i> Quản lý bài giảng</h1>
            <p class="subtitle">Lớp: <strong><?php echo htmlspecialchars($class_data['class_name']); ?></strong></p>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="manage-lectures-alert manage-lectures-alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="manage-lectures-alert manage-lectures-alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="stats-card">
            <h3><?php echo $total_lectures; ?></h3>
            <p><i class="fas fa-file-alt"></i> Tổng số bài giảng đã đăng tải</p>
        </div>

        <a href="upload_lecture.php?id=<?php echo $class_id; ?>" class="btn-upload">
            <i class="fas fa-cloud-upload-alt"></i> Đăng tải bài giảng mới
        </a>

        <?php if (mysqli_num_rows($result_lectures) > 0): ?>
            <?php while($lecture = mysqli_fetch_assoc($result_lectures)): ?>
                <?php
                // Xác định loại file
                $file_url = $lecture['file_url'];
                $file_extension = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
                
                if (in_array($file_extension, ['pdf'])) {
                    $badge_class = 'badge-pdf';
                    $badge_text = 'PDF';
                } elseif (in_array($file_extension, ['doc', 'docx'])) {
                    $badge_class = 'badge-doc';
                    $badge_text = 'DOCX';
                } elseif (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $badge_class = 'badge-image';
                    $badge_text = 'IMAGE';
                } else {
                    $badge_class = 'badge-doc';
                    $badge_text = 'FILE';
                }
                ?>
                <div class="lecture-item">
                    <div class="lecture-header">
                        <div class="lecture-title">
                            <i class="fas fa-file-alt"></i> 
                            <?php echo htmlspecialchars($lecture['title']); ?>
                            <span class="file-type-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                        </div>
                    </div>
                    
                    <div class="lecture-info">
                        <i class="fas fa-calendar-plus"></i> Đăng ngày: <?php echo date('d/m/Y H:i', strtotime($lecture['created_at'])); ?>
                    </div>
                    
                    <?php if ($lecture['description']): ?>
                        <div class="lecture-info">
                            <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($lecture['description']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="lecture-info">
                        <i class="fas fa-link"></i> URL: 
                        <a href="<?php echo htmlspecialchars($lecture['file_url']); ?>" target="_blank" class="lecture-url-link">
                            <?php echo substr($lecture['file_url'], 0, 60); ?>...
                        </a>
                    </div>
                    
                    <div class="lecture-actions">
                        <a href="<?php echo htmlspecialchars($lecture['file_url']); ?>" target="_blank" class="btn-action btn-view">
                            <i class="fas fa-eye"></i> Xem tài liệu
                        </a>
                        <a href="edit_lecture.php?id=<?php echo $lecture['id']; ?>&class_id=<?php echo $class_id; ?>" class="btn-action btn-edit">
                            <i class="fas fa-edit"></i> Chỉnh sửa
                        </a>
                        <button class="btn-action btn-delete js-confirm-delete-lecture" data-lecture-id="<?php echo $lecture['id']; ?>" type="button">
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h2>Chưa có bài giảng nào</h2>
                <p>Hãy đăng tải bài giảng đầu tiên cho lớp học này.</p>
                <a href="upload_lecture.php?id=<?php echo $class_id; ?>" class="btn-upload empty-upload-link">
                    <i class="fas fa-cloud-upload-alt"></i> Đăng tải ngay
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div id="manageLecturesConfig" data-class-id="<?php echo $class_id; ?>" hidden></div>
    <script src="xu_ly.js"></script>
</body>
</html>
