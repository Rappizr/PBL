<?php
// modules/components/sidebar.php
$role_user  = $_SESSION['user']['role'] ?? 'pemilik';
$is_pj      = $_SESSION['user']['is_pj'] ?? false;
$nama_user  = $_SESSION['user']['nama'] ?? ($role_user === 'pemilik' ? 'Bapak Kos' : 'Mas Dafa');
$currentPage = $_GET['page'] ?? 'dashboard';
?>
<aside class="desktop-sidebar">
    <div>
        <h1 class="sidebar-brand">SITEMAN - KOS</h1>
        <div class="sidebar-profile">
            <div class="sidebar-avatar"><i class="fa-solid fa-user"></i></div>
            <span class="sidebar-name"><?= htmlspecialchars($nama_user); ?></span>
        </div>

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
                <a href="/?page=verifikasi_pembayaran" class="nav-item <?= $currentPage === 'verifikasi_pembayaran' ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-check"></i><span>Verifikasi Bayar</span>
                </a>
            <?php else: ?>
                <a href="/?page=dashboard_penghuni" class="nav-item <?= $currentPage === 'dashboard_penghuni' ? 'active' : '' ?>">
                    <i class="fa-solid fa-table-cells-large"></i><span>Dashboard</span>
                </a>
                <a href="/?page=tagihan_riwayat" class="nav-item <?= $currentPage === 'tagihan_riwayat' ? 'active' : '' ?>">
                    <i class="fa-solid fa-receipt"></i><span>Tagihan & Riwayat</span>
                </a>
                <a href="/?page=peraturan_kost" class="nav-item <?= $currentPage === 'peraturan_kost' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book-open"></i><span>Peraturan Kos</span>
                </a>
            <?php endif; ?>

            <!-- Menu Bersama -->
            <a href="/?page=chat_penghuni" class="nav-item <?= $currentPage === 'chat_penghuni' ? 'active' : '' ?>">
                <i class="fa-solid fa-comments"></i><span>Chat Penghuni</span>
            </a>

            <?php if ($role_user === 'pemilik' || $is_pj): ?>
                <a href="/?page=kelola_iuran" class="nav-item <?= $currentPage === 'kelola_iuran' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i><span>Kelola Iuran</span>
                </a>
            <?php else: ?>
                <a href="/?page=monitoring_iuran" class="nav-item <?= $currentPage === 'monitoring_iuran' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i><span>Monitoring Iuran</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="sidebar-footer">
        <p class="sidebar-footer-title">SITEMAN - KOS &copy; 2g2b - 2026</p>
        <p class="sidebar-footer-desc">Sistem Manajemen Operasion Kos</p>
    </div>
</aside>