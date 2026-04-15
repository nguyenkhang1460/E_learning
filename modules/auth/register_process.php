<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['register'])) {
    Redirect::to(Routes::REGISTER);
}

$service = new AuthService();
$result  = $service->register(
    trim($_POST['full_name'] ?? ''),
    trim($_POST['username'] ?? ''),
    trim($_POST['email'] ?? ''),
    $_POST['password'] ?? '',
    $_POST['confirm_password'] ?? ''
);

if ($result['success']) {
    Redirect::to(Routes::REGISTER . '?success=1');
} else {
    Redirect::to(Routes::REGISTER . '?error=' . ($result['error'] ?? 'unknown'));
}