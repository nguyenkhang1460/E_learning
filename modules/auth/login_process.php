<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['login'])) {
    Redirect::to(Routes::LOGIN);
}

$service = new AuthService();
$result  = $service->login($_POST['username'] ?? '', $_POST['password'] ?? '');

if ($result['success']) {
    Redirect::to($result['role'] === 'admin' ? Routes::DASHBOARD_ADMIN : Routes::DASHBOARD_USER);
} else {
    Session::error($result['message']);
    Redirect::to(Routes::LOGIN);
}