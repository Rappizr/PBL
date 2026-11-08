<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $possible_paths = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../../../config/database.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/database.php'
    ];
    foreach ($possible_paths as $p) {
        if (file_exists($p)) {
            require_once $p;
            if (isset($pdo) && ($pdo instanceof PDO)) break;
        }
    }
}

$tahun = date('Y');

$kamar_terisi_str    = "0/0";
$menunggu_verif      = 0;
$pemasukan_tahun_ini = "Rp. 0";

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // A. HITUNG KAMAR TERISI & TOTAL KAMAR
        $totalKamar = (int) $pdo->query("SELECT COUNT(*) FROM kamar")->fetchColumn();

        $terisiKamar = (int) $pdo->query("
            SELECT COUNT(*) FROM kamar WHERE LOWER(status_kamar) = 'terisi'
        ")->fetchColumn();

        if ($terisiKamar === 0 && $totalKamar > 0) {
            $terisiKamar = (int) $pdo->query("
                SELECT COUNT(DISTINCT kamar_id) FROM penghuni_kamar WHERE LOWER(status_kamar) = 'aktif'
            ")->fetchColumn();
        }

        $kamar_terisi_str = $terisiKamar . '/' . $totalKamar;

        // B. HITUNG JUMLAH MENUNGGU VERIFIKASI (Pemilik: Semua transaksi pending)
        $menunggu_verif = (int) $pdo->query("
            SELECT COUNT(DISTINCT p.tagihan_id) 
            FROM pembayaran p
            WHERE LOWER(TRIM(p.status_verifikasi)) = 'pending'
        ")->fetchColumn();

        // C. HITUNG TOTAL PEMASUKAN SEWA TAHUN INI (HANYA SEWA: t.kategori_iuran_id IS NULL)
        $stmtPemasukan = $pdo->prepare("
            SELECT COALESCE(SUM(p.jumlah_bayar), 0) AS total 
            FROM pembayaran p
            JOIN tagihan t ON p.tagihan_id = t.id
            WHERE LOWER(TRIM(p.status_verifikasi)) IN ('terverifikasi', 'diterima', 'lunas')
              AND t.kategori_iuran_id IS NULL
              AND EXTRACT(YEAR FROM COALESCE(p.tanggal_bayar, NOW())) = :tahun
        ");
        $stmtPemasukan->execute([':tahun' => $tahun]);
        $totalPemasukan = (float) $stmtPemasukan->fetchColumn();

        $pemasukan_tahun_ini = "Rp. " . number_format($totalPemasukan, 0, ',', '.');

    } catch (Exception $e) {}
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
                    <a href="/?page=verifikasi_bayar" class="menu-item-box">
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
                <a href="/?page=verifikasi_bayar" class="desktop-pemilik-stat-card">
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

                <a href="/?page=verifikasi_bayar" class="pemilik-menu-row-card">
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