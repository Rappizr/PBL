<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role_user    = $_SESSION['user']['role'] ?? 'penghuni';
$is_pj        = $_SESSION['user']['is_pj'] ?? ($role_user === 'pemilik');
$link_kembali = ($role_user === 'pemilik') ? '/?page=dashboard_pemilik' : '/?page=dashboard_penghuni';

$current_page = $_GET['page'] ?? 'kelola_iuran';
$show_modal   = isset($_GET['modal']) && $_GET['modal'] === 'tambah_pengeluaran';

$total_penghuni  = 13;
$sudah_bayar     = 9;
$saldo_kas_iuran = '1.253.500';

$daftar_transaksi = [
    [
        'tipe'    => 'keluar',
        'icon'    => 'fa-solid fa-arrow-up-right-from-square',
        'judul'   => 'Pembelian alat kebersihan',
        'tanggal' => '2026-09-07 08:00:00',
        'nominal' => 'Rp. -150.000'
    ],
    [
        'tipe'    => 'keluar',
        'icon'    => 'fa-solid fa-arrow-up-right-from-square',
        'judul'   => 'Pembelian sabun & pewangi',
        'tanggal' => '2026-09-07 08:00:00',
        'nominal' => 'Rp. -150.000'
    ]
];

$daftar_penghuni = [
    ['nama' => 'Ridwan', 'kamar' => 'Kamar 01', 'nominal' => 'Rp. 450.000', 'no_wa' => '628123456789'],
    ['nama' => 'Rafi',   'kamar' => 'Kamar 03', 'nominal' => 'Rp. 450.000', 'no_wa' => '628123456789'],
    ['nama' => 'Rofiq',  'kamar' => 'Kamar 05', 'nominal' => 'Rp. 450.000', 'no_wa' => '628123456789']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | <?= $is_pj ? 'Kelola & Monitor Iuran' : 'Monitoring Iuran' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
                    <h2 class="subpage-banner-title"><?= $is_pj ? 'Kelola Iuran' : 'Monitoring Iuran' ?></h2>
                    <a href="<?= $link_kembali; ?>" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

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
                    <?php if ($is_pj): ?>
                        <a href="/?page=<?= urlencode($current_page); ?>&modal=tambah_pengeluaran" class="btn-kelola-action">
                            <span class="icon-kelola-white"><i class="fa-solid fa-plus"></i></span>
                            Tambah Pengeluaran
                        </a>
                    <?php endif; ?>
                    <div class="box-kelola-saldo" style="<?= !$is_pj ? 'width: 100%; justify-content: center;' : '' ?>">
                        <span class="icon-kelola-white"><i class="fa-solid fa-wallet"></i></span>
                        Rp. <?= $saldo_kas_iuran; ?>
                    </div>
                </div>

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
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title"><?= $is_pj ? 'Kelola & Monitor Iuran' : 'Monitoring Iuran' ?></h2>
            <p class="kelola-desktop-subtitle">Mengelola dan monitor kas iuran bersama penghuni kos</p>

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

            <div class="desktop-kelola-actions">
                <?php if ($is_pj): ?>
                    <a href="/?page=<?= urlencode($current_page); ?>&modal=tambah_pengeluaran" class="desktop-btn-action">
                        <span class="icon-kelola-white"><i class="fa-solid fa-plus"></i></span>
                        Tambah Pengeluaran
                    </a>
                <?php endif; ?>
                <div class="desktop-box-saldo">
                    <span class="icon-kelola-white"><i class="fa-solid fa-wallet"></i></span>
                    Rp. <?= $saldo_kas_iuran; ?>
                </div>
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
                        <span class="desktop-mutasi-badge"><?= htmlspecialchars($tx['nominal']); ?></span>
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
                            <span class="desktop-penghuni-nominal"><?= htmlspecialchars($p['nominal']); ?></span>
                        </div>
                        <a href="https://wa.me/<?= $p['no_wa']; ?>?text=Halo%20<?= urlencode($p['nama']); ?>" target="_blank" class="btn-chat-wa">
                            <i class="fa-brands fa-whatsapp"></i> Chat
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH PENGELUARAN -->
    <?php if ($show_modal): ?>
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