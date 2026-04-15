<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

// FIX BẢO MẬT: Bắt buộc đăng nhập + kiểm tra quyền owner trong MemberService
Auth::requireLogin();

$requestId  = (int)($_GET['id'] ?? 0);
$status     = $_GET['status'] ?? '';
$classId    = (int)($_GET['class_id'] ?? 0);

if ($requestId <= 0) {
    Redirect::withError(Routes::DASHBOARD_USER, 'Yêu cầu không hợp lệ!');
}

$service = new MemberService();
$result  = $service->handleRequest($requestId, $status, Auth::userId(), Auth::isAdmin());

if ($result['success']) {
    Session::success($result['message']);
} else {
    Session::error($result['message']);
}

$redirectClassId = $classId > 0 ? $classId : ($result['classId'] ?? 0);
Redirect::to(Routes::manageRequests($redirectClassId));