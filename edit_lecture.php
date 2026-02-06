<?php
session_start();
include 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
        echo "<script>alert('Cập nhật thành công!'); window.location='manage_lectures.php?id=$class_id';</script>";
    } else {
        echo "<script>alert('Lỗi: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa bài giảng</title>
    <link rel="stylesheet" href="user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .edit-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            font-size: 16px;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
        }
        .btn-cancel {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #666;
            text-decoration: none;
        }
        .btn-cancel:hover {
            color: #333;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i> AI-Learning
        </div>
        <div class="nav-links">
            <a href="manage_lectures.php?id=<?php echo $class_id; ?>" style="text-decoration: none; color: #666;">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-section">
            <h1><i class="fas fa-edit"></i> Chỉnh sửa bài giảng</h1>
        </div>

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
                    <small style="color: #999;">Link file trên Cloudinary hoặc dịch vụ lưu trữ khác</small>
                </div>

                <button type="submit" name="btn_update" class="btn-submit">
                    <i class="fas fa-save"></i> Lưu thay đổi
                </button>
                
                <a href="manage_lectures.php?id=<?php echo $class_id; ?>" class="btn-cancel">
                    <i class="fas fa-times"></i> Hủy bỏ
                </a>
            </form>
        </div>
    </div>
</body>
</html>
