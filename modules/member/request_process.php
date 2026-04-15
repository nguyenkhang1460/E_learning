<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Redirect::to(Routes::DASHBOARD_USER);
}

$classId = (int)($_POST['class_id'] ?? 0);
$answers = $_POST['answers'] ?? [];

$service = new MemberService();
$result  = $service->createRequest($classId, Auth::userId(), $answers ?: null);

if ($result['success']) {
    Session::success($result['message']);
} else {
    Session::error($result['message']);
}

Redirect::to(Routes::DASHBOARD_USER);