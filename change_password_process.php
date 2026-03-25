<?php
session_start();
include 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin!';
        header("Location: change_password.php");
        exit();
    }
    
    // Kiểm tra độ dài mật khẩu mới
    if (strlen($new_password) < 6) {
        $_SESSION['error'] = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
        header("Location: change_password.php");
        exit();
    }
    
    // Kiểm tra mật khẩu mới và xác nhận có khớp không
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'Mật khẩu mới và xác nhận mật khẩu không khớp!';
        header("Location: change_password.php");
        exit();
    }
    
    // Kiểm tra mật khẩu mới có trùng với mật khẩu cũ không
    if ($current_password === $new_password) {
        $_SESSION['error'] = 'Mật khẩu mới không được trùng với mật khẩu hiện tại!';
        header("Location: change_password.php");
        exit();
    }
    
    // Lấy thông tin user từ database
    $sql = "SELECT password FROM users WHERE id = '$user_id'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Xác thực mật khẩu hiện tại
        $password_correct = false;
        if (password_verify($current_password, $user['password'])) {
            // Mật khẩu được mã hóa bằng password_hash
            $password_correct = true;
        } elseif ($current_password == $user['password']) {
            // Mật khẩu plain text (không mã hóa)
            $password_correct = true;
        }
        
        if (!$password_correct) {
            $_SESSION['error'] = 'Mật khẩu hiện tại không chính xác!';
            header("Location: change_password.php");
            exit();
        }
        
        // Mã hóa mật khẩu mới
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Cập nhật mật khẩu mới vào database
        $sql_update = "UPDATE users SET password = '$hashed_password' WHERE id = '$user_id'";
        
        if (mysqli_query($conn, $sql_update)) {
            $_SESSION['success'] = 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại với mật khẩu mới.';
            
            // Log out user sau khi đổi mật khẩu (bảo mật)
            // Hoặc có thể để user tiếp tục sử dụng
            // Ở đây tôi sẽ redirect về trang change_password với thông báo thành công
            header("Location: change_password.php");
            exit();
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra khi cập nhật mật khẩu: ' . mysqli_error($conn);
            header("Location: change_password.php");
            exit();
        }
    } else {
        $_SESSION['error'] = 'Không tìm thấy thông tin người dùng!';
        header("Location: change_password.php");
        exit();
    }
} else {
    // Nếu không phải POST request, redirect về trang đổi mật khẩu
    header("Location: change_password.php");
    exit();
}
?>
