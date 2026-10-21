<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_pj = true;
$nama_penghuni = "Mas Dafa";

$daftar_tagihan_saya = [
    [
        'id'          => 1,
        'jenis'       => 'Sewa',
        'nominal'     => 1000000,
        'jatuh_tempo' => '11-10-26'
    ],
    [
        'id'          => 2,
        'jenis'       => 'Iuran',
        'nominal'     => 50000,
        'jatuh_tempo' => '11-10-26'
    ]
];

$daftar_riwayat = [
    [
        'id'      => 101,
        'judul'   => 'Sewa Bulanan',
        'tanggal' => '05 September 2026',
        'nominal' => 1200000
    ],
    [
        'id'      => 102,
        'judul'   => 'Sewa Bulanan',
        'tanggal' => '06 September 2026',
        'nominal' => 200000
    ],
    [
        'id'      => 103,
        'judul'   => 'Sewa Bulanan',
        'tanggal' => '10 September 2026',
        'nominal' => 100000
    ]
];

// Deteksi Tagihan Terpilih via Query Parameter URL
$modal_bayar_id = isset($_GET['bayar_id']) ? (int)$_GET['bayar_id'] : null;
$tagihan_dipilih = null;

if ($modal_bayar_id) {
    foreach ($daftar_tagihan_saya as $item) {
        if ($item['id'] === $modal_bayar_id) {
            $tagihan_dipilih = $item;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Tagihan &amp; Riwayat</title>
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
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title">Tagihan &amp; Riwayat</h2>
                    <a href="/?page=dashboard_penghuni" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <h3 class="section-title-white">Tagihan Saya</h3>
                <div class="section-group">
                    <?php foreach ($daftar_tagihan_saya as $t): ?>
                        <div class="item-tagihan-card">
                            <div class="item-tagihan-header">
                                <h4 class="item-tagihan-name"><?= htmlspecialchars($t['jenis']) ?></h4>
                                <span class="badge-status-krem">Belum bayar</span>
                            </div>

                            <div class="item-tagihan-body">
                                <div class="item-tagihan-left">
                                    <span>Total tagihan</span>
                                    <span>Batas bayar</span>
                                </div>
                                <div class="item-tagihan-right">
                                    <span class="nominal-highlight">Rp <?= number_format($t['nominal'], 0, ',', '.') ?></span>
                                    <span><?= htmlspecialchars($t['jatuh_tempo']) ?></span>
                                </div>
                            </div>

                            <a href="/?page=tagihan_riwayat&bayar_id=<?= $t['id'] ?>" class="btn-card-action">
                                Bayar
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h3 class="section-title-white">Riwayat Transaksi</h3>
                <div class="section-group">
                    <?php foreach ($daftar_riwayat as $r): ?>
                        <div class="item-tagihan-card">
                            <div class="item-tagihan-header">
                                <h4 class="item-tagihan-name"><?= htmlspecialchars($r['judul']) ?></h4>
                                <span class="badge-status-lunas">Lunas</span>
                            </div>

                            <div class="item-tagihan-body">
                                <div class="item-tagihan-left">
                                    <span><?= htmlspecialchars($r['tanggal']) ?></span>
                                </div>
                                <div class="item-tagihan-right">
                                    <span class="nominal-highlight">Rp <?= number_format($r['nominal'], 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>

        <?php require_once __DIR__ . '/../../components/footer.php'; ?>
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
                    <a href="/?page=dashboard_penghuni" class="nav-item">
                        <i class="fa-solid fa-table-cells-large"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="/?page=tagihan_riwayat" class="nav-item active">
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
            <h2 class="desktop-header-title">Tagihan &amp; Riwayat</h2>
            <p class="tagihan-desktop-subtitle">Ringkasan tagihan dan riwayat pembayaran</p>

            <h3 class="tagihan-section-title">Tagihan Saya</h3>
            <div class="desktop-tagihan-grid">
                <?php foreach ($daftar_tagihan_saya as $t): ?>
                    <div class="item-tagihan-card">
                        <div class="item-tagihan-header">
                            <h4 class="item-tagihan-name"><?= htmlspecialchars($t['jenis']) ?></h4>
                            <span class="badge-status-krem">Belum bayar</span>
                        </div>

                        <div class="item-tagihan-body">
                            <div class="item-tagihan-left">
                                <span>Total tagihan</span>
                                <span>Batas bayar</span>
                            </div>
                            <div class="item-tagihan-right">
                                <span class="nominal-highlight">Rp <?= number_format($t['nominal'], 0, ',', '.') ?></span>
                                <span><?= htmlspecialchars($t['jatuh_tempo']) ?></span>
                            </div>
                        </div>

                        <a href="/?page=tagihan_riwayat&bayar_id=<?= $t['id'] ?>" class="btn-card-action">
                            Bayar
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3 class="tagihan-section-title">Riwayat Transaksi</h3>
            <div class="desktop-riwayat-list">
                <?php foreach ($daftar_riwayat as $r): ?>
                    <div class="desktop-riwayat-card">
                        <div class="desktop-riwayat-info">
                            <h4 class="desktop-riwayat-title"><?= htmlspecialchars($r['judul']) ?></h4>
                            <span class="desktop-riwayat-date"><?= htmlspecialchars($r['tanggal']) ?></span>
                        </div>
                        <div class="desktop-riwayat-right">
                            <span class="badge-lunas-pill">Lunas</span>
                            <span class="desktop-riwayat-nominal">Rp <?= number_format($r['nominal'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- POP-UP MODAL BAYAR TAGIHAN -->
    <?php if ($tagihan_dipilih): ?>
        <div class="modal-overlay active">
            <div class="modal-bayar-card">
                <h3 class="modal-bayar-title">Bayar Tagihan</h3>

                <div class="modal-ringkasan-box">
                    <div class="modal-ringkasan-top">
                        <span class="modal-ringkasan-jenis"><?= htmlspecialchars($tagihan_dipilih['jenis']); ?></span>
                        <span class="modal-ringkasan-nominal">Rp <?= number_format($tagihan_dipilih['nominal'], 0, ',', '.'); ?></span>
                    </div>
                    <span class="modal-ringkasan-batas">Batas bayar <?= htmlspecialchars($tagihan_dipilih['jatuh_tempo']); ?></span>
                </div>

                <form action="/?page=tagihan_riwayat" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                    <input type="hidden" name="tagihan_id" value="<?= $tagihan_dipilih['id']; ?>">

                    <div class="modal-rekening-wrapper">
                        <label class="modal-rekening-label">Transfer Ke Rekening</label>
                        <div class="modal-rekening-val">
                            BCA 12345678 a.n Bapak Kos
                        </div>
                    </div>

                    <div class="modal-upload-wrapper">
                        <label class="modal-rekening-label">Upload Bukti Pembayaran</label>
                        <label class="modal-upload-box">
                            <input type="file" name="bukti_transfer" accept="image/*" required>
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#4ca5b0" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"></path>
                                <polyline points="7 9 12 4 17 9"></polyline>
                                <line x1="12" y1="4" x2="12" y2="16"></line>
                            </svg>
                            <p class="modal-upload-text">Ketuk untuk pilih foto</p>
                        </label>
                    </div>

                    <div class="modal-bayar-actions">
                        <a href="/?page=tagihan_riwayat" class="btn-bayar-batal">Batal</a>
                        <button type="submit" class="btn-bayar-kirim">Kirim Bukti</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>