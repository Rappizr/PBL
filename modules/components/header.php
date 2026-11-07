<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper untuk mengambil nama depan saja
function getNamaDepan(string $fullName): string {
    $parts = explode(' ', trim($fullName));
    return $parts[0] ?? $fullName;
}

// Proses Logout jika dikonfirmasi
if (isset($_GET['aksi']) && $_GET['aksi'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: /?page=login");
    exit;
}

// Sinkronisasi Real-Time Nama & Status dari Database
global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $possible_db = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../../../config/database.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/database.php'
    ];
    foreach ($possible_db as $p) {
        if (file_exists($p)) {
            require_once $p;
            break;
        }
    }
}

if (isset($pdo) && ($pdo instanceof PDO) && !empty($_SESSION['user']['id'])) {
    $uid  = (int)$_SESSION['user']['id'];
    $role = $_SESSION['user']['role'] ?? 'penghuni';

    try {
        if ($role !== 'pemilik') {
            $stmtSync = $pdo->prepare("SELECT nama, is_ketua_kos FROM penghuni WHERE id = :id LIMIT 1");
            $stmtSync->execute([':id' => $uid]);
            $freshUser = $stmtSync->fetch(PDO::FETCH_ASSOC);

            if ($freshUser) {
                $_SESSION['user']['nama']         = $freshUser['nama'];
                $_SESSION['user']['is_ketua_kos'] = (bool)$freshUser['is_ketua_kos'];
                $_SESSION['user']['is_pj']        = (bool)$freshUser['is_ketua_kos'];
            }
        }
    } catch (Exception $e) {}
}

// Ambil session user yang sudah diperbarui
$currentUser = $_SESSION['user'] ?? [
    'role'  => 'penghuni',
    'nama'  => 'Anak Kos',
    'kamar' => null,
    'is_pj' => false
];

$is_pemilik = ($currentUser['role'] ?? '') === 'pemilik';
$is_pj      = !$is_pemilik && (!empty($currentUser['is_pj']) || !empty($currentUser['is_ketua_kos']) || ($currentUser['role'] ?? '') === 'pj');

// Ambil Nama Depan Penghuni
$namaDisplay = getNamaDepan($currentUser['nama'] ?? 'Penghuni');
?>

<header class="dashboard-header">
    <h1 class="brand-title-dashboard">SITEMAN&nbsp;-&nbsp;KOS</h1>

    <div class="header-user-actions">
        <?php if ($is_pemilik): ?>
            <a href="/?page=profile" class="kotak-kapsul-pemilik header-user-link" title="<?= htmlspecialchars($currentUser['nama'] ?? 'Bapak Kos'); ?>">
                <span class="lingkaran-user-abu">
                    <i class="fa-solid fa-user"></i>
                </span>
                <span class="tulisan-nama"><?= htmlspecialchars($namaDisplay); ?></span>
            </a>

        <?php elseif ($is_pj): ?>
            <a href="/?page=profile" class="kotak-kapsul-pemilik badge-pj-header header-user-link" title="<?= htmlspecialchars($currentUser['nama'] ?? 'Ketua Kos'); ?>">
                <span class="lingkaran-user-abu">
                    <i class="fa-solid fa-user-shield"></i>
                </span>
                <span class="tulisan-nama">
                    <?= htmlspecialchars($namaDisplay); ?>
                    <?= !empty($currentUser['kamar']) ? '(' . htmlspecialchars($currentUser['kamar']) . ')' : ''; ?>
                </span>
            </a>

        <?php else: ?>
            <a href="/?page=profile" class="kotak-kapsul-pemilik badge-penghuni-header header-user-link" title="<?= htmlspecialchars($currentUser['nama'] ?? 'Penghuni'); ?>">
                <span class="lingkaran-user-abu">
                    <i class="fa-solid fa-user"></i>
                </span>
                <span class="tulisan-nama">
                    <?= htmlspecialchars($namaDisplay); ?>
                </span>
            </a>
        <?php endif; ?>

        <!-- Tombol Ikon Keluar di Sebelah Kanan Profil Mobile -->
        <button type="button" class="btn-logout-mobile" onclick="bukaModalLogout()" title="Keluar">
            <i class="fa-solid fa-right-from-bracket"></i>
        </button>
    </div>
</header>