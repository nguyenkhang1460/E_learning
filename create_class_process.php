<?php
session_start(); // Luôn phải ở dòng đầu tiên
include 'db_config.php';

// Kiểm tra nếu chưa đăng nhập hoặc Session bị mất
if (!isset($_SESSION['user_id'])) {
    die("Lỗi: Bạn cần đăng nhập để thực hiện tính năng này.");
}

if (isset($_POST['btn_create'])) {
    $class_name = mysqli_real_escape_string($conn, $_POST['class_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $owner_id = $_SESSION['user_id']; // Bây giờ user_id đã chắc chắn có giá trị

    // Tạo mã mời ngẫu nhiên 6 ký tự
    $invite_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

    $sql = "INSERT INTO classes (class_name, description, invite_code, owner_id) 
            VALUES ('$class_name', '$description', '$invite_code', '$owner_id')";

    if (mysqli_query($conn, $sql)) {
        echo "Tạo lớp thành công! Mã mời của bạn là: " . $invite_code;
        header("Location: user_dashboard.php"); // Bạn có thể mở lại dòng này sau khi test
    } else {
        echo "Lỗi truy vấn: " . mysqli_error($conn);
    }
}
?>