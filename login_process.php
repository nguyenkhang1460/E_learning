<?php
session_start();
// Bật hiển thị lỗi để dễ debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_config.php';

if (isset($_POST['login'])) {
    // Lấy dữ liệu và chống lỗi SQL Injection cơ bản
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Kiểm tra mật khẩu (Sử dụng password_verify cho mật khẩu mã hóa)
        if ($password == $user['password'] || password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];        
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit();
        } else {
            // Nếu sai mật khẩu, quay lại trang login và gửi kèm lỗi
            $_SESSION['error'] = 'Sai mật khẩu! Vui lòng thử lại.';
            header("Location: login.php");
            exit();
        }
    } else {
        // Nếu không tìm thấy user
        $_SESSION['error'] = 'Tài khoản không tồn tại! Vui lòng kiểm tra lại tên đăng nhập.';
        header("Location: login.php");
        exit();
    }
}
?>