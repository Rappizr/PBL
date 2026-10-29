<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page = $_GET['page'] ?? 'login';
$role = $_SESSION['user']['role'] ?? 'penghuni';

switch ($page) {
    case 'login':
        require_once __DIR__ . '/../modules/auth/login.php';
        break;

    // Pemilik
    case 'dashboard_pemilik':
        require_once __DIR__ . '/../modules/pemilik/dashboard.php';
        break;
    case 'manajemen_kamar':
        require_once __DIR__ . '/../modules/pemilik/manajemen_kamar.php';
        break;
    case 'arus_kas':
        require_once __DIR__ . '/../modules/pemilik/analisis_kas.php';
        break;
    case 'verifikasi_pembayaran':
        require_once __DIR__ . '/../modules/pemilik/verifikasi_bayar.php';
        break;

    // Penghuni
    case 'dashboard_penghuni':
        require_once __DIR__ . '/../modules/penghuni/dashboard.php';
        break;
    case 'tagihan_riwayat':
        require_once __DIR__ . '/../modules/penghuni/tagihan_riwayat.php';
        break;
    case 'peraturan_kost':
        require_once __DIR__ . '/../modules/penghuni/peraturan_kost.php';
        break;

    // Bersama (Shared)
    case 'chat_penghuni':
        require_once __DIR__ . '/../modules/shared/chat_penghuni.php';
        break;
    case 'kelola_iuran':
    case 'monitoring_iuran':
        require_once __DIR__ . '/../modules/shared/iuran.php';
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