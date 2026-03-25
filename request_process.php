<?php
session_start();
include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_id = intval($_POST['class_id']);
    $user_id = $_SESSION['user_id'];
    $answers = json_encode($_POST['answers'], JSON_UNESCAPED_UNICODE);

    $sql = "INSERT INTO class_requests (class_id, user_id, answers) VALUES ($class_id, $user_id, '$answers')";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Đã gửi yêu cầu thành công!'); window.location='user_dashboard.php';</script>";
    }
}
?>