<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['btn_upload'])) {
    Redirect::back();
}

// Load Cloudinary config
$apiConfigFile = __DIR__ . '/../../config/api_keys.php';
if (!file_exists($apiConfigFile)) {
    Session::error('Thiếu file cấu hình API. Vui lòng tạo config/api_keys.php từ file mẫu.');
    Redirect::back();
}

$apiConfig      = require $apiConfigFile;
$cloudinary     = $apiConfig['cloudinary'] ?? [];
$classId        = (int)($_POST['class_id'] ?? 0);
$title          = trim($_POST['title'] ?? '');

if ($classId <= 0) {
    Redirect::withError(Routes::DASHBOARD_USER, 'ID lớp học không hợp lệ!');
}

if (!isset($_FILES['lecture_file']) || $_FILES['lecture_file']['error'] !== UPLOAD_ERR_OK) {
    Session::error('Lỗi upload file. Vui lòng thử lại!');
    Redirect::to(Routes::manageClass($classId));
}

$service = new LectureService();
$result  = $service->uploadAndSave($classId, $title, $_FILES['lecture_file'], $cloudinary);

if ($result['success']) {
    Session::success($result['message']);
} else {
    Session::error($result['message']);
}

Redirect::to(Routes::manageClass($classId));