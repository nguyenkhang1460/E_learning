<?php
// 1. Cấu hình ban đầu - Tắt hiển thị lỗi trực tiếp để không làm hỏng JSON
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

session_start();
include 'db_config.php';
require_once __DIR__ . '/vendor/autoload.php';

use Smalot\PdfParser\Parser as PdfParser;

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Bạn cần đăng nhập để thực hiện tác vụ này."]);
    exit();
}

// CẤU HÌNH GROQ API
$api_config_file = __DIR__ . '/config/api_keys.php';
if (!file_exists($api_config_file)) {
    echo json_encode(["error" => "Thiếu file cấu hình API. Vui lòng tạo config/api_keys.php từ file mẫu."]);
    exit();
}

$api_config = require $api_config_file;
$groq_config = $api_config['groq'] ?? [];

$GROQ_API_KEY      = $groq_config['api_key'] ?? '';
$GROQ_ENDPOINT     = $groq_config['endpoint'] ?? 'https://api.groq.com/openai/v1/chat/completions';
$GROQ_TEXT_MODEL   = $groq_config['text_model'] ?? 'llama-3.3-70b-versatile';
$GROQ_VISION_MODEL = $groq_config['vision_model'] ?? 'llama-3.2-90b-vision-preview';

if (empty($GROQ_API_KEY)) {
    echo json_encode(["error" => "Chưa cấu hình GROQ API key trong config/api_keys.php"]);
    exit();
}

// HÀM GỌI GROQ API

function callGroq(string $api_key, string $endpoint, array $payload): ?array {
    $json_payload = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );

    if ($json_payload === false) {
        return [
            'error' => [
                'message' => 'Không thể mã hóa JSON request gửi đến Groq: ' . json_last_error_msg()
            ]
        ];
    }

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST,           true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     $json_payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT,        120);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ]);
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) return null;

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'error' => [
                'message' => 'Response từ Groq không phải JSON hợp lệ: ' . json_last_error_msg()
            ]
        ];
    }

    return $decoded;
}

try {
    // Lấy thông tin từ AJAX gửi lên
    $class_id          = intval($_GET['class_id'] ?? $_POST['class_id'] ?? 0);
    $source_lecture_id = intval($_GET['lecture_id'] ?? 0);
    $num_questions     = max(1, min(20, intval($_GET['num'] ?? $_POST['num'] ?? 5)));
    $difficulty        = $_GET['difficulty'] ?? $_POST['difficulty'] ?? 'medium';

    // Kiểm tra xem có file upload không
    $isUpload = isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK;
    
    if($isUpload) {
        // ── Xử lý file upload ──
        $uploadedFile = $_FILES['upload_file'];
        
        // Kiểm tra lỗi upload
        if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'File vượt quá kích thước cho phép trong php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File vượt quá kích thước cho phép trong form',
                UPLOAD_ERR_PARTIAL => 'File chỉ được upload một phần',
                UPLOAD_ERR_NO_FILE => 'Không có file nào được upload',
                UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
                UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file vào đĩa',
                UPLOAD_ERR_EXTENSION => 'Extension PHP đã dừng upload'
            ];
            $error_msg = $upload_errors[$uploadedFile['error']] ?? 'Lỗi upload không xác định';
            throw new Exception("Lỗi upload file: " . $error_msg);
        }
        
        $file_content = file_get_contents($uploadedFile['tmp_name']);
        $fileName = $uploadedFile['name'];
        
        if(!$file_content) {
            throw new Exception("Không thể đọc nội dung file. File có thể bị hỏng hoặc rỗng.");
        }
        
        // Xác định loại file
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $lecture_type = $fileExtension;
        
        // Kiểm tra định dạng hợp lệ
        $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png'];
        if(!in_array($lecture_type, $allowedTypes)) {
            throw new Exception("Định dạng file '$fileExtension' không được hỗ trợ. Chỉ chấp nhận: PDF, JPG, PNG");
        }
        
        // Log thông tin file (để debug)
        error_log("Upload file: $fileName, Type: $lecture_type, Size: " . strlen($file_content) . " bytes");
        
    } else {
        // ── Lấy thông tin tài liệu từ DB ──
        if(!$source_lecture_id) {
            throw new Exception("Vui lòng chọn tài liệu hoặc upload file!");
        }
        
        $sql_lec = "SELECT * FROM lectures WHERE id = $source_lecture_id LIMIT 1";
        $res_lec = mysqli_query($conn, $sql_lec);
        $lecture = mysqli_fetch_assoc($res_lec);

        if (!$lecture) throw new Exception("Không tìm thấy tài liệu trong hệ thống!");

        $lecture_url  = $lecture['file_url'];
        $lecture_type = strtolower($lecture['file_type'] ?? 'pdf');
        $file_content = @file_get_contents($lecture_url);

        if (!$file_content) throw new Exception("Không thể tải tài liệu từ máy chủ lưu trữ!");
    }

    // ── Sử dụng Prompt "Khủng" của Khang để đảm bảo chất lượng ──
    $prompt_text = "NHIỆM VỤ: Tạo $num_questions câu hỏi trắc nghiệm chất lượng cao, độ khó $difficulty. 
                    Trả về DUY NHẤT một JSON object mảng 'questions'. 
                    Mỗi câu có: 'question', 'options' (mảng 4 câu bắt đầu bằng A., B., C., D.), 'correct_index' (0-3), và 'explanation'.";

    // ── Xử lý PDF hoặc Ảnh (Logic Vision của Khang) ──
    if ($lecture_type === 'pdf') {
        $parser   = new PdfParser();
        $pdf      = $parser->parseContent($file_content);
        $doc_text = mb_convert_encoding($pdf->getText(), 'UTF-8', 'UTF-8');
        $doc_text = mb_substr($doc_text, 0, 10000); // Giới hạn để tránh quá tải
        
        $payload = [
            'model'       => $GROQ_TEXT_MODEL,
            'temperature' => 0.3,
            'messages'    => [
                ['role' => 'system', 'content' => 'Bạn là chuyên gia giáo dục. Chỉ trả về JSON thuần.'],
                ['role' => 'user', 'content' => "Nội dung: $doc_text \n\n $prompt_text"]
            ],
            'response_format' => ['type' => 'json_object']
        ];
    } else {
        // Xử lý Ảnh (Vision API)
        $image_b64 = base64_encode($file_content);
        
        // Xác định MIME type dựa vào lecture_type
        if ($lecture_type === 'png') {
            $mime = 'image/png';
        } elseif (in_array($lecture_type, ['jpg', 'jpeg'])) {
            $mime = 'image/jpeg';
        } else {
            // Fallback
            $mime = 'image/jpeg';
        }
        
        $payload = [
            'model'    => $GROQ_VISION_MODEL,
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'image_url', 'image_url' => ['url' => "data:$mime;base64,$image_b64"]],
                    ['type' => 'text', 'text' => $prompt_text]
                ]
            ]],
            'response_format' => ['type' => 'json_object']
        ];
    }

    // ── Gọi Groq ──
    $groq_data = callGroq($GROQ_API_KEY, $GROQ_ENDPOINT, $payload);
    
    if (!$groq_data || isset($groq_data['error'])) {
        $error_msg = $groq_data['error']['message'] ?? "Lỗi kết nối API Groq.";
        throw new Exception($error_msg);
    }
    
    // Kiểm tra cấu trúc response
    if (!isset($groq_data['choices'][0]['message']['content'])) {
        throw new Exception("Response từ Groq API không đúng cấu trúc. Vui lòng thử lại.");
    }

    // Làm sạch và trả kết quả JSON về cho AJAX
    $raw_content = $groq_data['choices'][0]['message']['content'];
    $clean_json  = preg_replace('/^```(?:json)?\s*/i', '', trim($raw_content));
    $clean_json  = preg_replace('/\s*```$/', '', $clean_json);

    // Fallback: nếu AI trả thêm text thừa, cố gắng lấy JSON object đầu tiên
    if (!(str_starts_with(trim($clean_json), '{') && str_ends_with(trim($clean_json), '}'))) {
        if (preg_match('/\{.*\}/s', $clean_json, $matches)) {
            $clean_json = $matches[0];
        }
    }
    
    // Validate JSON trước khi trả về
    $decoded = json_decode($clean_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("AI trả về dữ liệu không phải JSON hợp lệ: " . json_last_error_msg());
    }
    
    // Kiểm tra có questions array không
    if (!isset($decoded['questions']) || !is_array($decoded['questions'])) {
        throw new Exception("AI không tạo được câu hỏi. Vui lòng thử lại hoặc chọn file khác.");
    }
    
    if (count($decoded['questions']) === 0) {
        throw new Exception("AI không tạo được câu hỏi nào từ tài liệu này. Vui lòng thử file khác hoặc tăng nội dung tài liệu.");
    }

    echo $clean_json; // Dữ liệu sẽ đổ về reviewZone trong create_assignment.php

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}