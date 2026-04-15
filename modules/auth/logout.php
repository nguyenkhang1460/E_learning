<?php
session_start();
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../core/autoload.php';

(new AuthService())->logout();
Redirect::to(Routes::LOGIN);
