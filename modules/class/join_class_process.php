<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Redirect::to(Routes::CLASS_JOIN);
}

$inviteCode = trim($_POST['invite_code'] ?? '');
$service    = new ClassService();
$result     = $service->joinByCode($inviteCode, Auth::userId());

if (!$result['success']) {
    Session::error($result['message']);
    Redirect::to(Routes::CLASS_JOIN);
}

if (!empty($result['needRequest'])) {
    // Lớp private — chuyển sang trang điền thông tin yêu cầu
    $classId = (int)$result['class']['id'];
    Redirect::to('/E_learning/modules/class/request_join.php?class_id=' . $classId);
}

// Lớp public — vào thẳng
Session::success($result['message']);
Redirect::to(Routes::manageClass($result['classId']));