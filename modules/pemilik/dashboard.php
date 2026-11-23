<?php
require_once __DIR__ . '/../../config/init.php';
/** @var PDO $pdo */

$tahun = date('Y');

$kamar_terisi_str    = "0/0";
$menunggu_verif      = 0;
$pemasukan_tahun_ini = "Rp. 0";

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // 1. Hitung Total Kamar & Kamar Terisi dalam 1 Query
        $rowKamar = $pdo->query("
            SELECT 
                COUNT(*) AS total_kamar,
                COUNT(*) FILTER (WHERE LOWER(status_kamar) = 'terisi') AS terisi_kamar
            FROM kamar
        ")->fetch(PDO::FETCH_ASSOC);

        $totalKamar  = (int) ($rowKamar['total_kamar'] ?? 0);
        $terisiKamar = (int) ($rowKamar['terisi_kamar'] ?? 0);
        $kamar_terisi_str = $terisiKamar . '/' . $totalKamar;

        // 2. Hitung Antrean Verifikasi & Pemasukan Sewa Tahun Ini dalam 1 Query
        $stmtStats = $pdo->prepare("
            SELECT
                (SELECT COUNT(DISTINCT tagihan_id) FROM pembayaran WHERE LOWER(TRIM(status_verifikasi)) = 'pending') AS menunggu_verif,
                COALESCE((
                    SELECT SUM(p.jumlah_bayar)
                    FROM pembayaran p
                    JOIN tagihan t ON p.tagihan_id = t.id
                    WHERE LOWER(TRIM(p.status_verifikasi)) IN ('terverifikasi', 'diterima', 'lunas')
                      AND t.kategori_iuran_id IS NULL
                      AND EXTRACT(YEAR FROM COALESCE(p.tanggal_bayar, NOW())) = :tahun
                ), 0) AS total_pemasukan
        ");
        $stmtStats->execute([':tahun' => $tahun]);
        $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

        $menunggu_verif      = (int) ($stats['menunggu_verif'] ?? 0);
        $totalPemasukan      = (float) ($stats['total_pemasukan'] ?? 0);
        $pemasukan_tahun_ini = "Rp. " . number_format($totalPemasukan, 0, ',', '.');

    } catch (Exception $e) {}
}

$pemilikBelumIsiRek = (($_SESSION['user']['role'] ?? '') === 'pemilik')
    && (empty(trim((string)($_SESSION['user']['nama_bank'] ?? ''))) || empty(trim((string)($_SESSION['user']['no_rekening'] ?? ''))));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Dashboard Pemilik</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= filemtime(__DIR__ . '/../../public/css/global.css'); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= filemtime(__DIR__ . '/../../public/css/modules.css'); ?>">
</head>

<body class="dashboard-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <?php if ($pemilikBelumIsiRek): ?>
            <div class="iuran-notif-banner notif-warning banner-wajib-rek-mobile">
                <div class="iuran-notif-icon">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div class="iuran-notif-body">
                    <strong>Rekening Sewa Belum Lengkap</strong>
                    <p>Silakan lengkapi <b>Nama Bank</b> dan <b>Nomor Rekening</b> di <a href="/?page=profile&modal=ubah_profil" class="link-wajib-rek">Profil Akun</a> agar penghuni dapat melakukan transfer sewa kamar.</p>
                </div>
            </div>
            <?php endif; ?>

            <main class="menu-card-container">
                <h2 class="menu-heading">Silahkan pilih menu</h2>
                <div class="menu-grid">
                    <a href="/?page=manajemen_kamar" class="menu-item-box quick-nav-link">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-warehouse"></i></div>
                        <span class="menu-label">Manajemen<br>Kamar &amp; Penghuni</span>
                    </a>
                    <a href="/?page=analisis_kas" class="menu-item-box quick-nav-link">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-chart-simple"></i></div>
                        <span class="menu-label">Analisis Kas</span>
                    </a>
                    <a href="/?page=verifikasi_bayar" class="menu-item-box quick-nav-link">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-list-check"></i></div>
                        <span class="menu-label">Verifikasi<br>Pembayaran</span>
                    </a>
                    <a href="/?page=kelola_iuran" class="menu-item-box quick-nav-link">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-chart-pie"></i></div>
                        <span class="menu-label">Kelola Iuran</span>
                    </a>
                    <a href="/?page=chat_penghuni" class="menu-item-box quick-nav-link">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-comments"></i></div>
                        <span class="menu-label">Chat Penghuni</span>
                    </a>
                    <a href="/?page=edit_tatib" class="menu-item-box quick-nav-link">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-book-open"></i></div>
                        <span class="menu-label">Manajemen Peraturan Kos</span>
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
            <?php if ($pemilikBelumIsiRek): ?>
            <div class="iuran-notif-banner notif-warning banner-wajib-rek">
                <div class="iuran-notif-icon">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div class="iuran-notif-body">
                    <strong>Rekening Sewa Belum Lengkap</strong>
                    <p>Silakan lengkapi <b>Nama Bank</b> dan <b>Nomor Rekening</b> di halaman <a href="/?page=profile&modal=ubah_profil" class="link-wajib-rek">Profil Akun</a> agar penghuni dapat melakukan transfer sewa kamar.</p>
                </div>
            </div>
            <?php endif; ?>

            <h2 class="desktop-header-title">Ringkasan Tahun <?= htmlspecialchars($tahun); ?></h2>
            <p class="desktop-header-subtitle">Kondisi kos per tahun <?= htmlspecialchars($tahun); ?></p>

            <div class="desktop-pemilik-stats-grid">
                <a href="/?page=manajemen_kamar" class="desktop-pemilik-stat-card quick-nav-link">
                    <span class="stat-box-lbl">Kamar Terisi</span>
                    <span class="stat-box-val"><?= htmlspecialchars($kamar_terisi_str); ?></span>
                </a>
                <a href="/?page=verifikasi_bayar" class="desktop-pemilik-stat-card quick-nav-link">
                    <span class="stat-box-lbl">Menunggu Verifikasi</span>
                    <span class="stat-box-val val-danger"><?= htmlspecialchars($menunggu_verif); ?></span>
                </a>
                <a href="/?page=analisis_kas" class="desktop-pemilik-stat-card stat-card-wide quick-nav-link">
                    <span class="stat-box-lbl">Pemasukan Tahun Ini</span>
                    <span class="stat-box-val val-money"><?= htmlspecialchars($pemasukan_tahun_ini); ?></span>
                </a>
            </div>

            <h3 class="desktop-menu-list-title">Menu utama</h3>
            <div class="desktop-pemilik-menu-list">
                <a href="/?page=manajemen_kamar" class="pemilik-menu-row-card quick-nav-link">
                    <div class="pemilik-menu-icon-box">
                        <i class="fa-solid fa-warehouse"></i>
                    </div>
                    <div class="pemilik-menu-text">
                        <h4>Manajemen Kamar &amp; Penghuni</h4>
                        <p>Lihat status kamar dan penyewa</p>
                    </div>
                </a>

                <a href="/?page=analisis_kas" class="pemilik-menu-row-card quick-nav-link">
                    <div class="pemilik-menu-icon-box">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div class="pemilik-menu-text">
                        <h4>Analisis Kas</h4>
                        <p>Monitoring kas</p>
                    </div>
                </a>

                <a href="/?page=edit_tatib" class="pemilik-menu-row-card quick-nav-link">
                    <div class="pemilik-menu-icon-box">
                        <i class="fa-solid fa-book-open"></i>
                    </div>
                    <div class="pemilik-menu-text">
                        <h4>Manajemen Peraturan Kos</h4>
                        <p>Mengatur Peraturan Kos</p>
                    </div>
                </a>

                <a href="/?page=chat_penghuni" class="pemilik-menu-row-card quick-nav-link">
                    <div class="pemilik-menu-icon-box">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <div class="pemilik-menu-text">
                        <h4>Chat Penghuni</h4>
                        <p>Hubungi penghuni langsung melalui WhatsApp</p>
                    </div>
                </a>

                <a href="/?page=kelola_iuran" class="pemilik-menu-row-card quick-nav-link">
                    <div class="pemilik-menu-icon-box">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div class="pemilik-menu-text">
                        <h4>Kelola Iuran</h4>
                        <p>Pemasukan, Tunggakan, Kas</p>
                    </div>
                </a>

                <a href="/?page=verifikasi_bayar" class="pemilik-menu-row-card quick-nav-link">
                    <div class="pemilik-menu-icon-box">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div class="pemilik-menu-text">
                        <h4>Verifikasi Bayar</h4>
                        <p><?= htmlspecialchars($menunggu_verif); ?> bukti transfer menunggu diverifikasi</p>
                    </div>
                </a>
            </div>
        </main>
    </div>

    <script>
        // Prefetch & Progress Bar untuk Kartu Menu Dashboard
        document.addEventListener('DOMContentLoaded', function () {
            const loader = document.getElementById('topNavLoader');

            document.querySelectorAll('.quick-nav-link').forEach(function (link) {
                link.addEventListener('mouseenter', function () {
                    const url = this.getAttribute('href');
                    if (url && !document.querySelector('link[rel="prefetch"][href="' + url + '"]')) {
                        const prefetch = document.createElement('link');
                        prefetch.rel = 'prefetch';
                        prefetch.href = url;
                        document.head.appendChild(prefetch);
                    }
                });

                link.addEventListener('click', function (e) {
                    if (e.ctrlKey || e.metaKey || this.target === '_blank') return;
                    if (loader) {
                        loader.style.opacity = '1';
                        loader.style.width = '75%';
                    }
                });
            });
        });
    </script>

</body>

</html>