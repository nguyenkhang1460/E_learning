<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

Auth::requireLogin();

$classId = (int)($_GET['id'] ?? 0);
if ($classId <= 0) {
    Redirect::withError(Routes::DASHBOARD_USER, 'Lớp học không hợp lệ!');
}

$service = new ClassService();
$result  = $service->delete($classId, Auth::userId(), Auth::isAdmin());

if ($result['success']) {
    Session::success($result['message']);
} else {
    Session::error($result['message']);
}

Redirect::to(Routes::DASHBOARD_USER);
