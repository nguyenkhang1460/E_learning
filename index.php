<?php
/**
 * E_learning - Entry Point
 * Redirect người dùng đến trang đăng nhập
 */
session_start();

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
        header("Location: /E_learning/dashboard/admin_dashboard.php");
    } else {
        header("Location: /E_learning/dashboard/user_dashboard.php");
    }
} else {
    header("Location: /E_learning/modules/auth/login.php");
}
exit();
?>
