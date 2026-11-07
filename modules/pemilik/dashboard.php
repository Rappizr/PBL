<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Panggil koneksi database dengan jalur yang tepat berdasarkan struktur foldermu
if (!isset($pdo)) {
    $db_path = __DIR__ . '/../../config/database.php';
    if (file_exists($db_path)) {
        require_once $db_path;
    }
}

$tahun = date('Y');

// Default Fallback Values
$kamar_terisi_str    = "0/0";
$menunggu_verif      = 0;
$pemasukan_tahun_ini = "Rp. 0";

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // A. HITUNG KAMAR TERISI & TOTAL KAMAR
        $totalKamar = (int) $pdo->query("SELECT COUNT(*) FROM kamar")->fetchColumn();

        // Cek kamar berstatus 'terisi' dari tabel kamar
        $terisiKamar = (int) $pdo->query("
            SELECT COUNT(*) FROM kamar WHERE status_kamar = 'terisi'
        ")->fetchColumn();

        // Fallback: jika status kamar di tabel kamar belum di-update, cek dari penghuni_kamar yang 'aktif'
        if ($terisiKamar === 0 && $totalKamar > 0) {
            $terisiKamar = (int) $pdo->query("
                SELECT COUNT(DISTINCT kamar_id) FROM penghuni_kamar WHERE status_kamar = 'aktif'
            ")->fetchColumn();
        }

        $kamar_terisi_str = $terisiKamar . '/' . $totalKamar;

        // B. HITUNG JUMLAH MENUNGGU VERIFIKASI (status_verifikasi = 'pending')
        $menunggu_verif = (int) $pdo->query("
            SELECT COUNT(*) FROM pembayaran WHERE status_verifikasi = 'pending'
        ")->fetchColumn();

        // C. HITUNG TOTAL PEMASUKAN TAHUN INI (Dari tabel buku_kas atau pembayaran diterima)
        $stmtPemasukan = $pdo->prepare("
            SELECT COALESCE(SUM(nominal), 0) AS total 
            FROM buku_kas 
            WHERE tipe_transaksi = 'pemasukan' 
              AND EXTRACT(YEAR FROM tanggal_transaksi) = :tahun
        ");
        $stmtPemasukan->execute([':tahun' => $tahun]);
        $totalPemasukan = (float) $stmtPemasukan->fetchColumn();

        // Fallback jika buku_kas masih 0, ambil dari pembayaran yang 'diterima'
        if ($totalPemasukan == 0) {
            $stmtPembayaran = $pdo->prepare("
                SELECT COALESCE(SUM(jumlah_bayar), 0) AS total 
                FROM pembayaran 
                WHERE status_verifikasi = 'diterima' 
                  AND EXTRACT(YEAR FROM tanggal_bayar) = :tahun
            ");
            $stmtPembayaran->execute([':tahun' => $tahun]);
            $totalPemasukan = (float) $stmtPembayaran->fetchColumn();
        }

        $pemasukan_tahun_ini = "Rp. " . number_format($totalPemasukan, 0, ',', '.');

    } catch (Exception $e) {
        // Tangkap error jika query gagal
    }
}
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

    <!-- LAYOUT MOBILE -->
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

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Ringkasan Tahun <?= htmlspecialchars($tahun); ?></h2>
            <p class="desktop-header-subtitle">Kondisi kos per tahun <?= htmlspecialchars($tahun); ?></p>

            <div class="desktop-pemilik-stats-grid">
                <a href="/?page=manajemen_kamar" class="desktop-pemilik-stat-card">
                    <span class="stat-box-lbl">Kamar Terisi</span>
                    <span class="stat-box-val"><?= htmlspecialchars($kamar_terisi_str); ?></span>
                </a>
                <a href="/?page=verifikasi_pembayaran" class="desktop-pemilik-stat-card">
                    <span class="stat-box-lbl">Menunggu Verifikasi</span>
                    <span class="stat-box-val val-danger"><?= htmlspecialchars($menunggu_verif); ?></span>
                </a>
                <div class="desktop-pemilik-stat-card stat-card-wide">
                    <span class="stat-box-lbl">Pemasukan Tahun Ini</span>
                    <span class="stat-box-val val-money"><?= htmlspecialchars($pemasukan_tahun_ini); ?></span>
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
                        <p><?= htmlspecialchars($menunggu_verif); ?> bukti transfer menunggu diverifikasi</p>
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