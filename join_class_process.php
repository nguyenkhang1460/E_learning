<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['btn_join'])) {
    $invite_code = mysqli_real_escape_string($conn, strtoupper($_POST['invite_code']));
    $user_id = $_SESSION['user_id'];

    // 1. Kiểm tra mã mời có tồn tại trong bảng classes không
    $sql_check = "SELECT id, owner_id FROM classes WHERE invite_code = '$invite_code'";
    $result = mysqli_query($conn, $sql_check);

    if (mysqli_num_rows($result) > 0) {
        $class = mysqli_fetch_assoc($result);
        $class_id = $class['id'];

        // 2. Kiểm tra nếu người dùng là chủ phòng thì không cần tham gia nữa
        if ($class['owner_id'] == $user_id) {
            echo "<script>alert('Bạn là chủ lớp này rồi!'); window.location='user_dashboard.php';</script>";
            exit();
        }

        // 3. Thêm vào bảng class_members
        $sql_join = "INSERT INTO class_members (user_id, class_id) VALUES ('$user_id', '$class_id')";
        
        if (mysqli_query($conn, $sql_join)) {
            echo "<script>alert('Tham gia lớp học thành công!'); window.location='user_dashboard.php';</script>";
        } else {
            // Lỗi do đã tham gia rồi (Duplicate entry)
            echo "<script>alert('Bạn đã tham gia lớp học này trước đó!'); window.location='user_dashboard.php';</script>";
        }
    } else {
        echo "<script>alert('Mã mời không tồn tại!'); window.history.back();</script>";
    }
}
?>