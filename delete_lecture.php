<?php
session_start();
include 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$lecture_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($lecture_id == 0) {
    die("ID tài liệu không hợp lệ!");
}

// Lấy thông tin tài liệu và kiểm tra quyền
$sql_lecture = "SELECT l.*, c.owner_id, l.class_id 
                FROM lectures l 
                JOIN classes c ON l.class_id = c.id 
                WHERE l.id = '$lecture_id'";
$result = mysqli_query($conn, $sql_lecture);
$lecture = mysqli_fetch_assoc($result);

if (!$lecture) {
    die("Tài liệu không tồn tại!");
}

// Kiểm tra xem người dùng có phải là admin hoặc chủ lớp học không
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] == 'admin');
if (!$is_admin && $lecture['owner_id'] != $current_user_id) {
    die("Bạn không có quyền xóa tài liệu này!");
}

$class_id = $lecture['class_id'];
$file_url = $lecture['file_url'];

// Xóa tài liệu khỏi database
$sql_delete = "DELETE FROM lectures WHERE id = '$lecture_id'";
if (mysqli_query($conn, $sql_delete)) {
    // Xóa file vật lý nếu tồn tại
    if (file_exists($file_url)) {
        unlink($file_url);
    }
    
    header("Location: manage_class.php?id=$class_id");
    exit();
} else {
    die("Lỗi khi xóa tài liệu: " . mysqli_error($conn));
}
?>
