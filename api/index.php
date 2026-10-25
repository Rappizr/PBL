<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page = $_GET['page'] ?? 'login';
$role = $_SESSION['user']['role'] ?? 'penghuni'; // Deteksi role pengguna aktif

switch ($page) {
    case 'login':
        require_once __DIR__ . '/../modules/auth/login.php';
        break;

    case 'dashboard_pemilik':
        require_once __DIR__ . '/../modules/pemilik/dashboard.php';
        break;

    case 'dashboard_penghuni':
    case 'penghuni-dashboard':
        require_once __DIR__ . '/../modules/penghuni_Pj/dashboard.php';
        break;

    case 'verifikasi_pembayaran':
    case 'verifikasi_bayar':
        require_once __DIR__ . '/../modules/pemilik/verifikasi_bayar/page.php';
        break;

    case 'arus_kas':
        require_once __DIR__ . '/../modules/pemilik/analisis_kas/page.php';
        break;

    case 'manajemen_kamar':
        require_once __DIR__ . '/../modules/pemilik/manajemen_kamar/page.php'; 
        break;
    
    case 'kelola_iuran':
    case 'pj-kelola-iuran':
        require_once __DIR__ . '/../modules/pemilik/kelola_iuran/page.php';
        break;
    
    case 'chat_penghuni':
        // Arahkan otomatis berdasarkan role yang sedang login
        if ($role === 'pemilik') {
            require_once __DIR__ . '/../modules/pemilik/chat_penghuni/page.php';
        } else {
            require_once __DIR__ . '/../modules/penghuni_Pj/chat_penghuni/page.php';
        }
        break;
        
    case 'tagihan_riwayat':
        require_once __DIR__ . '/../modules/penghuni_Pj/tagihan_riwayat/page.php';
        break;
    
    case 'peraturan_kost':
        require_once __DIR__ . '/../modules/penghuni_Pj/peraturan_kost/page.php';
        break;
    
    case 'monitoring_iuran':
        require_once __DIR__ . '/../modules/penghuni_Pj/monitoring_iuran/page.php';
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