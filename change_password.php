<?php
session_start();
include 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="change-password-container">
        <a href="user_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Quay lại Dashboard</a>
        
        <h2><i class="fas fa-key"></i> Đổi mật khẩu</h2>
        <p class="subtitle">Tài khoản: <strong><?php echo htmlspecialchars($username); ?></strong></p>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <div class="password-requirements">
            <h4><i class="fas fa-info-circle"></i> Yêu cầu mật khẩu:</h4>
            <ul>
                <li>Tối thiểu 6 ký tự</li>
                <li>Nên kết hợp chữ và số</li>
                <li>Không được trùng với mật khẩu cũ</li>
            </ul>
        </div>
        
        <form id="changePasswordForm" action="change_password_process.php" method="POST">
            <div class="input-group">
                <label for="current_password">
                    <i class="fas fa-lock"></i> Mật khẩu hiện tại
                </label>
                <input type="password" id="current_password" name="current_password" required placeholder="Nhập mật khẩu hiện tại">
            </div>
            
            <div class="input-group">
                <label for="new_password">
                    <i class="fas fa-key"></i> Mật khẩu mới
                </label>
                <input type="password" id="new_password" name="new_password" required placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)" minlength="6">
            </div>
            
            <div class="input-group">
                <label for="confirm_password">
                    <i class="fas fa-check-circle"></i> Xác nhận mật khẩu mới
                </label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Nhập lại mật khẩu mới">
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Đổi mật khẩu
            </button>
        </form>
    </div>
    <script src="xu_ly.js"></script>
</body>
</html>
