<?php

session_start();
$page = $_GET['page'] ?? 'login';

switch ($page) {
    case 'login':
        require_once __DIR__ . '/../modules/auth/login.php';
        break;

    case 'dashboard_pemilik':
        require_once __DIR__ . '/../modules/pemilik/dashboard.php';
        break;

    case 'dashboard-penghuni':
        require_once __DIR__ . '/../modules/penghuni/dashboard.php';
        break;

    default:
        http_response_code(404);
        echo "Halaman Tidak Ada!";
        break;
}