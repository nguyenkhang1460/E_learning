<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

Auth::requireLogin();

$assignmentId = (int)($_GET['assignment_id'] ?? 0);
if ($assignmentId <= 0) {
    Redirect::withError(Routes::DASHBOARD_USER, 'ID bài tập không hợp lệ.');
}

$service = new GradeService();
$service->exportCsv($assignmentId, Auth::userId(), Auth::isAdmin());
