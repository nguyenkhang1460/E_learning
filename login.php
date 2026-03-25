<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống học tập AI</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">

    <div class="login-box">
        <h2>Đăng Nhập</h2>
        
        <!-- Hiển thị thông báo lỗi nếu có -->
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
        
        <form action="login_process.php" method="POST">
            <div class="input-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" required placeholder="Nhập username">
            </div>

            <div class="input-group">
                <label for="password">Mật khẩu</label>
                <input type="password" id="password" name="password" required placeholder="********">
            </div>

            <button type="submit" name="login">Vào hệ thống</button>
        </form>

        <div class="register-link">
            <p>Chưa có tài khoản? <a href="register.html">Đăng ký ngay</a></p>
        </div>

        <p class="footer-text">Đồ án 2 - Trang web học tập tích hợp AI</p>
    </div>

    <script src="xu_ly.js"></script>
</body>
</html>