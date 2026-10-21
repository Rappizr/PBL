<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_pj = true;
$nama_penghuni = 'Mas Dafa';

$tagihan = [
    'sewa' => [
        'status' => 'belum_lunas',
        'tanggal_jatuh_tempo' => '2026-10-11',
    ],
    'iuran' => [
        'status' => 'belum_lunas',
        'tanggal_jatuh_tempo' => '2026-10-11',
    ],
];

function formatTanggalIndo(?string $tanggal): string {
    if (!$tanggal) return '-';
    $hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    
    $ts = strtotime($tanggal);
    $hariNama = $hari[date('l', $ts)] ?? date('D', $ts);
    $bulanNama = $bulan[(int)date('n', $ts)] ?? date('F', $ts);
    
    return $hariNama . ', ' . date('d', $ts) . ' ' . $bulanNama . ' ' . date('Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="dashboard-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <header class="dashboard-header">
                <h1 class="brand-title-dashboard">SITEMAN - KOS</h1>
                <div class="kotak-kapsul-pemilik">
                    <span class="lingkaran-user-abu">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <span class="tulisan-nama"><?= htmlspecialchars($nama_penghuni); ?></span>
                </div>
            </header>

            <main class="page-content">
                <div class="penghuni-summary-cards">
                    <div class="tagihan-dark-card">
                        <span class="tagihan-pill-label">Pembayaran Sewa</span>
                        <div class="tagihan-meta-grid">
                            <div class="meta-row">
                                <span class="meta-label">Status Pembayaran</span>
                                <span class="meta-colon">:</span>
                                <span class="meta-value">
                                    <span class="<?= $tagihan['sewa']['status'] === 'lunas' ? 'status-badge-green' : 'status-badge-white' ?>">
                                        <?= $tagihan['sewa']['status'] === 'lunas' ? 'Lunas' : 'Belum Lunas' ?>
                                    </span>
                                </span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-label">Tenggat Pembayaran</span>
                                <span class="meta-colon">:</span>
                                <span class="meta-value date-text">
                                    <?= formatTanggalIndo($tagihan['sewa']['tanggal_jatuh_tempo']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="tagihan-dark-card">
                        <span class="tagihan-pill-label">Pembayaran Iuran</span>
                        <div class="tagihan-meta-grid">
                            <div class="meta-row">
                                <span class="meta-label">Status Pembayaran</span>
                                <span class="meta-colon">:</span>
                                <span class="meta-value">
                                    <span class="<?= $tagihan['iuran']['status'] === 'lunas' ? 'status-badge-green' : 'status-badge-white' ?>">
                                        <?= $tagihan['iuran']['status'] === 'lunas' ? 'Lunas' : 'Belum Lunas' ?>
                                    </span>
                                </span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-label">Tenggat Pembayaran</span>
                                <span class="meta-colon">:</span>
                                <span class="meta-value date-text">
                                    <?= formatTanggalIndo($tagihan['iuran']['tanggal_jatuh_tempo']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="menu-card-container">
                    <h2 class="menu-heading">Silahkan pilih menu</h2>
                    
                    <div class="menu-grid">
                        <a href="/?page=tagihan_riwayat" class="menu-item-box">
                            <div class="menu-icon-wrapper"><i class="fa-solid fa-receipt"></i></div>
                            <span class="menu-label">Tagihan &amp; Riwayat</span>
                        </a>

                        <?php if ($is_pj): ?>
                            <a href="/?page=kelola_iuran" class="menu-item-box">
                                <div class="menu-icon-wrapper"><i class="fa-solid fa-chart-pie"></i></div>
                                <span class="menu-label">Kelola Iuran</span>
                            </a>
                        <?php else: ?>
                            <a href="/?page=monitoring_iuran" class="menu-item-box">
                                <div class="menu-icon-wrapper"><i class="fa-solid fa-chart-pie"></i></div>
                                <span class="menu-label">Monitoring Iuran</span>
                            </a>
                        <?php endif; ?>

                        <a href="/?page=peraturan_kost" class="menu-item-box">
                            <div class="menu-icon-wrapper"><i class="fa-solid fa-book-open"></i></div>
                            <span class="menu-label">Peraturan Kos</span>
                        </a>

                        <a href="/?page=chat_penghuni" class="menu-item-box">
                            <div class="menu-icon-wrapper"><i class="fa-solid fa-comments"></i></div>
                            <span class="menu-label">Chat Penghuni</span>
                        </a>

                        <?php if ($is_pj): ?>
                            <a href="/?page=verifikasi_pembayaran" class="menu-item-box">
                                <div class="menu-icon-wrapper"><i class="fa-solid fa-list-check"></i></div>
                                <span class="menu-label">Verifikasi<br>Pembayaran</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <aside class="desktop-sidebar">
            <div>
                <h1 class="sidebar-brand">SITEMAN - KOS</h1>
                
                <div class="sidebar-profile">
                    <div class="sidebar-avatar"><i class="fa-solid fa-user"></i></div>
                    <span class="sidebar-name"><?= htmlspecialchars($nama_penghuni); ?></span>
                </div>

                <nav class="sidebar-nav">
                    <a href="/?page=dashboard_penghuni" class="nav-item active">
                        <i class="fa-solid fa-table-cells-large"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="/?page=tagihan_riwayat" class="nav-item">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Tagihan &amp; Riwayat</span>
                    </a>
                    <a href="/?page=monitoring_iuran" class="nav-item">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>Monitoring &amp; Tagihan Iuran</span>
                    </a>
                    <a href="/?page=peraturan_kost" class="nav-item">
                        <i class="fa-solid fa-book-open"></i>
                        <span>Peraturan Kos</span>
                    </a>
                    <a href="/?page=chat_penghuni" class="nav-item">
                        <i class="fa-solid fa-comments"></i>
                        <span>Chat Penghuni</span>
                    </a>
                    <?php if ($is_pj): ?>
                        <a href="/?page=kelola_iuran" class="nav-item">
                            <i class="fa-solid fa-wallet"></i>
                            <span>Kelola &amp; Monitor Iuran</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>

            <div class="sidebar-footer">
                <p class="sidebar-footer-title">SITEMAN - KOS &copy; 2g2b - 2026</p>
                <p class="sidebar-footer-desc">Sistem Manajemen Operasion Kos</p>
            </div>
        </aside>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Dashboard</h2>
            <p class="desktop-header-subtitle">Ringkasan tagihan dan akses cepat untukmu</p>

            <div class="desktop-tagihan-row">
                <div class="desktop-tagihan-card">
                    <span class="tagihan-badge-white">Pembayaran Sewa</span>
                    <div class="tagihan-details">
                        <div class="tagihan-detail-row">
                            <span class="lbl">Status Pembayaran</span>
                            <span class="colon">:</span>
                            <span class="tagihan-pill-warning">Belum Lunas</span>
                        </div>
                        <div class="tagihan-detail-row">
                            <span class="lbl">Tenggat</span>
                            <span class="colon">:</span>
                            <span><?= formatTanggalIndo($tagihan['sewa']['tanggal_jatuh_tempo']); ?></span>
                        </div>
                    </div>
                    <a href="/?page=tagihan_riwayat&modal=bayar_sewa" class="btn-upload-bukti">Upload Bukti Pembayaran</a>
                </div>

                <div class="desktop-tagihan-card">
                    <span class="tagihan-badge-white">Pembayaran Iuran</span>
                    <div class="tagihan-details">
                        <div class="tagihan-detail-row">
                            <span class="lbl">Status Pembayaran</span>
                            <span class="colon">:</span>
                            <span class="tagihan-pill-warning">Belum Lunas</span>
                        </div>
                        <div class="tagihan-detail-row">
                            <span class="lbl">Tenggat</span>
                            <span class="colon">:</span>
                            <span><?= formatTanggalIndo($tagihan['iuran']['tanggal_jatuh_tempo']); ?></span>
                        </div>
                    </div>
                    <a href="/?page=tagihan_riwayat&modal=bayar_iuran" class="btn-upload-bukti">Upload Bukti Pembayaran</a>
                </div>
            </div>

            <h3 class="menu-utama-title">Menu utama</h3>
            <div class="desktop-menu-grid">
                <a href="/?page=tagihan_riwayat" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-receipt"></i></div>
                    <span class="desktop-menu-label">Tagihan &amp;<br>Riwayat</span>
                </a>

                <a href="/?page=peraturan_kost" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-book-open"></i></div>
                    <span class="desktop-menu-label">Peraturan<br>Kos</span>
                </a>

                <a href="/?page=chat_penghuni" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-comments"></i></div>
                    <span class="desktop-menu-label">Chat<br>Penghuni</span>
                </a>

                <a href="/?page=monitoring_iuran" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-chart-pie"></i></div>
                    <span class="desktop-menu-label">Monitoring &amp;<br>Tagihan Iuran</span>
                </a>

                <?php if ($is_pj): ?>
                    <a href="/?page=kelola_iuran" class="desktop-menu-card">
                        <div class="desktop-menu-icon"><i class="fa-solid fa-wallet"></i></div>
                        <span class="desktop-menu-label">Kelola &amp; Monitor<br>Iuran</span>
                    </a>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>