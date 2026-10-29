<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Validasi Role: Default diarahkan ke 'penghuni' biasa untuk keamanan
$role_user    = $_SESSION['user']['role'] ?? 'penghuni';
$is_pemilik   = ($role_user === 'pemilik');

// 2. Hak khusus PJ/Ketua Kos hanya aktif jika role adalah penghuni DAN flag is_pj bernilai true
$is_pj        = !$is_pemilik && (!empty($_SESSION['user']['is_pj']) || !empty($_SESSION['user']['is_ketua_kos']));

// 3. Hak akses kelola: Hanya Pemilik atau PJ Kos
$can_manage   = $is_pemilik || $is_pj;

// 4. Pengalihan tombol kembali sesuai hak akses
$link_kembali = $is_pemilik ? '/?page=dashboard_pemilik' : '/?page=dashboard_penghuni';
$current_page = $_GET['page'] ?? ($can_manage ? 'kelola_iuran' : 'monitoring_iuran');
$show_modal   = isset($_GET['modal']) && $_GET['modal'] === 'tambah_pengeluaran';

// 5. Penyesuaian judul dan deskripsi halaman
if ($is_pemilik) {
    $judul_halaman    = 'Monitoring & Tagihan Iuran';
    $subjudul_halaman = 'Monitoring pengeluaran & pemasukan uang iuran';
} elseif ($is_pj) {
    $judul_halaman    = 'Kelola Iuran';
    $subjudul_halaman = 'Kelola dan monitor kas iuran bersama penghuni kos';
} else {
    $judul_halaman    = 'Monitoring Iuran';
    $subjudul_halaman = 'Pantau saldo dan riwayat pengeluaran uang iuran bersama';
}

$total_penghuni  = 13;
$sudah_bayar     = 9;
$saldo_kas_iuran = '1,253,500';

$daftar_transaksi = [
    [
        'tipe'    => 'keluar',
        'icon'    => 'fa-solid fa-arrow-up-right-from-square',
        'judul'   => 'Pembelian alat kebersihan',
        'tanggal' => '2026-09-07 08:00:00',
        'nominal' => 'Rp. -150,000'
    ],
    [
        'tipe'    => 'masuk',
        'icon'    => 'fa-solid fa-check',
        'judul'   => 'Iuran Kas Masuk',
        'tanggal' => '2026-09-07 08:00:00',
        'nominal' => 'Rp. +150,000'
    ],
    [
        'tipe'    => 'keluar',
        'icon'    => 'fa-solid fa-arrow-up-right-from-square',
        'judul'   => 'Pembelian lampu lorong',
        'tanggal' => '2026-09-07 08:00:00',
        'nominal' => 'Rp. -150,000'
    ]
];

// FILTER RENTANG TANGGAL (format: YYYY-MM-DD to YYYY-MM-DD)
$date_range = trim($_GET['date_range'] ?? '');

// Abaikan kalau formatnya aneh
if (!preg_match('/^\d{4}-\d{2}-\d{2}( to \d{4}-\d{2}-\d{2})?$/', $date_range)) {
    $date_range = '';
}

$tgl_mulai   = '';
$tgl_selesai = '';

if ($date_range !== '') {
    $parts       = explode(' to ', $date_range);
    $tgl_mulai   = trim($parts[0]);
    $tgl_selesai = trim($parts[1] ?? $tgl_mulai);
}

$daftar_transaksi = array_filter($daftar_transaksi, function ($tx) use ($tgl_mulai, $tgl_selesai) {
    if ($tgl_mulai === '') {
        return true;
    }
    $tgl_tx = substr($tx['tanggal'], 0, 10);
    return ($tgl_tx >= $tgl_mulai && $tgl_tx <= $tgl_selesai);
});

$is_filtered = ($date_range !== '');

$daftar_penghuni = [
    ['nama' => 'Ridwan', 'kamar' => 'Kamar 01', 'nominal' => 'Rp. 450,000', 'no_wa' => '628123456789'],
    ['nama' => 'Raffi',  'kamar' => 'Kamar 02', 'nominal' => 'Rp. 450,000', 'no_wa' => '628123456789'],
    ['nama' => 'Rofiq',  'kamar' => 'Kamar 03', 'nominal' => 'Rp. 450,000', 'no_wa' => '628123456789']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | <?= htmlspecialchars($judul_halaman); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

     <!-- Flatpickr CSS untuk kalender range -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="dashboard-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <main class="kelola-container">
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title"><?= htmlspecialchars($judul_halaman); ?></h2>
                    <a href="<?= $link_kembali; ?>" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <?php if ($can_manage): ?>
                    <!-- Fitur Khusus Pemilik & PJ Kos -->
                    <div class="kelola-stats-row">
                        <div class="kelola-stat-card">
                            <span class="kelola-stat-label">Total Penghuni</span>
                            <span class="kelola-stat-value"><?= $total_penghuni; ?></span>
                        </div>
                        <div class="kelola-stat-card">
                            <span class="kelola-stat-label">Sudah Bayar</span>
                            <span class="kelola-stat-value"><?= $sudah_bayar . '/' . $total_penghuni; ?></span>
                        </div>
                    </div>

                    <div class="kelola-action-row">
                        <a href="/?page=<?= urlencode($current_page); ?>&modal=tambah_pengeluaran" class="btn-kelola-action">
                            <span class="icon-kelola-white"><i class="fa-solid fa-plus"></i></span>
                            Tambah Pengeluaran
                        </a>
                        <div class="box-kelola-saldo">
                            <span class="icon-kelola-white"><i class="fa-solid fa-wallet"></i></span>
                            Rp. <?= $saldo_kas_iuran; ?>
                        </div>
                    </div>

                     <!-- Filter tanggal mobile -->
                    <form method="GET" action="" class="filter-range-form">
                        <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                        <div class="flatpickr-box-wrapper">
                            <i class="fa-regular fa-calendar date-icon"></i>
                            <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                                   placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                            <?php if ($is_filtered): ?>
                                <a href="/?page=<?= urlencode($current_page); ?>" class="btn-reset-range" title="Reset">
                                    <i class="fa-solid fa-rotate-left"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- Fitur Khusus Penghuni Biasa -->
                    <div class="kas-total-card" style="margin-bottom: 4px;">
                        <span class="kas-total-label">Saldo Bersih</span>
                        <span class="kas-total-value">Rp. <?= $saldo_kas_iuran; ?></span>
                    </div>
                    <h3 class="section-title-white" style="font-size: 15px; margin: 10px 0 4px 2px;">Riwayat Transaksi Iuran</h3>
                <?php endif; ?>

                <!-- Riwayat Transaksi (Dilihat oleh Semua Role) -->
                <div class="kelola-transaksi-list">
                    <?php foreach ($daftar_transaksi as $tx): ?>
                        <div class="kelola-transaksi-card">
                            <div class="kelola-tx-left">
                                <div class="kelola-tx-icon"><i class="<?= $tx['icon']; ?>"></i></div>
                                <div class="kelola-tx-info">
                                    <h4 class="kelola-tx-title"><?= htmlspecialchars($tx['judul']); ?></h4>
                                    <span class="kelola-tx-date"><?= htmlspecialchars($tx['tanggal']); ?></span>
                                </div>
                            </div>
                            <span class="<?= $tx['tipe'] === 'keluar' ? 'kelola-nominal-red' : 'kelola-nominal-green'; ?>">
                                <?= htmlspecialchars($tx['nominal']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($can_manage): ?>
                    <!-- Filter & Daftar Penagihan Penghuni (Hanya Pemilik & PJ Kos) -->
                    <div class="kelola-filter-row">
                        <a href="#" class="filter-pill active">Semua (13)</a>
                        <a href="#" class="filter-pill inactive">Belum Bayar (4)</a>
                        <a href="#" class="filter-pill inactive">Verifikasi (4)</a>
                    </div>

                    <div class="kelola-penghuni-list">
                        <?php foreach ($daftar_penghuni as $p): ?>
                            <div class="kelola-penghuni-card">
                                <div class="kelola-penghuni-info">
                                    <div class="kelola-penghuni-top">
                                        <h4 class="kelola-penghuni-name"><?= htmlspecialchars($p['nama']); ?></h4>
                                        <span class="badge-kamar-putih"><?= htmlspecialchars($p['kamar']); ?></span>
                                    </div>
                                    <span class="kelola-penghuni-nominal"><?= htmlspecialchars($p['nominal']); ?></span>
                                </div>
                                <a href="https://wa.me/<?= $p['no_wa']; ?>?text=Halo%20<?= urlencode($p['nama']); ?>" target="_blank" class="btn-chat-wa">
                                    <i class="fa-brands fa-whatsapp"></i> Chat
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title"><?= htmlspecialchars($judul_halaman); ?></h2>
            <p class="kelola-desktop-subtitle"><?= htmlspecialchars($subjudul_halaman); ?></p>

            <?php if ($can_manage): ?>
                <div class="desktop-kelola-stats">
                    <div class="desktop-kelola-stat-card">
                        <span class="desktop-kelola-stat-label">Total Penghuni</span>
                        <span class="desktop-kelola-stat-val"><?= $total_penghuni; ?></span>
                    </div>
                    <div class="desktop-kelola-stat-card">
                        <span class="desktop-kelola-stat-label">Sudah Bayar</span>
                        <span class="desktop-kelola-stat-val"><?= $sudah_bayar . '/' . $total_penghuni; ?></span>
                    </div>
                </div>

                <div class="iuran-desktop-actions-row">
                    <a href="/?page=<?= urlencode($current_page); ?>&modal=tambah_pengeluaran" class="iuran-btn-pill-action">
                        <i class="fa-solid fa-plus"></i> Tambah Pengeluaran
                    </a>
                    <div class="iuran-box-pill-saldo">
                        <i class="fa-solid fa-wallet"></i> Rp. <?= $saldo_kas_iuran; ?>
                    </div>
                </div>

                <!-- Filter tanggal desktop, di samping bubble saldo -->
                    <form method="GET" action="" class="filter-range-form-desktop">
                        <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                        <div class="flatpickr-box-wrapper">
                            <i class="fa-regular fa-calendar date-icon"></i>
                            <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                                   placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                            <?php if ($is_filtered): ?>
                                <a href="/?page=<?= urlencode($current_page); ?>" class="btn-reset-range" title="Reset Filter">
                                    <i class="fa-solid fa-rotate-left"></i> Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="desktop-kelola-mutasi-list">
                    <?php foreach ($daftar_transaksi as $tx): ?>
                        <div class="desktop-mutasi-card">
                            <div class="desktop-mutasi-left">
                                <div class="desktop-mutasi-icon"><i class="<?= $tx['icon']; ?>"></i></div>
                                <div class="desktop-mutasi-info">
                                    <h4><?= htmlspecialchars($tx['judul']); ?></h4>
                                    <span><?= htmlspecialchars($tx['tanggal']); ?></span>
                                </div>
                            </div>
                            <span class="<?= $tx['tipe'] === 'keluar' ? 'desktop-mutasi-badge-red' : 'desktop-mutasi-badge-green'; ?>">
                                <?= htmlspecialchars($tx['nominal']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="iuran-desktop-filters">
                    <a href="#" class="iuran-filter-pill active">Semua (13)</a>
                    <a href="#" class="iuran-filter-pill inactive">Belum Bayar (4)</a>
                    <a href="#" class="iuran-filter-pill inactive">Verifikasi (4)</a>
                </div>

                <div class="desktop-penghuni-list">
                    <?php foreach ($daftar_penghuni as $p): ?>
                        <div class="desktop-penghuni-card">
                            <div class="desktop-penghuni-info">
                                <div class="desktop-penghuni-top">
                                    <h4><?= htmlspecialchars($p['nama']); ?></h4>
                                    <span class="iuran-kamar-pill"><?= htmlspecialchars($p['kamar']); ?></span>
                                </div>
                                <span class="desktop-penghuni-nominal"><?= htmlspecialchars($p['nominal']); ?></span>
                            </div>
                            <a href="https://wa.me/<?= $p['no_wa']; ?>?text=Halo%20<?= urlencode($p['nama']); ?>" target="_blank" class="iuran-btn-chat-pill">
                                <i class="fa-brands fa-whatsapp"></i> Chat
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <!-- Desktop View Penghuni Biasa -->
                <div class="desktop-kelola-stat-card" style="max-width: 250px; margin-bottom: 22px;">
                    <span class="desktop-kelola-stat-label">Saldo Bersih</span>
                    <span class="desktop-kelola-stat-val">Rp. <?= $saldo_kas_iuran; ?></span>
                </div>

                <h3 class="desktop-section-title" style="margin-bottom: 14px;">Riwayat Transaksi Iuran</h3>

                <div class="desktop-kelola-mutasi-list">
                    <?php foreach ($daftar_transaksi as $tx): ?>
                        <div class="desktop-mutasi-card">
                            <div class="desktop-mutasi-left">
                                <div class="desktop-mutasi-icon"><i class="<?= $tx['icon']; ?>"></i></div>
                                <div class="desktop-mutasi-info">
                                    <h4><?= htmlspecialchars($tx['judul']); ?></h4>
                                    <span><?= htmlspecialchars($tx['tanggal']); ?></span>
                                </div>
                            </div>
                            <span class="<?= $tx['tipe'] === 'keluar' ? 'desktop-mutasi-badge-red' : 'desktop-mutasi-badge-green'; ?>">
                                <?= htmlspecialchars($tx['nominal']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal Tambah Pengeluaran (Hanya untuk yang berhak) -->
    <?php if ($can_manage && $show_modal): ?>
        <div class="modal-overlay active">
            <div class="modal-card">
                <h3 class="modal-title">Tambah Pengeluaran</h3>
                <form action="/?page=<?= urlencode($current_page); ?>" method="POST" class="modal-form">
                    <div class="modal-field">
                        <label class="modal-label" for="keterangan">Keterangan</label>
                        <input type="text" id="keterangan" name="keterangan" class="modal-input" required>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label" for="kategori">Kategori</label>
                        <input type="text" id="kategori" name="kategori" class="modal-input" required>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label" for="nominal">Nominal</label>
                        <input type="number" id="nominal" name="nominal" class="modal-input" required>
                    </div>
                    <div class="modal-actions">
                        <a href="/?page=<?= urlencode($current_page); ?>" class="btn-modal-cancel">Batal</a>
                        <button type="submit" class="btn-modal-submit">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>