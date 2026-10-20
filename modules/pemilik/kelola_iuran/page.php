<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Deteksi Role Pengguna (Dinamis: Pemilik atau PJ Kos)
// Default jika belum login: fallback ke PJ
$role_user = $_SESSION['user']['role'] ?? 'pj';

if ($role_user === 'pemilik') {
    $nama_user    = 'Bapak Kos';
    $link_kembali = '/?page=dashboard_pemilik';
} else {
    $nama_user    = 'Mas Daffa';
    $link_kembali = '/?page=penghuni-dashboard';
}

$current_page = $_GET['page'] ?? 'pj-kelola-iuran';
$show_modal = isset($_GET['modal']) && $_GET['modal'] === 'tambah_pengeluaran';

// 2. Data Ringkasan Penghuni
$total_penghuni = 13;
$sudah_bayar    = 9;

// 3. Saldo Kas Iuran
$saldo_kas_iuran = '1,253,500';

// 4. Data Dummy Riwayat Transaksi Mutasi Iuran
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
        'icon'    => 'fa-regular fa-square-check',
        'judul'   => 'Pembelian alat kebersihan',
        'tanggal' => '2026-09-07 08:00:00',
        'nominal' => 'Rp. -150,000'
    ],
    [
        'tipe'    => 'keluar',
        'icon'    => 'fa-solid fa-arrow-up-right-from-square',
        'judul'   => 'Pembelian alat kebersihan',
        'tanggal' => '2026-09-07 08:00:00',
        'nominal' => 'Rp. -150,000'
    ]
];

// 5. Data Dummy Daftar Iuran Penghuni + Kontak WhatsApp
$daftar_penghuni = [
    [
        'nama'    => 'Ridwan',
        'kamar'   => 'Kamar 01',
        'nominal' => 'Rp. 450,000',
        'no_wa'   => '628123456789'
    ],
    [
        'nama'    => 'Ridwan',
        'kamar'   => 'Kamar 01',
        'nominal' => 'Rp. 450,000',
        'no_wa'   => '628123456789'
    ],
    [
        'nama'    => 'Ridwan',
        'kamar'   => 'Kamar 01',
        'nominal' => 'Rp. 450,000',
        'no_wa'   => '628123456789'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Kelola Iuran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>

<body class="dashboard-body">
    <div class="mobile-container">
        <div>
            <!-- Header Top Bar -->
            <header class="dashboard-header">
                <h1 class="brand-title-dashboard">SITEMAN - KOS</h1>
                <div class="kotak-kapsul-pemilik">
                    <span class="lingkaran-user-abu">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <span class="tulisan-nama"><?= htmlspecialchars($nama_user); ?></span>
                </div>
            </header>

            <main class="kelola-container">
                <!-- 1. Banner Judul & Tombol Kembali Dinamis -->
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title">Kelola Iuran</h2>
                    <a href="<?= $link_kembali; ?>" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- 2. Ringkasan Dua Kolom: Total Penghuni & Sudah Bayar -->
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

                <!-- 3. Dua Tombol Aksi: Tambah Pengeluaran & Saldo Kas -->
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

                <!-- 4. Daftar Transaksi Mutasi Singkat -->
                <div class="kelola-transaksi-list">
                    <?php foreach ($daftar_transaksi as $tx): ?>
                        <div class="kelola-transaksi-card">
                            <div class="kelola-tx-left">
                                <div class="kelola-tx-icon">
                                    <i class="<?= $tx['icon']; ?>"></i>
                                </div>
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

                <!-- 5. Baris Filter Pill -->
                <div class="kelola-filter-row">
                    <a href="#" class="filter-pill active">Semua (13)</a>
                    <a href="#" class="filter-pill inactive">Belum Bayar (4)</a>
                    <a href="#" class="filter-pill inactive">Verifikasi (4)</a>
                </div>

                <!-- 6. Daftar Penghuni Iuran + Tombol Chat WhatsApp -->
                <div class="kelola-penghuni-list">
                    <?php foreach ($daftar_penghuni as $p): ?>
                        <div class="kelola-penghuni-card">
                            <div class="kelola-penghuni-info">
                                <div class="kelola-penghuni-top">
                                    <h4 class="kelola-penghuni-name"><?= htmlspecialchars($p['nama']); ?></h4>
                                    <span class="badge-kamar-putih"><?= htmlspecialchars($p['kamar']); ?></span>
                                </div>
                                <span class="kelola-penghuni-nominal">
                                    <?= htmlspecialchars($p['nominal']); ?>
                                </span>
                            </div>

                            <a href="https://wa.me/<?= $p['no_wa']; ?>?text=Halo%20<?= urlencode($p['nama']); ?>%2C%20ini%20mengenai%20iuran%20kos." 
                               target="_blank" 
                               class="btn-chat-wa">
                                <i class="fa-brands fa-whatsapp"></i> Chat
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>

        <?php require_once __DIR__ . '/../../components/footer.php'; ?>
    </div>

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
                    <!-- Tombol Batal kembali ke halaman asal tanpa query modal -->
                    <a href="/?page=<?= urlencode($current_page); ?>" class="btn-modal-cancel" style="display: flex; align-items: center; justify-content: center; text-decoration: none;">Batal</a>
                    <button type="submit" class="btn-modal-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
</body>

</html>