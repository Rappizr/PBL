<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$total_pemasukan = 100000000;

$daftar_transaksi = [
    [
        'judul'   => 'Pembayaran Sewa Rafi',
        'tanggal' => '2026-09-07 08:00:00',
        'nominal' => '-150.000'
    ],
    [
        'judul'   => 'Pembayaran Sewa Rafi',
        'tanggal' => '2026-09-07 08:00:00',
        'nominal' => '-150.000'
    ],
    [
        'judul'   => 'Pembayaran Sewa Rafi',
        'tanggal' => '2026-09-07 08:00:00',
        'nominal' => '-150.000'
    ]
];

$daftar_penghuni = [
    [
        'nama'    => 'Ridwan',
        'kamar'   => 'Kamar 01',
        'nominal' => '450.000',
        'no_wa'   => '082764518892'
    ],
    [
        'nama'    => 'Raffi',
        'kamar'   => 'Kamar 05',
        'nominal' => '450.000',
        'no_wa'   => '082765678892'
    ],
    [
        'nama'    => 'Rofiq',
        'kamar'   => 'Kamar 03',
        'nominal' => '450.000',
        'no_wa'   => '082094518892'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Analisis Kas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Link CSS Modular Terpisah -->
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="dashboard-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <main class="kas-container">
                <div class="kas-banner-card">
                    <h2 class="kas-banner-title">Analisis Kas</h2>
                    <a href="/?page=dashboard_pemilik" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="kas-total-card">
                    <span class="kas-total-label">Total Pemasukan</span>
                    <span class="kas-total-value">Rp. <?= number_format($total_pemasukan, 0, ',', '.'); ?></span>
                </div>

                <div class="kas-transaksi-list">
                    <?php foreach ($daftar_transaksi as $tx): ?>
                        <div class="kas-transaksi-card">
                            <div class="kas-transaksi-left">
                                <div class="kas-icon-box">
                                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                                        <path d="M16.19 2H7.81C4.17 2 2 4.17 2 7.81V16.18C2 19.83 4.17 22 7.81 22H16.18C19.82 22 21.99 19.83 21.99 16.19V7.81C22 4.17 19.83 2 16.19 2ZM17.53 7.53L9.81 15.25H12.83C13.24 15.25 13.58 15.59 13.58 16C13.58 16.41 13.24 16.75 12.83 16.75H8C7.59 16.75 7.25 16.41 7.25 16V11.17C7.25 10.76 7.59 10.42 8 10.42C8.41 10.42 8.75 10.76 8.75 11.17V14.19L16.47 6.47C16.62 6.32 16.81 6.25 17 6.25C17.19 6.25 17.38 6.32 17.53 6.47C17.82 6.76 17.82 7.24 17.53 7.53Z" fill="#ffffff"/>
                                    </svg>
                                </div>
                                <div class="kas-transaksi-info">
                                    <span class="kas-item-title"><?= htmlspecialchars($tx['judul']); ?></span>
                                    <span class="kas-item-date"><?= htmlspecialchars($tx['tanggal']); ?></span>
                                </div>
                            </div>
                            <span class="kas-nominal-pill">
                                Rp. <?= htmlspecialchars($tx['nominal']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="kas-filter-row">
                    <a href="#" class="filter-pill active">Semua (13)</a>
                    <a href="#" class="filter-pill inactive">Belum Bayar (4)</a>
                    <a href="#" class="filter-pill inactive">Verifikasi (4)</a>
                </div>

                <div class="kas-penghuni-list">
                    <?php foreach ($daftar_penghuni as $p): ?>
                        <div class="kas-penghuni-card">
                            <div class="kas-penghuni-info">
                                <div class="kas-penghuni-top">
                                    <h4 class="kas-penghuni-name"><?= htmlspecialchars($p['nama']); ?></h4>
                                    <span class="badge-kamar-putih"><?= htmlspecialchars($p['kamar']); ?></span>
                                </div>
                                <span class="kas-penghuni-nominal">
                                    Rp. <?= htmlspecialchars($p['nominal']); ?>
                                </span>
                            </div>
                            <a href="https://wa.me/<?= preg_replace('/^0/', '62', $p['no_wa']); ?>?text=Halo%20<?= urlencode($p['nama']); ?>%2C%20ini%20mengenai%20tagihan%20sewa%20kamar." 
                               target="_blank" 
                               class="btn-chat-wa">
                                <i class="fa-brands fa-whatsapp"></i> Chat
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Analisis Kas</h2>
            <p class="desktop-header-subtitle">Laporan arus kas dan status pembayaran penghuni</p>

            <div class="desktop-kelola-stats" style="grid-template-columns: minmax(280px, 340px);">
                <div class="desktop-kelola-stat-card">
                    <span class="desktop-kelola-stat-label">Total Pemasukan</span>
                    <span class="desktop-kelola-stat-val">Rp. <?= number_format($total_pemasukan, 0, ',', '.'); ?></span>
                </div>
            </div>

            <h3 class="desktop-section-title">Riwayat Arus Kas</h3>
            <div class="desktop-kelola-mutasi-list">
                <?php foreach ($daftar_transaksi as $tx): ?>
                    <div class="desktop-mutasi-card">
                        <div class="desktop-mutasi-left">
                            <div class="desktop-mutasi-icon"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
                            <div class="desktop-mutasi-info">
                                <h4><?= htmlspecialchars($tx['judul']); ?></h4>
                                <span><?= htmlspecialchars($tx['tanggal']); ?></span>
                            </div>
                        </div>
                        <span class="desktop-mutasi-badge" style="background-color: #8ce3b9; color: #116843;">
                            Rp. <?= htmlspecialchars($tx['nominal']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="desktop-kelola-filter-row">
                <a href="#" class="filter-pill active">Semua (13)</a>
                <a href="#" class="filter-pill inactive">Belum Bayar (4)</a>
                <a href="#" class="filter-pill inactive">Verifikasi (4)</a>
            </div>

            <div class="desktop-penghuni-list">
                <?php foreach ($daftar_penghuni as $p): ?>
                    <div class="desktop-penghuni-card">
                        <div class="desktop-penghuni-info">
                            <div class="desktop-penghuni-top">
                                <h4><?= htmlspecialchars($p['nama']); ?></h4>
                                <span class="badge-kamar-putih"><?= htmlspecialchars($p['kamar']); ?></span>
                            </div>
                            <span class="desktop-penghuni-nominal">Rp. <?= htmlspecialchars($p['nominal']); ?></span>
                        </div>
                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', $p['no_wa']); ?>?text=Halo%20<?= urlencode($p['nama']); ?>%2C%20ini%20mengenai%20tagihan%20sewa%20kamar." 
                           target="_blank" 
                           class="btn-chat-wa">
                            <i class="fa-brands fa-whatsapp"></i> Chat
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

</body>
</html>