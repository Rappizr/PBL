<?php
/**
 * SITEMAN-KOS — Front controller for Vercel
 * All page routes land here via vercel.json
 */
session_start();

$page = $_GET['page'] ?? 'login';

switch ($page) {
    case 'login':
        require_once __DIR__ . '/../modules/auth/login.php';
        break;

    case 'dashboard_pemilik':
        require_once __DIR__ . '/../modules/pemilik/dashboard.php';
        break;

    case 'dashboard_penghuni':
        require_once __DIR__ . '/../modules/penghuni/dashboard.php';
        break;

    case 'logout':
        session_destroy();
        header('Location: /?page=login');
        exit;

    default:
        http_response_code(404);
        require_once __DIR__ . '/../modules/components/404notfound.php';
        break;
}
