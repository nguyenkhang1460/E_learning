<?php
session_start();
include 'db_config.php';

if (isset($_POST['btn_upload'])) {
    $class_id = $_POST['class_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    
    // Kiểm tra đuôi file trước khi upload
    $file_name = $_FILES['lecture_file']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = array("pdf", "jpg", "jpeg", "png", "gif");

    if (!in_array($file_ext, $allowed_ext)) {
        echo "<script>alert('Lỗi: Chỉ cho phép tải lên file PDF hoặc Hình ảnh!'); window.history.back();</script>";
        exit();
    }

    // Thông tin Cloudinary của bạn
    $cloud_name = "dwvsbcegm"; 
    $api_key = "833377455192851"; 
    $upload_preset = "do_an_ai"; // Đảm bảo bạn đã tạo cái này trên Cloudinary

    $file_tmp = $_FILES['lecture_file']['tmp_name'];
    $url = "https://api.cloudinary.com/v1_1/$cloud_name/auto/upload";
    
    $post_data = array(
        "file" => new CURLFile($file_tmp),
        "upload_preset" => $upload_preset,
        "resource_type" => "auto"
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($result['secure_url'])) {
        $file_url = $result['secure_url'];
        // Lưu 'pdf' hoặc 'img' vào cột file_type để dễ hiển thị icon
        $type_to_save = ($file_ext == 'pdf') ? 'pdf' : 'image';

        $sql = "INSERT INTO lectures (class_id, title, file_url, file_type) 
                VALUES ('$class_id', '$title', '$file_url', '$type_to_save')";

        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Đã tải lên tài liệu thành công!'); window.location='manage_class.php?id=$class_id';</script>";
        }
    } else {
        echo "Lỗi: " . $result['error']['message'];
    }
}
?>