<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nama_penghuni = $_SESSION['user']['nama'];

// Data dummy sesuai mockup
$daftar_tagihan_saya = [
    [
        'id' => 1,
        'jenis' => 'Sewa',
        'nominal' => 450000,
        'jatuh_tempo' => '11-10-26'
    ],
    [
        'id' => 2,
        'jenis' => 'Iuran',
        'nominal' => 450000,
        'jatuh_tempo' => '11-10-26'
    ]
];

$daftar_riwayat = [
    [
        'id' => 101,
        'judul' => 'Sewa Bulanan',
        'tanggal' => '05 September 2026',
        'nominal' => 1200000
    ],
    [
        'id' => 102,
        'judul' => 'Iuran Bulanan',
        'tanggal' => '05 September 2026',
        'nominal' => 1200000
    ]
];
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
    <div class="mobile-container">
        <div>
            <!-- Header Top Bar -->
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
                <!-- Banner Tagihan & Riwayat + Tombol Kembali -->
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title">Tagihan &amp; Riwayat</h2>
                    <a href="/?page=dashboard_pemilik" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- Section: Tagihan Saya -->
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

                            <a href="/?page=penghuni-bayar&id=<?= $t['id'] ?>" class="btn-card-action">
                                Bayar
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Section: Riwayat Transaksi -->
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

                            <a href="/?page=cetak-invoice&id=<?= $r['id'] ?>" target="_blank" class="btn-card-action">
                                Cetak Invoice
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>

        <!-- Footer Modular -->
        <?php require_once __DIR__ . '/../../components/footer.php'; ?>
    </div>
</body>
</html>