<?php
session_start();
include 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin bài kiểm tra
$sql = "SELECT a.*, c.owner_id FROM assignments a 
        JOIN classes c ON a.class_id = c.id 
        WHERE a.id = '$assignment_id'";
$result = mysqli_query($conn, $sql);
$assignment = mysqli_fetch_assoc($result);

if (!$assignment) {
    echo "<script>alert('Bài kiểm tra không tồn tại!'); window.history.back();</script>";
    exit();
}

// Kiểm tra quyền xóa (chỉ chủ lớp mới được xóa)
if ($assignment['owner_id'] != $current_user_id) {
    echo "<script>alert('Bạn không có quyền xóa bài kiểm tra này!'); window.history.back();</script>";
    exit();
}

// Thông tin Cloudinary
$cloud_name = "dwvsbcegm";
$api_key = "833377455192851";
$api_secret = "Iw16cRIVLYnANWWdjGKUQyD5YQ4"; // Bạn cần thay thế bằng API Secret thực của Cloudinary

// Hàm lấy public_id từ URL Cloudinary
function getPublicIdFromUrl($url) {
    // URL mẫu: https://res.cloudinary.com/dwvsbcegm/image/upload/v1234567890/abc123.pdf
    $pattern = '/\/upload\/(?:v\d+\/)?(.+)\.[a-z]{3,4}$/i';
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    return null;
}

// Hàm xóa file trên Cloudinary
function deleteFromCloudinary($url, $cloud_name, $api_key, $api_secret) {
    $public_id = getPublicIdFromUrl($url);
    if (!$public_id) {
        return false;
    }

    $timestamp = time();
    $signature = sha1("public_id={$public_id}&timestamp={$timestamp}{$api_secret}");
    
    $delete_url = "https://api.cloudinary.com/v1_1/{$cloud_name}/image/destroy";
    
    $post_data = array(
        "public_id" => $public_id,
        "api_key" => $api_key,
        "timestamp" => $timestamp,
        "signature" => $signature
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $delete_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return isset($result['result']) && $result['result'] == 'ok';
}

// Xóa file đề bài trên Cloudinary
if (!empty($assignment['assignment_file_url'])) {
    deleteFromCloudinary($assignment['assignment_file_url'], $cloud_name, $api_key, $api_secret);
}

// Xóa file đáp án trên Cloudinary
if (!empty($assignment['answer_key_url'])) {
    deleteFromCloudinary($assignment['answer_key_url'], $cloud_name, $api_key, $api_secret);
}

// Lấy tất cả bài nộp liên quan để xóa file của học viên
$sql_subs = "SELECT student_file_url FROM submissions WHERE assignment_id = '$assignment_id'";
$result_subs = mysqli_query($conn, $sql_subs);
while ($sub = mysqli_fetch_assoc($result_subs)) {
    if (!empty($sub['student_file_url'])) {
        deleteFromCloudinary($sub['student_file_url'], $cloud_name, $api_key, $api_secret);
    }
}

// Xóa tất cả bài nộp liên quan trong database
$sql_delete_subs = "DELETE FROM submissions WHERE assignment_id = '$assignment_id'";
mysqli_query($conn, $sql_delete_subs);

// Xóa bài kiểm tra trong database
$sql_delete_assignment = "DELETE FROM assignments WHERE id = '$assignment_id'";
if (mysqli_query($conn, $sql_delete_assignment)) {
    echo "<script>alert('Đã xóa bài kiểm tra và tất cả bài nộp liên quan thành công!'); window.location='manage_class.php?id=" . $assignment['class_id'] . "';</script>";
} else {
    echo "<script>alert('Lỗi khi xóa: " . mysqli_error($conn) . "'); window.history.back();</script>";
}
?>
