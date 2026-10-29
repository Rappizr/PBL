<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pendingPembayaranCount = 3; // Contoh data dummy (atau query DB)
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Dashboard Pemilik</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="dashboard-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <main class="menu-card-container">
                <h2 class="menu-heading">Silahkan pilih menu</h2>
                <div class="menu-grid">
                    <a href="/?page=manajemen_kamar" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-warehouse"></i></div>
                        <span class="menu-label">Manajemen<br>Kamar & Penghuni</span>
                    </a>
                    <a href="/?page=arus_kas" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-wallet"></i></div>
                        <span class="menu-label">Analisis Kas</span>
                    </a>
                    <a href="/?page=verifikasi_pembayaran" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-list-check"></i></div>
                        <span class="menu-label">Verifikasi<br>Pembayaran</span>
                        <?php if ($pendingPembayaranCount > 0): ?>
                            <span class="badge-notif"><i class="fa-solid fa-bell"></i> <?= $pendingPembayaranCount ?> Notifikasi</span>
                        <?php endif; ?>
                    </a>
                    <a href="/?page=kelola_iuran" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-chart-pie"></i></div>
                        <span class="menu-label">Kelola Iuran</span>
                    </a>
                    <a href="/?page=chat_penghuni" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-comments"></i></div>
                        <span class="menu-label">Chat Penghuni</span>
                    </a>
                </div>
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Dashboard Pemilik</h2>
            <p class="desktop-header-subtitle">Ringkasan operasional dan keuangan kos</p>

            <h3 class="menu-utama-title">Menu utama</h3>
            <div class="desktop-menu-grid">
                <a href="/?page=manajemen_kamar" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-warehouse"></i></div>
                    <span class="desktop-menu-label">Manajemen<br>Kamar</span>
                </a>
                <a href="/?page=arus_kas" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-wallet"></i></div>
                    <span class="desktop-menu-label">Analisis<br>Kas</span>
                </a>
                <a href="/?page=verifikasi_pembayaran" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-list-check"></i></div>
                    <span class="desktop-menu-label">Verifikasi<br>Bayar</span>
                </a>
                <a href="/?page=kelola_iuran" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-chart-pie"></i></div>
                    <span class="desktop-menu-label">Kelola<br>Iuran</span>
                </a>
                <a href="/?page=chat_penghuni" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-comments"></i></div>
                    <span class="desktop-menu-label">Chat<br>Penghuni</span>
                </a>
            </div>
        </main>
    </div>

</body>
</html>