<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah akun adalah PJ / Ketua Kos
$is_pj = !empty($_SESSION['user']['is_pj']) || !empty($_SESSION['user']['is_ketua_kos']);

$tagihan = [
    'sewa' => [
        'status' => 'belum_lunas',
        'tenggat' => 'Rabu, 11 October 2026',
    ],
    'iuran' => [
        'status' => 'belum_lunas',
        'tenggat' => 'Rabu, 11 October 2026',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="dashboard-body">

    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <!-- Ringkasan Tagihan Penghuni / PJ -->
            <div class="penghuni-mobile-summary">
                <div class="penghuni-summary-card">
                    <span class="badge-tagihan-title">Pembayaran Sewa</span>
                    <div class="tagihan-info-grid">
                        <div class="tagihan-info-row">
                            <span class="info-label">Status Pembayaran</span>
                            <span class="info-colon">:</span>
                            <span class="badge-status-pill"><?= $tagihan['sewa']['status'] === 'lunas' ? 'Lunas' : 'Belum Lunas'; ?></span>
                        </div>
                        <div class="tagihan-info-row">
                            <span class="info-label">Tenggat Pembayaran</span>
                            <span class="info-colon">:</span>
                            <span class="info-date"><?= $tagihan['sewa']['tenggat']; ?></span>
                        </div>
                    </div>
                </div>

                <div class="penghuni-summary-card">
                    <span class="badge-tagihan-title">Pembayaran Iuran</span>
                    <div class="tagihan-info-grid">
                        <div class="tagihan-info-row">
                            <span class="info-label">Status Pembayaran</span>
                            <span class="info-colon">:</span>
                            <span class="badge-status-pill"><?= $tagihan['iuran']['status'] === 'lunas' ? 'Lunas' : 'Belum Lunas'; ?></span>
                        </div>
                        <div class="tagihan-info-row">
                            <span class="info-label">Tenggat Pembayaran</span>
                            <span class="info-colon">:</span>
                            <span class="info-date"><?= $tagihan['iuran']['tenggat']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid Menu Utama (Urutan sesuai desain mockup PJ) -->
            <div class="menu-card-container">
                <h2 class="menu-heading">Silahkan pilih menu</h2>
                <div class="menu-grid">
                    
                    <!-- 1. Tagihan & Riwayat -->
                    <a href="/?page=tagihan_riwayat" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-receipt"></i></div>
                        <span class="menu-label">Tagihan &amp; Riwayat</span>
                    </a>

                    <!-- 2. Kelola Iuran (Khusus PJ) / Monitoring Iuran (Penghuni biasa) -->
                    <?php if ($is_pj): ?>
                        <a href="/?page=iuran" class="menu-item-box">
                            <div class="menu-icon-wrapper"><i class="fa-solid fa-wallet"></i></div>
                            <span class="menu-label">Kelola Iuran</span>
                        </a>
                    <?php else: ?>
                        <a href="/?page=iuran" class="menu-item-box">
                            <div class="menu-icon-wrapper"><i class="fa-solid fa-chart-pie"></i></div>
                            <span class="menu-label">Monitoring<br>Iuran</span>
                        </a>
                    <?php endif; ?>

                    <!-- 3. Peraturan Kos -->
                    <a href="/?page=peraturan_kost" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-book-open"></i></div>
                        <span class="menu-label">Peraturan Kos</span>
                    </a>

                    <!-- 4. Chat Penghuni -->
                    <a href="/?page=chat_penghuni" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-comment-dots"></i></div>
                        <span class="menu-label">Chat Penghuni</span>
                    </a>

                    <!-- 5. Verifikasi Pembayaran (Hanya tampil jika akun PJ) -->
                    <?php if ($is_pj): ?>
                        <a href="/?page=verifikasi_bayar" class="menu-item-box">
                            <div class="menu-icon-wrapper"><i class="fa-solid fa-list-check"></i></div>
                            <span class="menu-label">Verifikasi<br>Pembayaran</span>
                        </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- Tampilan Desktop View -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Dashboard</h2>
            <p class="desktop-header-subtitle">Ringkasan tagihan dan akses cepat untukmu</p>

            <div class="desktop-tagihan-row">
                <div class="desktop-tagihan-card">
                    <span class="tagihan-badge-white">Pembayaran Sewa</span>
                    <div class="tagihan-details">
                        <div class="tagihan-detail-row">
                            <span class="lbl">Status Pembayaran</span><span class="colon">:</span>
                            <span class="tagihan-pill-warning">Belum Lunas</span>
                        </div>
                        <div class="tagihan-detail-row">
                            <span class="lbl">Tenggat</span><span class="colon">:</span>
                            <span><?= $tagihan['sewa']['tenggat']; ?></span>
                        </div>
                    </div>
                    <a href="/?page=tagihan_riwayat" class="btn-upload-bukti">Upload Bukti Pembayaran</a>
                </div>

                <div class="desktop-tagihan-card">
                    <span class="tagihan-badge-white">Pembayaran Iuran</span>
                    <div class="tagihan-details">
                        <div class="tagihan-detail-row">
                            <span class="lbl">Status Pembayaran</span><span class="colon">:</span>
                            <span class="tagihan-pill-warning">Belum Lunas</span>
                        </div>
                        <div class="tagihan-detail-row">
                            <span class="lbl">Tenggat</span><span class="colon">:</span>
                            <span><?= $tagihan['iuran']['tenggat']; ?></span>
                        </div>
                    </div>
                    <a href="/?page=tagihan_riwayat" class="btn-upload-bukti">Upload Bukti Pembayaran</a>
                </div>
            </div>

            <h3 class="menu-utama-title">Menu utama</h3>
            <div class="desktop-menu-grid">
                <a href="/?page=tagihan_riwayat" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-receipt"></i></div>
                    <span class="desktop-menu-label">Tagihan &amp;<br>Riwayat</span>
                </a>

                <?php if ($is_pj): ?>
                    <a href="/?page=iuran" class="desktop-menu-card">
                        <div class="desktop-menu-icon"><i class="fa-solid fa-wallet"></i></div>
                        <span class="desktop-menu-label">Kelola &amp; Monitor<br>Iuran</span>
                    </a>
                <?php else: ?>
                    <a href="/?page=iuran" class="desktop-menu-card">
                        <div class="desktop-menu-icon"><i class="fa-solid fa-chart-pie"></i></div>
                        <span class="desktop-menu-label">Monitoring &amp;<br>Tagihan Iuran</span>
                    </a>
                <?php endif; ?>

                <a href="/?page=peraturan_kost" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-book-open"></i></div>
                    <span class="desktop-menu-label">Peraturan<br>Kos</span>
                </a>

                <a href="/?page=chat_penghuni" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-comment-dots"></i></div>
                    <span class="desktop-menu-label">Chat<br>Penghuni</span>
                </a>

                <?php if ($is_pj): ?>
                    <a href="/?page=verifikasi_bayar" class="desktop-menu-card">
                        <div class="desktop-menu-icon"><i class="fa-solid fa-list-check"></i></div>
                        <span class="desktop-menu-label">Verifikasi<br>Pembayaran</span>
                    </a>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>