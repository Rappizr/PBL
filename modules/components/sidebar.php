<?php
// modules/components/sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    <div>
        <h1 class="sidebar-brand">SITEMAN - KOS</h1>
        <a href="/?page=profile" class="sidebar-profile <?= $currentPage === 'profile' ? 'active' : '' ?>">
            <div class="sidebar-avatar"><i class="fa-solid fa-user"></i></div>
            <span class="sidebar-name"><?= htmlspecialchars($nama_user); ?></span>
        </a>

        <nav class="sidebar-nav">
            <?php if ($role_user === 'pemilik'): ?>
                <a href="/?page=dashboard_pemilik" class="nav-item <?= in_array($currentPage, ['dashboard_pemilik', 'dashboard']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-table-cells-large"></i><span>Dashboard</span>
                </a>
                <a href="/?page=manajemen_kamar" class="nav-item <?= $currentPage === 'manajemen_kamar' ? 'active' : '' ?>">
                    <i class="fa-solid fa-warehouse"></i><span>Manajemen Kamar & Penghuni</span>
                </a>
                <a href="/?page=analisis_kas" class="nav-item <?= $currentPage === 'analisis_kas' ? 'active' : '' ?>">
                    <i class="fa-solid fa-wallet"></i><span>Analisis Kas</span>
                </a>
                <a href="/?page=edit_tatib" class="nav-item <?= $currentPage === 'edit_tatib' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book-open"></i><span>Manajemen Peraturan Kos</span>
                </a>
            <?php else: ?>
                <a href="/?page=dashboard_penghuni" class="nav-item <?= in_array($currentPage, ['dashboard_penghuni', 'dashboard']) ? 'active' : '' ?>">
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
                <a href="/?page=kelola_iuran" class="nav-item <?= in_array($currentPage, ['kelola_iuran', 'iuran']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i><span>Kelola Iuran</span>
                </a>
                <a href="/?page=verifikasi_bayar" class="nav-item <?= $currentPage === 'verifikasi_bayar' ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-check"></i><span>Verifikasi Bayar</span>
                </a>
            <?php else: ?>
                <a href="/?page=monitoring_iuran" class="nav-item <?= in_array($currentPage, ['monitoring_iuran', 'iuran']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i><span>Monitoring Iuran</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="sidebar-bottom-group">
        <button type="button" class="btn-logout-sidebar" onclick="bukaModalLogoutSidebar()">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Keluar</span>
        </button>

        <div class="sidebar-footer">
            <p class="sidebar-footer-title">SITEMAN - KOS &copy; 2g2b - 2026</p>
            <p class="sidebar-footer-desc">Sistem Manajemen Operasion Kos</p>
        </div>
    </div>
</aside>

<div id="modalKonfirmasiLogoutSidebar" class="modal-overlay" onclick="tutupModalLogoutSidebar()">
    <div class="modal-card-konfirmasi-kamar" onclick="event.stopPropagation()">
        <div class="konfirmasi-icon-wrapper" id="logoutIconBoxSidebar">
            <i class="fa-solid fa-right-from-bracket"></i>
        </div>
        <h3 class="konfirmasi-title">Yakin ingin keluar?</h3>
        <p class="konfirmasi-desc">
            Sesi akun Anda akan diakhiri dan Anda harus masuk kembali untuk mengakses sistem.
        </p>
        <div class="konfirmasi-actions">
            <button type="button" class="btn-konfirmasi-batal" id="btnBatalLogoutSidebar" onclick="tutupModalLogoutSidebar()">Batal</button>
            <a href="/?page=logout" class="btn-konfirmasi-hapus btn-ya-keluar" onclick="prosesLogoutAnimasi(event, this, 'logoutIconBoxSidebar', 'btnBatalLogoutSidebar')">
                <span>Ya, Keluar</span>
            </a>
        </div>
    </div>
</div>

<script>
    function bukaModalLogoutSidebar() {
        const modal = document.getElementById('modalKonfirmasiLogoutSidebar');
        if (modal) modal.classList.add('active');
    }

    function tutupModalLogoutSidebar() {
        const modal = document.getElementById('modalKonfirmasiLogoutSidebar');
        if (modal) modal.classList.remove('active');
    }

    if (typeof prosesLogoutAnimasi !== 'function') {
        function prosesLogoutAnimasi(e, el, iconId, batalId) {
            e.preventDefault();
            const targetUrl = el.getAttribute('href');
            
            const iconBox = document.getElementById(iconId);
            if (iconBox) {
                iconBox.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
            }
            
            el.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Keluar...';
            el.style.pointerEvents = 'none';
            el.style.opacity = '0.85';
            
            const btnBatal = document.getElementById(batalId);
            if (btnBatal) btnBatal.style.display = 'none';

            setTimeout(function() {
                window.location.href = targetUrl;
            }, 300);
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') tutupModalLogoutSidebar();
    });
</script>