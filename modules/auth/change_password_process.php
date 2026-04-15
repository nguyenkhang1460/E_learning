<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Redirect::to(Routes::CHANGE_PASSWORD);
}

$service = new AuthService();
$result  = $service->changePassword(
    Auth::userId(),
    $_POST['current_password'] ?? '',
    $_POST['new_password'] ?? '',
    $_POST['confirm_password'] ?? ''
);

if ($result['success']) {
    Session::success($result['message']);
} else {
    Session::error($result['message']);
}

Redirect::to(Routes::CHANGE_PASSWORD);
