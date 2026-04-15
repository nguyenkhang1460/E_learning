<?php
// 1. Tắt display_errors — không được làm hỏng JSON output
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // smalot/pdfparser

use Smalot\PdfParser\Parser as PdfParser;

// Kiểm tra đăng nhập
if (!Auth::check()) {
    echo json_encode(['error' => 'Bạn cần đăng nhập để thực hiện tác vụ này.']);
    exit();
}

// Load Groq API config
$apiConfigFile = __DIR__ . '/../../config/api_keys.php';
if (!file_exists($apiConfigFile)) {
    echo json_encode(['error' => 'Thiếu file cấu hình API. Vui lòng tạo config/api_keys.php từ file mẫu.']);
    exit();
}

$apiConfig  = require $apiConfigFile;
$groqConfig = $apiConfig['groq'] ?? [];

if (empty($groqConfig['api_key'])) {
    echo json_encode(['error' => 'Chưa cấu hình GROQ API key trong config/api_keys.php']);
    exit();
}

try {
    $numQuestions = max(1, min(20, (int)($_GET['num'] ?? $_POST['num'] ?? 5)));
    $difficulty   = $_GET['difficulty'] ?? $_POST['difficulty'] ?? 'medium';
    $lectureId    = (int)($_GET['lecture_id'] ?? 0);
    $isUpload     = isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK;

    $service   = new QuizService();
    $uploadFile = $isUpload ? $_FILES['upload_file'] : null;

    $data = $service->generateWithAI($groqConfig, $lectureId ?: null, $uploadFile, $numQuestions, $difficulty);

    if (isset($data['error'])) {
        throw new Exception($data['error']);
    }

    if (!isset($data['questions']) || !is_array($data['questions']) || count($data['questions']) === 0) {
        throw new Exception('AI không tạo được câu hỏi. Vui lòng thử lại hoặc chọn file khác.');
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}