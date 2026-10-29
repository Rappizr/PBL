<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

// Hitung pembayaran yang masih menunggu verifikasi
$stmtPendingPembayaran = $pdo->query("
    SELECT COUNT(*) AS total
    FROM pembayaran
    WHERE status_verifikasi = 'pending'
");
$pendingPembayaranCount = (int) $stmtPendingPembayaran->fetchColumn();
$nama_pemilik = 'Joko Budi';

$kamarTerisi = 13;
$kamarTotal = 16;
$pemasukanHarini = 19500000;

function formatTanggal(string $tanggal): string
{
    $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $ts = strtotime($tanggal);
    return date('d', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function formatRupiah(int $angka): string
{
    return 'Rp. ' . number_format($angka, 0, ',', '.');
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Dashboard Pemilik</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="/css/styles.css">
</head>

<body class="dashboard-body">
    <div class="mobile-container mobile-dashboard-view">
        <?php require_once __DIR__ . '/../components/header.php'; ?>

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
                </div>
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <aside class="desktop-sidebar">
            <div>
                <h1 class="sidebar-brand">SITEMAN - KOS</h1>

                <div class="sidebar-profile">
                    <div class="sidebar-avatar"><i class="fa-solid fa-user"></i></div>
                    <span class="sidebar-name"><?= htmlspecialchars($nama_pemilik); ?></span>
                </div>

                <nav class="sidebar-nav">
                    <a href="/?page=dashboard_pemilik" class="nav-item active">
                        <i class="fa-solid fa-table-cells-large"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="/?page=manajemen_kamar" class="nav-item">
                        <i class="fa-solid fa-warehouse"></i>
                        <span>Manajemen Kamar &amp; Penghuni</span>
                    </a>
                    <a href="/?page=kelola_iuran" class="nav-item">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>Monitoring &amp; Tagihan Iuran</span>
                    </a>
                    <a href="/?page=verifikasi_pembayaran" class="nav-item">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Verifikasi Pembayaran</span>
                    </a>
                    <a href="/?page=chat_penghuni" class="nav-item">
                        <i class="fa-solid fa-comments"></i>
                        <span>Chat Penghuni</span>
                    </a>
                    <a href="/?page=arus_kas" class="nav-item">
                        <i class="fa-solid fa-wallet"></i>
                        <span>Analisis Kas</span>
                    </a>
                </nav>
            </div>

            <div class="sidebar-footer">
                <p class="sidebar-footer-title">SITEMAN - KOS &copy; 2g2b - 2026</p>
                <p class="sidebar-footer-desc">Sistem Manajemen Operasion Kos</p>
            </div>
        </aside>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Ringkasan Hari Ini</h2>
            <p class="desktop-header-subtitle">Kondisi kos per <?= formatTanggal(date('Y-m-d')); ?></p>

            <div class="pemilik-stat-grid">
                <div class="pemilik-stat-card">
                    <span class="pemilik-stat-label">Kamar Terisi</span>
                    <span class="pemilik-stat-val"><?= $kamarTerisi ?>/<?= $kamarTotal ?></span>
                </div>
                <div class="pemilik-stat-card">
                    <span class="pemilik-stat-label">Menunggu Verifikasi</span>
                    <span class="pemilik-stat-val merah"><?= $pendingPembayaranCount ?></span>
                </div>
                <div class="pemilik-stat-card lebar">
                    <span class="pemilik-stat-label">Pemasukan Hari Ini</span>
                    <span class="pemilik-stat-val"><?= formatRupiah($pemasukanHarini) ?></span>
                </div>
            </div>

            <h3 class="menu-utama-title">Menu utama</h3>
                <div class="pemilik-menu-list">
                    <a href="/?page=manajemen_kamar" class="pemilik-menu-row">
                        <div class="pemilik-menu-icon"><i class="fa-solid fa-warehouse"></i></div>
                        <div class="pemilik-menu-text">
                            <span class="pemilik-menu-title">Manajemen Kamar &amp; Penghuni</span>
                            <span class="pemilik-menu-desc">Lihat status kamar dan penyewa</span>
                        </div>
                    </a>

                    <a href="/?page=kelola_iuran" class="pemilik-menu-row">
                        <div class="pemilik-menu-icon"><i class="fa-solid fa-chart-pie"></i></div>
                        <div class="pemilik-menu-text">
                            <span class="pemilik-menu-title">Monitoring &amp; Tagihan Iuran</span>
                            <span class="pemilik-menu-desc">Pemasukan, Tunggakan, Kas</span>
                        </div>
                    </a>

                    <a href="/?page=verifikasi_pembayaran" class="pemilik-menu-row">
                        <div class="pemilik-menu-icon"><i class="fa-solid fa-list-check"></i></div>
                        <div class="pemilik-menu-text">
                            <span class="pemilik-menu-title">Verifikasi Pembayaran</span>
                            <span class="pemilik-menu-desc">
                                <?= $pendingPembayaranCount ?> bukti transfer menunggu diverifikasi
                            </span>
                        </div>
                    </a>

                    <a href="/?page=chat_penghuni" class="pemilik-menu-row">
                        <div class="pemilik-menu-icon"><i class="fa-solid fa-comments"></i></div>
                        <div class="pemilik-menu-text">
                            <span class="pemilik-menu-title">Chat Penghuni</span>
                            <span class="pemilik-menu-desc">Hubungi penghuni langsung melalui Whatsapp</span>
                        </div>
                    </a>

                    <a href="/?page=arus_kas" class="pemilik-menu-row">
                        <div class="pemilik-menu-icon"><i class="fa-solid fa-wallet"></i></div>
                        <div class="pemilik-menu-text">
                            <span class="pemilik-menu-title">Analisis Kas</span>
                            <span class="pemilik-menu-desc">Monitoring kas</span>
                        </div>
                    </a>
                </div>
        </main>
    </div>

</body>
</html>