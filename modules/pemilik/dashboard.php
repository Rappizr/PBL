<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan koneksi $pdo tersedia
if (!isset($pdo)) {
    require_once __DIR__ . '/../../config/database.php';
}

$tahun = date('Y');

// 1. HITUNG KAMAR TERISI (Contoh: "13/16" atau dihitung dinamis dari tabel kamar)
$kamar_terisi_str = "0/0";
try {
    // Total kamar keseluruhan
    $totalKamar = (int)$pdo->query("SELECT COUNT(*) FROM kamar")->fetchColumn();
    
    // Total kamar yang terisi / aktif
    $terisiKamar = (int)$pdo->query("SELECT COUNT(DISTINCT kamar_id) FROM penghuni_kamar WHERE status_kamar = 'aktif'")->fetchColumn();
    
    $kamar_terisi_str = $terisiKamar . '/' . $totalKamar;
} catch (Exception $e) {
    $kamar_terisi_str = "0/0";
}

// 2. HITUNG JUMLAH MENUNGGU VERIFIKASI (Dari tabel pembayaran status 'pending')
$menunggu_verif = 0;
try {
    $menunggu_verif = (int)$pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_verifikasi = 'pending'")->fetchColumn();
} catch (Exception $e) {
    $menunggu_verif = 0;
}

// 3. HITUNG TOTAL PEMASUKAN TAHUN INI (Dari tabel buku_kas dengan tipe 'pemasukan')
$total_pemasukan_val = 0;
try {
    $stmtPemasukan = $pdo->prepare("
        SELECT COALESCE(SUM(nominal), 0) AS total 
        FROM buku_kas 
        WHERE tipe_transaksi = 'pemasukan' 
        AND EXTRACT(YEAR FROM tanggal_transaksi) = :tahun
    ");
    $stmtPemasukan->execute([':tahun' => $tahun]);
    $total_pemasukan_val = (float)$stmtPemasukan->fetchColumn();
} catch (Exception $e) {
    $total_pemasukan_val = 0;
}
$pemasukan_tahun_ini = "Rp. " . number_format($total_pemasukan_val, 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Dashboard Pemilik</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>

<body class="dashboard-body">

    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <main class="menu-card-container">
                <h2 class="menu-heading">Silahkan pilih menu</h2>
                <div class="menu-grid">
                    <a href="/?page=manajemen_kamar" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-warehouse"></i></div>
                        <span class="menu-label">Manajemen<br>Kamar &amp; Penghuni</span>
                    </a>
                    <a href="/?page=arus_kas" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-chart-simple"></i></div>
                        <span class="menu-label">Analisis Kas</span>
                    </a>
                    <a href="/?page=verifikasi_pembayaran" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-list-check"></i></div>
                        <span class="menu-label">Verifikasi<br>Pembayaran</span>
                    </a>
                    <a href="/?page=kelola_iuran" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-chart-pie"></i></div>
                        <span class="menu-label">Kelola Iuran</span>
                    </a>
                    <a href="/?page=chat_penghuni" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-comments"></i></div>
                        <span class="menu-label">Chat Penghuni</span>
                    </a>
                    <a href="/?page=edit_tatib" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-book-open"></i></div>
                        <span class="menu-label">Manajemen Peraturan kos</span>
                    </a>
                </div>
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Ringkasan Tahun <?= $tahun ?></h2>
            <p class="desktop-header-subtitle">Kondisi kos per tahun <?= $tahun; ?></p>

            <div class="desktop-pemilik-stats-grid">
                <a href="/?page=manajemen_kamar" class="desktop-pemilik-stat-card">
                    <span class="stat-box-lbl">Kamar Terisi</span>
                    <span class="stat-box-val"><?= $kamar_terisi_str; ?></span>
                </a>
                <a href="/?page=verifikasi_pembayaran" class="desktop-pemilik-stat-card">
                    <span class="stat-box-lbl">Menunggu Verifikasi</span>
                    <span class="stat-box-val val-danger"><?= $menunggu_verif; ?></span>
                </a>
                <div class="desktop-pemilik-stat-card stat-card-wide">
                    <span class="stat-box-lbl">Pemasukan Tahun Ini</span>
                    <span class="stat-box-val val-money"><?= $pemasukan_tahun_ini; ?></span>
                </div>
            </div>

            <h3 class="desktop-menu-list-title">Menu utama</h3>
            <div class="desktop-pemilik-menu-list">
                <a href="/?page=manajemen_kamar" class="pemilik-menu-row-card">
                    <div class="pemilik-menu-icon-box">
                        <i class="fa-solid fa-warehouse"></i>
                    </div>
                    <div class="pemilik-menu-text">
                        <h4>Manajemen Kamar &amp; penghuni</h4>
                        <p>Lihat status kamar dan penyewa</p>
                    </div>
                </a>

                <a href="/?page=kelola_iuran" class="pemilik-menu-row-card">
                    <div class="pemilik-menu-icon-box">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div class="pemilik-menu-text">
                        <h4>Monitoring &amp; Tagihan iuran</h4>
                        <p>Pemasukan, Tunggakan, Kas</p>
                    </div>
                </a>

                <a href="/?page=verifikasi_pembayaran" class="pemilik-menu-row-card">
                    <div class="pemilik-menu-icon-box">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div class="pemilik-menu-text">
                        <h4>Verifikasi pembayaran</h4>
                        <p><?= $menunggu_verif; ?> bukti transfer menunggu diverifikasi</p>
                    </div>
                </a>

                <a href="/?page=chat_penghuni" class="pemilik-menu-row-card">
                    <div class="pemilik-menu-icon-box">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <div class="pemilik-menu-text">
                        <h4>Chat penghuni</h4>
                        <p>Hubungi penghuni langsung melalui Whatsapp</p>
                    </div>
                </a>

                <a href="/?page=arus_kas" class="pemilik-menu-row-card">
                    <div class="pemilik-menu-icon-box">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div class="pemilik-menu-text">
                        <h4>Analisis Kas</h4>
                        <p>Monitoring kas</p>
                    </div>
                </a>

                <a href="/?page=edit_tatib" class="pemilik-menu-row-card">
                    <div class="pemilik-menu-icon-box">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>
                    <div class="pemilik-menu-text">
                        <h4>Manajemen Peraturan Kos</h4>
                        <p>Mengatur Peraturan Kos</p>
                    </div>
                </a>
            </div>
        </main>
    </div>

</body>

</html>