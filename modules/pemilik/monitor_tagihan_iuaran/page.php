<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek autentikasi pemilik (opsional/sesuaikan dengan auth sistemmu)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'pemilik') {
    // header("Location: /?page=login&role=pemilik");
    // exit;
}

// Data Dummy / Query Ringkasan Kas Iuran
$totalPemasukan   = 15000000;
$totalPengeluaran = 3000000;

// Data Dummy / Query Verifikasi Tagihan Masuk
$daftarIuran = [
    [
        'nama'     => 'Ridwan',
        'kamar'    => 'Kamar 2',
        'nominal'  => 'Rp. 75.000',
        'tanggal'  => '2026-09-07 08:00',
        'foto'     => 'https://picsum.photos/200'
    ],
    [
        'nama'     => 'Raffi',
        'kamar'    => 'Kamar 5',
        'nominal'  => 'Rp. 75.000',
        'tanggal'  => '2026-11-27 09:00',
        'foto'     => 'https://picsum.photos/201'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Monitoring & Tagihan Iuran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="verifikasi-body">
    <div class="mobile-container">
        <div>
            <!-- Header Modular -->
            <?php require_once __DIR__ . '/../../components/header.php'; ?>

            <main class="verifikasi-container">
                <!-- Banner Judul -->
                <div class="banner-card">
                    <h2 class="banner-title">Monitoring & Tagihan Iuran</h2>
                    <a href="/?page=dashboard_pemilik" class="btn-kembali">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- Ringkasan Pemasukan & Pengeluaran -->
                <div class="monitoring-summary-row">
                    <div class="summary-card">
                        <span class="summary-title">Pemasukan</span>
                        <span class="summary-nominal">Rp <?= number_format($totalPemasukan, 0, ',', '.'); ?></span>
                    </div>
                    <div class="summary-card">
                        <span class="summary-title">Pengeluaran</span>
                        <span class="summary-nominal">Rp <?= number_format($totalPengeluaran, 0, ',', '.'); ?></span>
                    </div>
                </div>

                <!-- List Card Pembayaran Tagihan -->
                <div class="card-list">
                    <?php foreach ($daftarIuran as $iuran): ?>
                        <div class="verifikasi-card">
                            <div class="verifikasi-card-body">
                                <div class="bukti-wrapper">
                                    <img src="<?= htmlspecialchars($iuran['foto']); ?>" alt="Bukti Transfer" class="bukti-img">
                                    <a href="#" class="btn-zoom">
                                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                                    </a>
                                </div>
                                <div class="detail-wrapper">
                                    <div class="user-info-row">
                                        <span class="nama-penghuni"><?= htmlspecialchars($iuran['nama']); ?></span>
                                        <span class="badge-kamar"><?= htmlspecialchars($iuran['kamar']); ?></span>
                                    </div>
                                    <div class="nominal-text"><?= htmlspecialchars($iuran['nominal']); ?></div>
                                    <div class="metode-tanggal">Transfer ~ <?= htmlspecialchars($iuran['tanggal']); ?></div>
                                </div>
                            </div>
                            <div class="verifikasi-card-footer">
                                <a href="#" class="btn-action btn-chat">
                                    <i class="fa-brands fa-whatsapp"></i> Chat
                                </a>
                                <button type="button" class="btn-action btn-decline">
                                    <i class="fa-solid fa-circle-xmark"></i> Tolak
                                </button>
                                <button type="button" class="btn-action btn-approve">
                                    <i class="fa-solid fa-circle-check"></i> Terima
                                </button>
                            </div>
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