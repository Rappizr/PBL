<?php
// if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'penghuni') {
//     $_SESSION['login_error'] = 'Anda harus masuk terlebih dahulu!';
//     header("Location: /?page=login&role=penghuni");
//     exit;
// }

require_once __DIR__ . '/../../config/database.php';

// Hitung pembayaran yang masih menunggu verifikasi
$stmtPendingPembayaranIuran = $pdo->query("
    SELECT COUNT(*) AS total
    FROM pembayaran
    WHERE status_verifikasi = 'pending'
");
$pendingPembayaranIuranCount = (int) $stmtPendingPembayaranIuran->fetchColumn();


$is_pj = true;
$nama_penghuni = 'Mas Dafa';

$tagihan = [
    'sewa' => [
        'status' => 'belum_lunas',
        'tanggal_jatuh_tempo' => '2026-10-11',
    ],
    'iuran' => [
        'status' => 'belum_lunas',
        'tanggal_jatuh_tempo' => '2026-10-11',
    ],
];

function formatTanggalIndo(?string $tanggal): string {
    if (!$tanggal) return '-';
    $hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $bulan = [1 => 'October', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
    
    $ts = strtotime($tanggal);
    $hariNama = $hari[date('l', $ts)] ?? date('D', $ts);
    $bulanNama = $bulan[(int)date('n', $ts)] ?? date('F', $ts);
    
    return $hariNama . ', ' . date('d', $ts) . ' ' . $bulanNama . ' ' . date('Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>

<body class="dashboard-body">
    <div class="mobile-container">
        <div>
            <!-- Header -->
        <header class="dashboard-header">
            <h1 class="brand-title-dashboard">SITEMAN - KOS</h1>
            <div class="kotak-kapsul-pemilik">
                <span class="lingkaran-user-abu">
                    <i class="fa-solid fa-user"></i>
                </span>
                <span class="tulisan-nama">Penghuni</span>
            </div>
        </header>

            <main class="page-content">
                <!-- Cards Tagihan -->
                <?php foreach (['sewa' => 'Pembayaran Sewa', 'iuran' => 'Pembayaran Iuran'] as $jenis => $label): ?>
                    <?php $t = $tagihan[$jenis]; ?>
                    <div class="tagihan-dark-card">
                        <span class="tagihan-pill-label"><?= $label ?></span>
                        <div class="tagihan-meta-grid">
                            <div class="meta-row">
                                <span class="meta-label">Status Pembayaran</span>
                                <span class="meta-colon">:</span>
                                <span class="meta-value">
                                    <span class="status-badge-yellow">
                                        <?= ($t && $t['status'] === 'lunas') ? 'Lunas' : 'Belum Lunas'; ?>
                                    </span>
                                </span>
                            </div>
                            <div class="meta-row">
                                <span class="meta-label">Tenggat Pembayaran</span>
                                <span class="meta-colon">:</span>
                                <span class="meta-value date-text">
                                    <?= formatTanggalIndo($t['tanggal_jatuh_tempo'] ?? null) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="menu-card-container">
                    <h2 class="menu-heading">Silahkan pilih menu</h2>
                    <div class="menu-grid">
                        <!-- Menu 1 -->
                        <a href="/?page=penghuni-tagihan" class="menu-item-box">
                            <div class="menu-icon-wrapper">
                                <i class="fa-solid fa-receipt"></i>
                            </div>
                            <span class="menu-label">Tagihan &amp; Riwayat</span>
                        </a>

                        <!-- Menu 2 -->
                        <a href="/?page=penghuni-bayar" class="menu-item-box">
                            <div class="menu-icon-wrapper">
                                <i class="fa-solid fa-file-invoice-dollar"></i>
                            </div>
                            <span class="menu-label">Pembayaran</span>
                        </a>

                        <a href="/?page=verifikasi-pembayaran" class="menu-item-box">
                            <div class="menu-icon-wrapper">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            <span class="menu-label">Verifikasi<br>Pembayaran Iuran</span>
                            <?php if ($pendingPembayaranIuranCount > 0): ?>
                                <span class="badge-notif">
                                    <i class="fa-solid fa-bell"></i>
                                    <?= $pendingPembayaranIuranCount ?> Notifikasi
                                </span>
                            <?php endif; ?>
                        </a>

                        <!-- Menu 3 -->
                        <a href="/?page=penghuni-peraturan" class="menu-item-box">
                            <div class="menu-icon-wrapper">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                            <span class="menu-label">Peraturan Kos</span>
                        </a>

                        <!-- Menu 4 -->
                        <a href="/?page=penghuni-chat" class="menu-item-box">
                            <div class="menu-icon-wrapper">
                                <i class="fa-solid fa-comment-dots"></i>
                            </div>
                            <span class="menu-label">Chat Penghuni</span>
                        </a>

                        <!-- Menu 5 (Kondisional: Monitor Kas vs Kelola & Monitor Iuran) -->
                        <?php if (!$is_pj): ?>
                            <a href="/?page=penghuni-kas" class="menu-item-box">
                                <div class="menu-icon-wrapper">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                                <span class="menu-label">Monitor Kas</span>
                            </a>
                        <?php else: ?>
                            <a href="/?page=pj-kelola-iuran" class="menu-item-box">
                                <div class="menu-icon-wrapper">
                                    <i class="fa-solid fa-wallet"></i>
                                </div>
                                <span class="menu-label">Kelola &amp; Monitor<br>Iuran</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>
</body>
</html>