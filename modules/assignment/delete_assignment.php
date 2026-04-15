<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

Auth::requireLogin();

$assignmentId = (int)($_GET['id'] ?? 0);
if ($assignmentId <= 0) {
    Redirect::withError(Routes::DASHBOARD_USER, 'ID bài kiểm tra không hợp lệ!');
}

$db         = Database::getInstance();
$assignment = $db->fetchOne(
    'SELECT a.*, c.owner_id FROM assignments a JOIN classes c ON a.class_id = c.id WHERE a.id = ? LIMIT 1',
    [$assignmentId], 'i'
);

if (!$assignment) {
    Redirect::withError(Routes::DASHBOARD_USER, 'Bài kiểm tra không tồn tại!');
}

if (!Auth::isOwnerOrAdmin((int)$assignment['owner_id'])) {
    Session::error('Bạn không có quyền xóa bài kiểm tra này!');
    Redirect::to(Routes::manageClass((int)$assignment['class_id']));
}

// Kiểm tra đã có học viên nộp bài chưa
$submissionCount = $db->fetchOne(
    'SELECT COUNT(*) as total FROM submissions WHERE assignment_id = ?',
    [$assignmentId], 'i'
)['total'] ?? 0;

if ($submissionCount > 0) {
    Session::error("Không thể xóa! Đã có $submissionCount học viên nộp bài. Bài kiểm tra đã có người làm không được phép xóa.");
    Redirect::to(Routes::manageClass((int)$assignment['class_id']));
}

// Xóa quiz_questions liên quan
$db->execute('DELETE FROM quiz_questions WHERE assignment_id = ?', [$assignmentId], 'i');

// Xóa bài tập
$db->execute('DELETE FROM assignments WHERE id = ?', [$assignmentId], 'i');

Session::success('Đã xóa bài kiểm tra thành công!');
Redirect::to(Routes::manageClass((int)$assignment['class_id']));
