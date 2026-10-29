<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$total_pemasukan = 100000000;

$semua_transaksi = [
    [
        'judul'   => 'Pembayaran Sewa Rafi',
        'tanggal' => '2026-09-07 08:00:00',
        'nominal' => '-150.000'
    ],
    [
        'judul'   => 'Pembayaran Sewa Ridwan',
        'tanggal' => '2026-09-10 10:15:00',
        'nominal' => '-150.000'
    ],
    [
        'judul'   => 'Pembayaran Sewa Rofiq',
        'tanggal' => '2026-09-15 14:30:00',
        'nominal' => '-150.000'
    ],
    [
        'judul'   => 'Pembayaran Sewa Rafi',
        'tanggal' => '2026-10-01 09:00:00',
        'nominal' => '-150.000'
    ]
];

// Ambil input range string (format: YYYY-MM-DD to YYYY-MM-DD)
$date_range  = trim($_GET['date_range'] ?? '');
$tgl_mulai   = '';
$tgl_selesai = '';

if ($date_range !== '') {
    $parts = explode(' to ', $date_range);
    $tgl_mulai   = trim($parts[0] ?? '');
    $tgl_selesai = trim($parts[1] ?? $tgl_mulai);
}

// Logika penyaringan tanggal
$daftar_transaksi = array_filter($semua_transaksi, function ($tx) use ($tgl_mulai, $tgl_selesai) {
    if ($tgl_mulai === '') {
        return true;
    }
    $tgl_tx = substr($tx['tanggal'], 0, 10);
    return ($tgl_tx >= $tgl_mulai && $tgl_tx <= $tgl_selesai);
});

$is_filtered = ($date_range !== '');

$daftar_penghuni = [
    ['nama' => 'Ridwan', 'kamar' => 'Kamar 01', 'nominal' => '450.000', 'no_wa' => '082764518892'],
    ['nama' => 'Raffi',  'kamar' => 'Kamar 05', 'nominal' => '450.000', 'no_wa' => '082765678892'],
    ['nama' => 'Rofiq',  'kamar' => 'Kamar 03', 'nominal' => '450.000', 'no_wa' => '082094518892']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Analisis Kas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Flatpickr CSS untuk Kalender Range -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
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
<!-- Form Mobile -->
<form method="GET" action="" id="formFilterMobile" class="filter-range-form">
    <input type="hidden" name="page" value="arus_kas">
    <div class="flatpickr-box-wrapper">
        <i class="fa-regular fa-calendar date-icon"></i>
        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>" placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
        <?php if ($is_filtered): ?>
            <a href="/?page=arus_kas" class="btn-reset-range" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
        <?php endif; ?>
    </div>
</form>

                <div class="kas-transaksi-list">
                    <?php if (empty($daftar_transaksi)): ?>
                        <div class="empty-state-tx">
                            <i class="fa-regular fa-folder-open"></i>
                            <p>Tidak ada transaksi pada rentang tanggal ini</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($daftar_transaksi as $tx): ?>
<div class="kas-transaksi-card">
    <div class="kas-transaksi-left">
        <div class="kas-icon-box">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
        </div>
        <div class="kas-transaksi-info">
            <span class="kas-item-title"><?= htmlspecialchars($tx['judul']); ?></span>
            <span class="kas-item-date"><?= htmlspecialchars($tx['tanggal']); ?></span>
        </div>
    </div>
    <span class="desktop-mutasi-badge-green">
        Rp. <?= htmlspecialchars($tx['nominal']); ?>
    </span>
</div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                                <span class="kas-penghuni-nominal">Rp. <?= htmlspecialchars($p['nominal']); ?></span>
                            </div>
                            <a href="https://wa.me/<?= preg_replace('/^0/', '62', $p['no_wa']); ?>?text=Halo%20<?= urlencode($p['nama']); ?>%2C%20ini%20mengenai%20tagihan%20sewa%20kamar." target="_blank" class="btn-chat-wa">
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

            <!-- Header Riwayat Arus Kas & Form Range Sejajar -->
            <div class="desktop-range-filter-wrapper">
                <h3 class="desktop-section-title" style="margin: 0;">Riwayat Arus Kas</h3>
                
<!-- Form Desktop -->
<form method="GET" action="" id="formFilterDesktop" class="filter-range-form-desktop">
    <input type="hidden" name="page" value="arus_kas">
    <div class="flatpickr-box-wrapper">
        <i class="fa-regular fa-calendar date-icon"></i>
        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>" placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
        <?php if ($is_filtered): ?>
            <a href="/?page=arus_kas" class="btn-reset-range" title="Reset Filter">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        <?php endif; ?>
    </div>
</form>
            </div>

            <div class="desktop-kelola-mutasi-list">
                <?php if (empty($daftar_transaksi)): ?>
                    <div class="empty-state-tx-desktop">
                        <i class="fa-regular fa-folder-open"></i>
                        <p>Tidak ada transaksi arus kas pada rentang tanggal yang dipilih</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($daftar_transaksi as $tx): ?>
                        <div class="desktop-mutasi-card">
                            <div class="desktop-mutasi-left">
                                <div class="desktop-mutasi-icon"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
                                <div class="desktop-mutasi-info">
                                    <h4><?= htmlspecialchars($tx['judul']); ?></h4>
                                    <span><?= htmlspecialchars($tx['tanggal']); ?></span>
                                </div>
                            </div>
                            <span class="desktop-mutasi-badge-green">
                                Rp. <?= htmlspecialchars($tx['nominal']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', $p['no_wa']); ?>?text=Halo%20<?= urlencode($p['nama']); ?>%2C%20ini%20mengenai%20tagihan%20sewa%20kamar." target="_blank" class="btn-chat-wa">
                            <i class="fa-brands fa-whatsapp"></i> Chat
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Script Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.querySelectorAll('.flatpickr-range-input').forEach(function(input) {
            flatpickr(input, {
                mode: "range",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                onClose: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        instance.element.closest('form').submit();
                    }
                }
            });
        });
    </script>
</body>
</html>