<?php
session_start();
include 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập']);
    exit();
}

$user_id = $_SESSION['user_id'];
$class_id = isset($_POST['class_id']) ? mysqli_real_escape_string($conn, $_POST['class_id']) : 0;
$reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : '';

// Kiểm tra đầu vào
if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập lý do báo cáo']);
    exit();
}

// Xác định loại báo cáo
$report_type = '';
$target_id = 0;

if (isset($_POST['lecture_id'])) {
    $report_type = 'lecture';
    $target_id = mysqli_real_escape_string($conn, $_POST['lecture_id']);
} elseif (isset($_POST['assignment_id'])) {
    $report_type = 'assignment';
    $target_id = mysqli_real_escape_string($conn, $_POST['assignment_id']);
} else {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin báo cáo']);
    exit();
}

// Lưu báo cáo vào database
$sql = "INSERT INTO reports (user_id, class_id, report_type, target_id, reason, created_at) 
        VALUES ('$user_id', '$class_id', '$report_type', '$target_id', '$reason', NOW())";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Báo cáo đã được gửi thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . mysqli_error($conn)]);
}
?>
