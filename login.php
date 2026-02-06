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
    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: bold;
            animation: slideDown 0.3s ease-out;
        }
        .alert-error {
            background-color: #ffebee;
            color: #c62828;
            border-left: 4px solid #e74c3c;
        }
        .alert-success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #27ae60;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

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

    <script>
        // Auto focus vào ô username
        document.getElementById('username').focus();
    </script>
</body>
</html>