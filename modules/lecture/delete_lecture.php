<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

Auth::requireLogin();

$lectureId = (int)($_GET['id'] ?? 0);
$classId   = (int)($_GET['class_id'] ?? 0);

if ($lectureId <= 0) {
    Redirect::withError(Routes::DASHBOARD_USER, 'ID tài liệu không hợp lệ!');
}

// Kiểm tra quyền qua DB
$db      = Database::getInstance();
$lecture = $db->fetchOne(
    'SELECT l.*, c.owner_id FROM lectures l JOIN classes c ON l.class_id = c.id WHERE l.id = ? LIMIT 1',
    [$lectureId], 'i'
);

if (!$lecture) {
    Redirect::withError(Routes::DASHBOARD_USER, 'Tài liệu không tồn tại!');
}

if (!Auth::isOwnerOrAdmin((int)$lecture['owner_id'])) {
    Redirect::withError(Routes::manageClass((int)$lecture['class_id']), 'Bạn không có quyền xóa tài liệu này!');
}

$service = new LectureService();
$result  = $service->delete($lectureId, (int)$lecture['class_id']);

if ($result['success']) {
    Session::success('Đã xóa bài giảng thành công!');
} else {
    Session::error($result['message']);
}

Redirect::to(Routes::manageClass((int)$lecture['class_id']));
