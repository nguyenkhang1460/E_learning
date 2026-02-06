<?php
session_start();

// Hủy tất cả các biến session
$_SESSION = array();

// Hủy cookie session nếu có
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Hủy session
session_destroy();

// Chuyển hướng về trang login
header("Location: login.php");
exit();
?>
