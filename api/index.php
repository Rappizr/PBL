<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page = $_GET['page'] ?? 'login';

// Jika halaman yang dituju adalah logout, langsung proses logout
if ($page === 'logout') {
    require_once __DIR__ . '/../modules/auth/logout.php';
    exit;
}

// Jika pengguna belum login (sesi kosong) dan mencoba membuka halaman selain login/reset, lempar ke login
if (empty($_SESSION['user']) && !in_array($page, ['login', 'reset'])) {
    header("Location: /?page=login");
    exit;
}

$role = $_SESSION['user']['role'] ?? 'penghuni';

switch ($page) {
    case 'login':
        require_once __DIR__ . '/../modules/auth/login.php';
        break;
    
    case 'reset':
        require_once __DIR__ . '/../modules/auth/reset.php';
        break;

    case 'dashboard_pemilik':
        require_once __DIR__ . '/../modules/pemilik/dashboard.php';
        break;
        
    case 'manajemen_kamar':
        require_once __DIR__ . '/../modules/pemilik/manajemen_kamar.php';
        break;
        
    case 'arus_kas':
    case 'analisis_kas':
        require_once __DIR__ . '/../modules/pemilik/analisis_kas.php';
        break;
        
    case 'verifikasi_bayar':
    case 'verifikasi_pembayaran':
        require_once __DIR__ . '/../modules/shared/verifikasi_bayar.php';
        break;

    case 'dashboard_penghuni':
        require_once __DIR__ . '/../modules/penghuni/dashboard.php';
        break;
        
    case 'tagihan_riwayat':
        require_once __DIR__ . '/../modules/penghuni/tagihan_riwayat.php';
        break;
        
    case 'peraturan_kost':
        require_once __DIR__ . '/../modules/penghuni/peraturan_kost.php';
        break;
        
    case 'edit_tatib':
        require_once __DIR__ . '/../modules/pemilik/edit_tatib.php';
        break;

    case 'chat_penghuni':
        require_once __DIR__ . '/../modules/shared/chat_penghuni.php';
        break;
        
    case 'iuran':                     
    case 'kelola_iuran':
    case 'monitoring_iuran':
        require_once __DIR__ . '/../modules/shared/iuran.php';
        break;

    case 'profile':
        require_once __DIR__ . '/../modules/shared/profile.php';
        break;

    default:
        http_response_code(404);
        require_once __DIR__ . '/../modules/components/404notfound.php';
        break;
}