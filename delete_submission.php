<?php
session_start();
include 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin bài nộp
$sql = "SELECT s.*, a.class_id, c.owner_id 
        FROM submissions s 
        JOIN assignments a ON s.assignment_id = a.id 
        JOIN classes c ON a.class_id = c.id 
        WHERE s.id = '$submission_id'";
$result = mysqli_query($conn, $sql);
$submission = mysqli_fetch_assoc($result);

if (!$submission) {
    echo "<script>alert('Bài nộp không tồn tại!'); window.history.back();</script>";
    exit();
}

// Kiểm tra quyền xóa (chủ lớp hoặc người nộp bài)
$is_owner = ($submission['owner_id'] == $current_user_id);
$is_submitter = ($submission['user_id'] == $current_user_id);

if (!$is_owner && !$is_submitter) {
    echo "<script>alert('Bạn không có quyền xóa bài nộp này!'); window.history.back();</script>";
    exit();
}

// Thông tin Cloudinary
$cloud_name = "dwvsbcegm";
$api_key = "833377455192851";
$api_secret = "YOUR_API_SECRET"; // Bạn cần thay thế bằng API Secret thực của Cloudinary

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
    
    // Thử xóa như image trước
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

    // Nếu không thành công với image, thử với raw (PDF)
    if (!isset($result['result']) || $result['result'] != 'ok') {
        $delete_url = "https://api.cloudinary.com/v1_1/{$cloud_name}/raw/destroy";
        $signature = sha1("public_id={$public_id}&timestamp={$timestamp}{$api_secret}");
        
        $post_data['signature'] = $signature;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $delete_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);
    }

    return isset($result['result']) && $result['result'] == 'ok';
}

// Xóa file bài nộp trên Cloudinary
if (!empty($submission['student_file_url'])) {
    deleteFromCloudinary($submission['student_file_url'], $cloud_name, $api_key, $api_secret);
}

// Xóa bài nộp trong database
$sql_delete = "DELETE FROM submissions WHERE id = '$submission_id'";
if (mysqli_query($conn, $sql_delete)) {
    echo "<script>alert('Đã xóa bài nộp thành công!'); window.location='manage_class.php?id=" . $submission['class_id'] . "';</script>";
} else {
    echo "<script>alert('Lỗi khi xóa: " . mysqli_error($conn) . "'); window.history.back();</script>";
}
?>
