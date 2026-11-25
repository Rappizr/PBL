<?php
ob_start();

// Security Headers untuk Production
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

// Cegah eksekusi ganda akibat request otomatis /favicon.ico dari browser
if ($uriPath === '/favicon.ico') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/init.php';
/** @var PDO $pdo */

// Endpoint API Internal (Kompatibel Vercel & Apache Front-Controller)
if ($uriPath === '/api/notif_sidebar' || $uriPath === '/api/notif_sidebar.php') {
    if (ob_get_length()) ob_clean();
    require_once __DIR__ . '/notif_sidebar.php';
    exit;
}

if ($uriPath === '/api/ajukan_janji_bayar' || $uriPath === '/api/ajukan_janji_bayar.php') {
    if (ob_get_length()) ob_clean();
    require_once __DIR__ . '/ajukan_janji_bayar.php';
    exit;
}

$page = trim((string)($_GET['page'] ?? 'login'));

// 1. Jika logout, langsung proses dan keluar
if ($page === 'logout') {
    require_once __DIR__ . '/../modules/auth/logout.php';
    exit;
}

// 2. Proteksi Autentikasi: Tamu (belum login) hanya boleh akses login dan reset
if (empty($_SESSION['user']) && !in_array($page, ['login', 'reset'], true)) {
    header('Location: /?page=login');
    exit;
}

// 3. Identifikasi Role & Status Akun Pengguna
$userRole   = $_SESSION['user']['role'] ?? 'penghuni';
$is_pj      = !empty($_SESSION['user']['is_pj']) || !empty($_SESSION['user']['is_ketua_kos']) || $userRole === 'pj';
$statusAkun = strtolower(trim((string)($_SESSION['user']['status_akun'] ?? 'aktif')));

// Sinkronisasi status_akun penghuni dengan cache 30 detik (langsung cek DB jika POST atau di halaman akun_freeze)
if (!empty($_SESSION['user']) && $userRole !== 'pemilik') {
    $penghuniId   = (int)($_SESSION['user']['id'] ?? 0);
    $isPost       = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
    $lastSync     = (int)($_SESSION['user']['_status_synced_at'] ?? 0);
    $perluSyncDb  = $isPost || ($page === 'akun_freeze') || ((time() - $lastSync) >= 30);

    if ($penghuniId > 0 && $perluSyncDb) {
        if (isset($pdo) && ($pdo instanceof PDO)) {
            $stmtSt = $pdo->prepare("SELECT LOWER(TRIM(COALESCE(status_akun, 'aktif'))) FROM penghuni WHERE id = :id LIMIT 1");
            $stmtSt->execute([':id' => $penghuniId]);
            $dbSt = $stmtSt->fetchColumn();

            if ($dbSt) {
                $statusAkun = $dbSt;
                $_SESSION['user']['status_akun']       = $statusAkun;
                $_SESSION['user']['_status_synced_at'] = time();
            }
        }
    }
}

$defaultDashboard = ($userRole === 'pemilik')
    ? '/?page=dashboard_pemilik'
    : (($statusAkun === 'freeze') ? '/?page=akun_freeze' : '/?page=dashboard_penghuni');

// 4. Jika sudah login tapi membuka halaman login/reset, arahkan ke dashboard/halaman aktifnya
if (!empty($_SESSION['user']) && in_array($page, ['login', 'reset'], true)) {
    header('Location: ' . $defaultDashboard);
    exit;
}

// 4B. Gatekeeper Freeze: Penghuni berstatus freeze dikunci hanya di halaman akun_freeze
if (!empty($_SESSION['user']) && $userRole !== 'pemilik' && $statusAkun === 'freeze' && $page !== 'akun_freeze') {
    header('Location: /?page=akun_freeze');
    exit;
}

// 4C. Jika akun sudah aktif (di-unfreeze pemilik), jangan biarkan terjebak di akun_freeze
if (!empty($_SESSION['user']) && $userRole !== 'pemilik' && $statusAkun !== 'freeze' && $page === 'akun_freeze') {
    header('Location: /?page=dashboard_penghuni');
    exit;
}

// 5. Normalisasi Alias URL Lama ke Nama Route Tunggal yang Solid
$routeAliases = [
    'kelola_iuran'          => 'iuran',
    'monitoring_iuran'      => 'iuran',
    'arus_kas'              => 'analisis_kas',
    'verifikasi_pembayaran' => 'verifikasi_bayar',
];
if (isset($routeAliases[$page])) {
    $page = $routeAliases[$page];
}

// 6. KONFIGURASI ROUTING & RBAC TUNGGAL
$routes = [
    // Auth
    'login'              => ['file' => '/../modules/auth/login.php',                'roles' => ['*']],
    'reset'              => ['file' => '/../modules/auth/reset.php',                'roles' => ['*']],

    // Pemilik Kos
    'dashboard_pemilik'  => ['file' => '/../modules/pemilik/dashboard.php',         'roles' => ['pemilik']],
    'manajemen_kamar'    => ['file' => '/../modules/pemilik/manajemen_kamar.php',   'roles' => ['pemilik']],
    'analisis_kas'       => ['file' => '/../modules/pemilik/analisis_kas.php',      'roles' => ['pemilik']],
    'cetak_kas'          => ['file' => '/../modules/pemilik/cetak_kas.php',         'roles' => ['pemilik']],
    'edit_tatib'         => ['file' => '/../modules/pemilik/edit_tatib.php',        'roles' => ['pemilik']],

    // Penghuni & PJ
    'dashboard_penghuni' => ['file' => '/../modules/penghuni/dashboard.php',        'roles' => ['penghuni', 'pj']],
    'tagihan_riwayat'    => ['file' => '/../modules/penghuni/tagihan_riwayat.php',  'roles' => ['penghuni', 'pj']],
    'peraturan_kost'     => ['file' => '/../modules/penghuni/peraturan_kost.php',   'roles' => ['penghuni', 'pj']],
    'cetak_invoice'      => ['file' => '/../modules/penghuni/cetak_invoice.php',    'roles' => ['penghuni', 'pj']],
    'akun_freeze'        => ['file' => '/../modules/penghuni/akun_freeze.php',      'roles' => ['penghuni', 'pj']],

    // Shared (Pemilik, PJ, Penghuni)
    'verifikasi_bayar'   => ['file' => '/../modules/shared/verifikasi_bayar.php',   'roles' => ['pemilik', 'pj']],
    'chat_penghuni'      => ['file' => '/../modules/shared/chat_penghuni.php',      'roles' => ['pemilik', 'pj', 'penghuni']],
    'iuran'              => ['file' => '/../modules/shared/iuran.php',              'roles' => ['pemilik', 'pj', 'penghuni']],
    'cetak_iuran'        => ['file' => '/../modules/shared/cetak_iuran.php',        'roles' => ['pemilik', 'pj', 'penghuni']],
    'profile'            => ['file' => '/../modules/shared/profile.php',            'roles' => ['pemilik', 'pj', 'penghuni']],
];

// 7. EKSEKUSI VALIDASI RBAC & LOAD HALAMAN
if (!isset($routes[$page])) {
    http_response_code(404);
    if (ob_get_length()) ob_clean();
    require_once __DIR__ . '/../modules/components/404notfound.php';
    exit;
}

$targetRoute  = $routes[$page];
$allowedRoles = $targetRoute['roles'];

if (!in_array('*', $allowedRoles, true)) {
    $hasAccess = in_array($userRole, $allowedRoles, true) || ($is_pj && in_array('pj', $allowedRoles, true));

    if (!$hasAccess) {
        header('Location: ' . $defaultDashboard);
        exit;
    }
}

$targetFile = __DIR__ . $targetRoute['file'];
if (!file_exists($targetFile)) {
    http_response_code(500);
    if (ob_get_length()) ob_clean();
    die('Halaman tidak tersedia.');
}

// Bersihkan spasi/BOM tersembunyi sebelum merender HTML halaman
if (ob_get_length()) {
    ob_clean();
}

require_once $targetFile;