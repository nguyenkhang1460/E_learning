<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: /E_learning/modules/auth/login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$lecture_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;
$class_id = isset($_GET['class_id']) ? mysqli_real_escape_string($conn, $_GET['class_id']) : 0;

// Lấy thông tin bài giảng
$sql = "SELECT l.*, c.owner_id FROM lectures l 
        JOIN classes c ON l.class_id = c.id 
        WHERE l.id = '$lecture_id'";
$result = mysqli_query($conn, $sql);
$lecture = mysqli_fetch_assoc($result);

if (!$lecture) {
    die("Bài giảng không tồn tại!");
}

// Kiểm tra quyền chủ phòng
if ($lecture['owner_id'] != $current_user_id) {
    die("Bạn không có quyền chỉnh sửa bài giảng này!");
}

// Xử lý cập nhật
if (isset($_POST['btn_update'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $file_url = mysqli_real_escape_string($conn, $_POST['file_url']);
    
    $sql_update = "UPDATE lectures SET 
                   title = '$title',
                   description = '$description',
                   file_url = '$file_url'
                   WHERE id = '$lecture_id'";
    
    if (mysqli_query($conn, $sql_update)) {
        $_SESSION['success'] = 'Cập nhật bài giảng thành công!';
        header("Location: /E_learning/modules/lecture/manage_lectures.php?id=$class_id");
        exit();
    } else {
        $_SESSION['error'] = 'Lỗi khi cập nhật: ' . mysqli_error($conn);
        header("Location: /E_learning/modules/lecture/edit_lecture.php?id=$lecture_id&class_id=$class_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa bài giảng</title>
    <link rel="stylesheet" href="/E_learning/assets/css/style.css">
    <link rel="stylesheet" href="/E_learning/assets/css/user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i> AI-Learning
        </div>
        <div class="nav-links">
            <a href="/E_learning/modules/lecture/manage_lectures.php?id=<?php echo $class_id; ?>" class="edit-lecture-back-link">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-section">
            <h1><i class="fas fa-edit"></i> Chỉnh sửa bài giảng</h1>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="edit-lecture-alert edit-lecture-alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="edit-form">
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-heading"></i> Tiêu đề:</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($lecture['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Mô tả (không bắt buộc):</label>
                    <textarea name="description"><?php echo htmlspecialchars($lecture['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-link"></i> URL file:</label>
                    <input type="url" name="file_url" value="<?php echo htmlspecialchars($lecture['file_url']); ?>" required>
                    <small class="field-help-text">Link file trên Cloudinary hoặc dịch vụ lưu trữ khác</small>
                </div>

                <button type="submit" name="btn_update" class="btn-submit">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
                
                <a href="/E_learning/modules/lecture/manage_lectures.php?id=<?php echo $class_id; ?>" class="btn-cancel">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
            </form>
        </div>
    </div>
</body>
</html>
