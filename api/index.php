<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page = $_GET['page'] ?? 'login';

// 1. Jika logout, langsung proses dan keluar
if ($page === 'logout') {
    require_once __DIR__ . '/../modules/auth/logout.php';
    exit;
}

// 2. Proteksi Autentikasi: Tamu (belum login) hanya boleh akses login dan reset
if (empty($_SESSION['user']) && !in_array($page, ['login', 'reset'], true)) {
    header("Location: /?page=login");
    exit;
}

// 3. Identifikasi Role Pengguna
$userRole = $_SESSION['user']['role'] ?? 'penghuni';
$is_pj    = !empty($_SESSION['user']['is_pj']) || !empty($_SESSION['user']['is_ketua_kos']) || $userRole === 'pj';

// Dashboard fallback berdasarkan role masing-masing
$defaultDashboard = ($userRole === 'pemilik') ? '/?page=dashboard_pemilik' : '/?page=dashboard_penghuni';

// 4. Jika sudah login tapi iseng membuka halaman login/reset, arahkan ke dashboard
if (!empty($_SESSION['user']) && in_array($page, ['login', 'reset'], true)) {
    header("Location: " . $defaultDashboard);
    exit;
}

// 5. MATRIKS HAK AKSES HALAMAN (RBAC)
// Tentukan role apa saja yang berhak membuka tiap halaman
$rolePermissions = [
    // Halaman Khusus Pemilik Kos Saja
    'dashboard_pemilik'      => ['pemilik'],
    'manajemen_kamar'        => ['pemilik'],
    'arus_kas'               => ['pemilik'],
    'analisis_kas'           => ['pemilik'],
    'edit_tatib'             => ['pemilik'],
    'cetak_kas'              => ['pemilik'],

    // Halaman yang Boleh Dibuka Pemilik & PJ (Verifikasi Pembayaran)
    'verifikasi_bayar'       => ['pemilik', 'pj'],
    'verifikasi_pembayaran'  => ['pemilik', 'pj'],

    // Halaman Khusus Penghuni & PJ (Bukan untuk Pemilik)
    'dashboard_penghuni'     => ['penghuni', 'pj'],
    'tagihan_riwayat'        => ['penghuni', 'pj'],
    'peraturan_kost'         => ['penghuni', 'pj'],

    // Halaman Bersama (Semua Role Boleh Akses)
    'chat_penghuni'          => ['pemilik', 'pj', 'penghuni'],
    'iuran'                  => ['pemilik', 'pj', 'penghuni'],
    'kelola_iuran'           => ['pemilik', 'pj', 'penghuni'],
    'monitoring_iuran'       => ['pemilik', 'pj', 'penghuni'],
    'cetak_iuran'            => ['pemilik', 'pj', 'penghuni'],
    'profile'                => ['pemilik', 'pj', 'penghuni'],
];

// 6. VALIDASI HAK AKSES URL
if (isset($rolePermissions[$page])) {
    $allowedRoles = $rolePermissions[$page];
    
    // Cek apakah role user ada di daftar izin
    $hasAccess = false;
    if (in_array($userRole, $allowedRoles, true)) {
        $hasAccess = true;
    } elseif ($is_pj && in_array('pj', $allowedRoles, true)) {
        $hasAccess = true;
    }

    // Jika tidak berhak (misal penghuni mengetik ?page=arus_kas atau ?page=manajemen_kamar)
    if (!$hasAccess) {
        // Opsi A: Lempar kembali ke dashboard miliknya dengan notifikasi URL terlarang
        header("Location: " . $defaultDashboard);
        exit;

        // Opsi B (alternatif jika ingin menampilkan pesan tegas):
        // http_response_code(403);
        // die("403 Forbidden: Anda tidak memiliki akses untuk membuka halaman ini.");
    }
}

// 7. ROUTING HALAMAN
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
        
    case 'analisis_kas':
        require_once __DIR__ . '/../modules/pemilik/analisis_kas.php';
        break;

    case 'cetak_kas':
        require_once __DIR__ . '/../modules/pemilik/cetak_kas.php';
        break;

    case 'cetak_iuran':
        require_once __DIR__ . '/../modules/shared/cetak_iuran.php';
        break;
        
    case 'verifikasi_bayar':
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