<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// Simulasi user / ambil dari session
$is_pj = true; // Jika bernilai true, menampilkan menu PJ (Kelola Iuran & Verifikasi)
$nama_penghuni = 'Mas Dafa';

// Tagihan Dummy
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
    $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'Desember'];
    
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
    <title>SITEMAN - KOS | Dashboard Penghuni</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>

<body class="dashboard-body">
    <div class="mobile-container">
        <div>
            <!-- Header Modular -->
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
                <!-- Cards Tagihan Atas -->
                <div class="penghuni-summary-cards">
                    <!-- Pembayaran Sewa -->
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

                    <!-- Pembayaran Iuran -->
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

                <!-- Kontainer Menu Teal -->
                <div class="menu-card-container">
                    <h2 class="menu-heading">Silahkan pilih menu</h2>
                    
                    <div class="menu-grid">
                        <!-- Menu 1: Tagihan & Riwayat -->
                        <a href="/?page=tagihan_riwayat" class="menu-item-box">
                            <div class="menu-icon-wrapper">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                            <span class="menu-label">Tagihan &amp; Riwayat</span>
                        </a>

                        <!-- Menu 2: Monitoring & Tagihan Iuran -->
                         <?php if ($is_pj): ?>
                            <a href="/?page=kelola_iuran" class="menu-item-box">
                                <div class="menu-icon-wrapper">
                                    <i class="fa-solid fa-chart-pie"></i>
                                </div>
                                <span class="menu-label">Kelola Iuran</span>
                            </a>
                        <?php else: ?>
                            <a href="/?page=monitoring_iuran" class="menu-item-box">
                            <div class="menu-icon-wrapper">
                                <i class="fa-solid fa-chart-pie"></i>
                            </div>
                            <span class="menu-label">Monitoring Iuran</span>
                        </a>
                        <?php endif; ?>

                        <!-- Menu 3: Peraturan Kos -->
                        <a href="/?page=peraturan_kost" class="menu-item-box">
                            <div class="menu-icon-wrapper">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                            <span class="menu-label">Peraturan Kos</span>
                        </a>
                        <!-- Menu 4: Chat Penghuni -->
                        <a href="/?page=chat_penghuni" class="menu-item-box">
                            <div class="menu-icon-wrapper">
                                <i class="fa-solid fa-comments"></i>
                            </div>
                            <span class="menu-label">Chat Penghuni</span>
                        </a>

                        <!-- Menu 5 & 6 (Khusus PJ Kos) -->
                        <?php if ($is_pj): ?>
                            <!-- Menu 6: Verifikasi Pembayaran -->
                            <a href="/?page=verifikasi_bayar" class="menu-item-box">
                                <div class="menu-icon-wrapper">
                                    <i class="fa-solid fa-list-check"></i>
                                </div>
                                <span class="menu-label">Verifikasi<br>Pembayaran</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>

        <!-- Footer Modular -->
        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>
</body>
</html>