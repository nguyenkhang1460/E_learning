<?php
session_start();
include 'db_config.php';

// Kiểm tra nếu form được submit
if (isset($_POST['register'])) {
    // Lấy dữ liệu từ form
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Kiểm tra dữ liệu hợp lệ
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        header("Location: register.html?error=invalid_data");
        exit();
    }
    
    // Kiểm tra mật khẩu khớp
    if ($password !== $confirm_password) {
        header("Location: register.html?error=password_mismatch");
        exit();
    }
    
    // Kiểm tra độ dài mật khẩu
    if (strlen($password) < 6) {
        header("Location: register.html?error=password_too_short");
        exit();
    }
    
    // Kiểm tra username đã tồn tại chưa
    $check_username = "SELECT * FROM users WHERE username = '$username'";
    $result_username = mysqli_query($conn, $check_username);
    
    if (mysqli_num_rows($result_username) > 0) {
        header("Location: register.html?error=username_exists");
        exit();
    }
    
    // Kiểm tra email đã tồn tại chưa
    $check_email = "SELECT * FROM users WHERE email = '$email'";
    $result_email = mysqli_query($conn, $check_email);
    
    if (mysqli_num_rows($result_email) > 0) {
        header("Location: register.html?error=email_exists");
        exit();
    }
    
    // Mã hóa mật khẩu
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Thêm user mới vào database
    $sql = "INSERT INTO users (username, email, password, role, created_at) 
            VALUES ('$username', '$email', '$hashed_password', 'user', NOW())";
    
    if (mysqli_query($conn, $sql)) {
        // Đăng ký thành công, chuyển về trang login
        header("Location: register.html?success=1");
        exit();
    } else {
        header("Location: register.html?error=database_error");
        exit();
    }
} else {
    // Nếu không phải POST request, chuyển về trang register
    header("Location: register.html");
    exit();
}

mysqli_close($conn);
?>