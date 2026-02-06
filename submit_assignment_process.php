<?php
session_start();
include 'db_config.php';
// Gọi thư viện trích xuất văn bản từ PDF
require 'vendor/autoload.php'; 

if (isset($_POST['btn_submit_task'])) {
    // 1. CHUẨN BỊ DỮ LIỆU
    $asm_id = mysqli_real_escape_string($conn, $_POST['assignment_id']);
    $user_id = $_SESSION['user_id'];
    
    // Cấu hình Cloudinary của Khang
    $cloud_name = "dwvsbcegm";
    $upload_preset = "do_an_ai"; 

    $file_tmp  = $_FILES['student_file']['tmp_name'];
    $file_name = $_FILES['student_file']['name'];
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // 2. TẢI FILE LÊN CLOUDINARY (LƯU BẰNG CHỨNG)
    $url = "https://api.cloudinary.com/v1_1/$cloud_name/auto/upload";
    $post_data = ["file" => new CURLFile($file_tmp), "upload_preset" => $upload_preset];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    if (isset($result['secure_url'])) {
        $file_url = $result['secure_url'];
        $type = ($file_ext == 'pdf') ? 'pdf' : 'image';
        $extracted_text = "";

        // 3. TRÍCH XUẤT VĂN BẢN THÔ (NGUYÊN LIỆU CHO AI LƯỢT 1)
        if ($file_ext == 'pdf') {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($file_tmp);
                $extracted_text = $pdf->getText(); // Lấy chữ thô dính nhau hoặc lộn xộn
            } catch (Exception $e) {
                $extracted_text = "Lỗi đọc PDF: " . $e->getMessage();
            }
        } else {
            $extracted_text = "Nội dung hình ảnh (Chưa xử lý OCR).";
        }

        // 4. LƯU VÀO DATABASE (KHÔNG CHẤM ĐIỂM Ở ĐÂY)
        $student_text_db = mysqli_real_escape_string($conn, trim($extracted_text));

        // Lưu vào bảng submissions sạch sẽ
        $sql = "INSERT INTO submissions (assignment_id, user_id, student_file_url, student_file_type, student_text) 
                VALUES ('$asm_id', '$user_id', '$file_url', '$type', '$student_text_db')";

        if (mysqli_query($conn, $sql)) {
            echo "<script>
                alert('Nộp bài thành công! Bạn có thể nhấn Chấm bài để AI thực hiện phân tích.'); 
                window.location='user_dashboard.php';
            </script>";
        }
    } else {
        echo "❌ Lỗi Cloudinary: " . ($result['error']['message'] ?? 'Upload thất bại.');
    }
}
?>