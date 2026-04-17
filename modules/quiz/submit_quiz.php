<?php
/**
 * Submit Quiz — Xử lý nộp bài trắc nghiệm
 * Đã fix: SQL Injection, dùng QuizService thay vì raw mysqli
 */
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['assignment_id'])) {
    Redirect::to(Routes::DASHBOARD_USER);
}

$assignmentId = (int)$_POST['assignment_id'];
if ($assignmentId <= 0) {
    Redirect::withError(Routes::DASHBOARD_USER, 'Bài tập không hợp lệ!');
}

$service = new QuizService();
$result  = $service->grade($assignmentId, Auth::userId(), $_POST);

if (!$result['success']) {
    Redirect::withError(Routes::DASHBOARD_USER, $result['message']);
}

// Lưu kết quả vào session để view hiển thị
$_SESSION['quiz_result'] = $result;
Redirect::to(Routes::QUIZ_RESULT);