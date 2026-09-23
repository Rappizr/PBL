<?php

session_start();
$page = $_GET['page'] ?? 'login';

switch ($page) {
    case 'login':
        require_once __DIR__ . '/../modules/auth/login.php';
        break;

    case 'dashboard-pemilik':
        require_once __DIR__ . '/../modules/pemilik/dashboard.php';
        break;

    case 'dashboard-penghuni':
        require_once __DIR__ . '/../modules/penghuni/dashboard.php';
        break;

    case 'logout':
        session_destroy();
        header("Location: index.php?page=login");
        exit;
        
    default:
        http_response_code(404);
        require_once __DIR__ . '/../modules/components/404notfound.php';
        break;
}