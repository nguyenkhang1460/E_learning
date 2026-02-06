<?php
session_start();
include 'db_config.php';

// --- 1. CẤU HÌNH CLOUDINARY (Khang điền thông tin của bạn vào đây) ---
$cloudinary_cloud_name = 'dwvsbcegm'; 
$cloudinary_api_key    = '833377455192851';
$cloudinary_api_secret = 'Iw16cRIVLYnANWWdjGKUQyD5YQ4';

/**
 * Hàm hỗ trợ upload file (PDF/Ảnh) lên Cloudinary bằng cURL
 */
function uploadToCloudinary($file, $cloud_name, $api_key, $api_secret) {
    $file_path = $file['tmp_name'];
    $timestamp = time();
    $signature = sha1("timestamp=$timestamp$api_secret");
    $url = "https://api.cloudinary.com/v1_1/$cloud_name/auto/upload";

    $post_data = [
        'file' => new CURLFile($file_path),
        'timestamp' => $timestamp,
        'api_key' => $api_key,
        'signature' => $signature
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['secure_url'] ?? null;
}

// --- 2. XỬ LÝ KHI NHẤN NÚT ĐĂNG BÀI ---
if (isset($_POST['btn_create'])) {
    $class_id = intval($_POST['class_id']);
    $title    = mysqli_real_escape_string($conn, $_POST['title']);
    $content  = mysqli_real_escape_string($conn, $_POST['content']);

    // A. Thực hiện upload Đề bài và Đáp án chuẩn lên Cloudinary thực tế
    $file_url   = uploadToCloudinary($_FILES['assignment_file'], $cloudinary_cloud_name, $cloudinary_api_key, $cloudinary_api_secret);
    $answer_url = uploadToCloudinary($_FILES['answer_key'], $cloudinary_cloud_name, $cloudinary_api_key, $cloudinary_api_secret);

    if (!$file_url || !$answer_url) {
        die("❌ Lỗi upload file lên Cloudinary. Kiểm tra lại cấu hình API!");
    }

    // B. Lưu thông tin vào bảng assignments (Đã khớp với cấu trúc SHOW COLUMNS của bạn)
    $sql_asm = "INSERT INTO assignments (class_id, title, content, file_url, answer_key_url) 
                VALUES ($class_id, '$title', '$content', '$file_url', '$answer_url')";

    if (mysqli_query($conn, $sql_asm)) {
        $assignment_id = mysqli_insert_id($conn); // Lấy ID vừa tạo để liên kết Rubric

        // C. Xử lý lưu Rubric đi kèm
        if (isset($_POST['rubric_content'])) {
            $contents = $_POST['rubric_content'];
            $scores   = $_POST['rubric_score'];
            $rubric_data = [];

            foreach ($contents as $i => $text) {
                if (trim($text) !== '') {
                    $rubric_data[] = [
                        "id"      => $i + 1,
                        "content" => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
                        "score"   => (float)$scores[$i]
                    ];
                }
            }
            $rubric_json = json_encode($rubric_data, JSON_UNESCAPED_UNICODE);

            // Lưu vào bảng assignment_rubrics
            $sql_rubric = "INSERT INTO assignment_rubrics (assignment_id, rubric_json) 
                           VALUES ($assignment_id, '$rubric_json')
                           ON DUPLICATE KEY UPDATE rubric_json = '$rubric_json'";
            mysqli_query($conn, $sql_rubric);
        }

        echo "<script>
            alert('Thành công! Bài tập và Rubric đã được AI kích hoạt.');
            window.location='manage_class.php?id=$class_id';
        </script>";
    } else {
        echo "❌ Lỗi Database: " . mysqli_error($conn);
    }
}
?>