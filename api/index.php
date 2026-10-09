<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page = $_GET['page'] ?? 'login';

switch ($page) {
    case 'login':
        require_once __DIR__ . '/../modules/auth/login.php';
        break;

    case 'dashboard_pemilik':
        require_once __DIR__ . '/../modules/pemilik/dashboard.php';
        break;

    case 'dashboard_penghuni':
        require_once __DIR__ . '/../modules/penghuni_Pj/dashboard.php';
        break;

    case 'verifikasi_pembayaran':
        require_once __DIR__ . '/../modules/pemilik/verifikasi_bayar/page.php';
        break;

     case 'arus_kas':
        require_once __DIR__ . '/../modules/pemilik/analisis_kas/page.php';
        break;

    case 'manajemen_kamar':
        require_once __DIR__ . '/../modules/pemilik/manajemen_kamar/page.php'; 
        break;
    
    case 'monitoring_tagihan_iuran':
        require_once __DIR__ . '/../modules/pemilik/monitor_tagihan_iuaran/page.php';
        break;
    
    case 'chat_penghuni':
        require_once __DIR__ . '/../modules/pemilik/chat_penghuni/page.php';
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