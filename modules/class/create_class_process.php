<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['btn_create'])) {
    Redirect::to(Routes::CLASS_CREATE);
}

$service = new ClassService();
$result  = $service->create(
    trim($_POST['class_name'] ?? ''),
    trim($_POST['description'] ?? ''),
    $_POST['class_type'] ?? 'public',
    Auth::userId(),
    $_POST['requirements'] ?? null
);

if ($result['success']) {
    Session::success('Đã tạo lớp học thành công!');
    Redirect::to(Routes::DASHBOARD_USER);
} else {
    Session::error('Lỗi khi tạo lớp học. Vui lòng thử lại.');
    Redirect::to(Routes::CLASS_CREATE);
}