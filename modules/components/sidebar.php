<?php
// modules/components/sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

$role_user   = $_SESSION['user']['role'] ?? 'penghuni';
$is_pj       = !empty($_SESSION['user']['is_pj']) || !empty($_SESSION['user']['is_ketua_kos']);
$nama_user   = $_SESSION['user']['nama'] ?? ($role_user === 'pemilik' ? 'Bapak Kos' : 'Penghuni');
$currentPage = $_GET['page'] ?? 'dashboard';
?>
<aside class="desktop-sidebar">
    <!-- BAGIAN ATAS: BRAND, PROFIL & MENU NAVIGASI -->
    <div>
        <h1 class="sidebar-brand">SITEMAN - KOS</h1>
        <a href="/?page=profile" class="sidebar-profile <?= $currentPage === 'profile' ? 'active' : '' ?>">
            <div class="sidebar-avatar"><i class="fa-solid fa-user"></i></div>
            <span class="sidebar-name"><?= htmlspecialchars($nama_user); ?></span>
        </a>

        <nav class="sidebar-nav">
            <?php if ($role_user === 'pemilik'): ?>
                <a href="/?page=dashboard_pemilik" class="nav-item <?= $currentPage === 'dashboard_pemilik' ? 'active' : '' ?>">
                    <i class="fa-solid fa-table-cells-large"></i><span>Dashboard</span>
                </a>
                <a href="/?page=manajemen_kamar" class="nav-item <?= $currentPage === 'manajemen_kamar' ? 'active' : '' ?>">
                    <i class="fa-solid fa-warehouse"></i><span>Manajemen Kamar</span>
                </a>
                <a href="/?page=arus_kas" class="nav-item <?= $currentPage === 'arus_kas' ? 'active' : '' ?>">
                    <i class="fa-solid fa-wallet"></i><span>Analisis Kas</span>
                </a>
                <a href="/?page=edit_tatib" class="nav-item <?= $currentPage === 'edit_tatib' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book-open"></i><span>Manajemen Peraturan Kos</span>
                </a>
            <?php else: ?>
                <a href="/?page=dashboard_penghuni" class="nav-item <?= $currentPage === 'dashboard_penghuni' ? 'active' : '' ?>">
                    <i class="fa-solid fa-table-cells-large"></i><span>Dashboard</span>
                </a>
                <a href="/?page=tagihan_riwayat" class="nav-item <?= $currentPage === 'tagihan_riwayat' ? 'active' : '' ?>">
                    <i class="fa-solid fa-receipt"></i><span>Tagihan &amp; Riwayat</span>
                </a>
                <a href="/?page=peraturan_kost" class="nav-item <?= $currentPage === 'peraturan_kost' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book-open"></i><span>Peraturan Kos</span>
                </a>
            <?php endif; ?>

            <a href="/?page=chat_penghuni" class="nav-item <?= $currentPage === 'chat_penghuni' ? 'active' : '' ?>">
                <i class="fa-solid fa-comments"></i><span>Chat Penghuni</span>
            </a>

            <?php if ($role_user === 'pemilik' || $is_pj): ?>
                <a href="/?page=kelola_iuran" class="nav-item <?= $currentPage === 'kelola_iuran' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i><span>Kelola Iuran</span>
                </a>
                <a href="/?page=verifikasi_pembayaran" class="nav-item <?= $currentPage === 'verifikasi_pembayaran' ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-check"></i><span>Verifikasi Bayar</span>
                </a>
            <?php else: ?>
                <a href="/?page=monitoring_iuran" class="nav-item <?= $currentPage === 'monitoring_iuran' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i><span>Monitoring Iuran</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- BAGIAN BAWAH: TOMBOL KELUAR TEPAT DI ATAS FOOTER -->
    <div class="sidebar-bottom-group">
        <button type="button" class="btn-logout-sidebar" onclick="bukaModalLogout()">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Keluar</span>
        </button>

        <div class="sidebar-footer">
            <p class="sidebar-footer-title">SITEMAN - KOS &copy; 2g2b - 2026</p>
            <p class="sidebar-footer-desc">Sistem Manajemen Operasion Kos</p>
        </div>
    </div>
</aside>

<!-- MODAL POP-UP KONFIRMASI LOGOUT (JIKA HALAMAN TIDAK MEMUAT HEADER MOBILE) -->
<script>
    if (typeof bukaModalLogout !== 'function') {
        function bukaModalLogout() {
            const modal = document.getElementById('modalKonfirmasiLogout');
            if (modal) modal.classList.add('active');
        }
        function tutupModalLogout() {
            const modal = document.getElementById('modalKonfirmasiLogout');
            if (modal) modal.classList.remove('active');
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') tutupModalLogout();
        });
    }
</script>