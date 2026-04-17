<?php
/**
 * Save Quiz — Lưu bài trắc nghiệm đã tạo
 * Đã fix: Thêm kiểm tra phân quyền (class ownership)
 */
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['class_id'])) {
    Redirect::to(Routes::DASHBOARD_USER);
}

$classId     = (int)$_POST['class_id'];
$title       = trim($_POST['title'] ?? '');
$difficulty  = $_POST['difficulty'] ?? 'medium';
$duration    = !empty($_POST['duration']) ? (int)$_POST['duration'] : null;
$deadline    = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
$showScore   = isset($_POST['show_score']) ? 1 : 0;
$questions   = $_POST['questions'] ?? [];
$options     = $_POST['options'] ?? [];
$corrects    = $_POST['correct_index'] ?? [];

if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
    $difficulty = 'medium';
}

// ── Kiểm tra phân quyền: chỉ owner hoặc admin mới được tạo quiz ────────
$classRepo = new ClassRepository();
$classInfo = $classRepo->findById($classId);

if (!$classInfo) {
    Session::error('Lớp học không tồn tại!');
    Redirect::to(Routes::DASHBOARD_USER);
}

if (!Auth::isOwnerOrAdmin((int)$classInfo['owner_id'])) {
    Session::error('Bạn không có quyền tạo bài quiz cho lớp này!');
    Redirect::to(Routes::DASHBOARD_USER);
}

$service = new QuizService();
$result  = $service->save($classId, $title, $difficulty, $duration, $deadline, $showScore, $questions, $options, $corrects);

if ($result['success']) {
    Session::success('✅ Đã đăng bài trắc nghiệm thành công!');
} else {
    Session::error($result['message']);
}

Redirect::to(Routes::manageClass($classId));