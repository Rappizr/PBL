<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Load Koneksi Database
global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $possible_paths = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../config/database.php',
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

// 2. Load Handler Tagihan & Helper Pengingat Sewa / Iuran
$handler_path = __DIR__ . '/handler/tagihan_handler.php';
if (file_exists($handler_path)) {
    require_once $handler_path;
}
require_once __DIR__ . '/../shared/handler/sewa_reminder_helper.php';
require_once __DIR__ . '/../shared/handler/iuran_reminder_helper.php';

// 3. Identifikasi Akun & Resolusi ID Kamar via Handler Resmi
$currentUser = $_SESSION['user'] ?? [];
$is_pj       = !empty($currentUser['is_pj']) || !empty($currentUser['is_ketua_kos']) || ($currentUser['role'] ?? '') === 'pj';
$penghuniId  = (int)($currentUser['id'] ?? 0);

// Evaluasi status jatuh tempo sewa & iuran (+7 hari masa tenggang)
$evalSewa  = ($penghuniId > 0 && isset($pdo)) ? evaluasiStatusSewaPenghuni($pdo, $penghuniId) : null;
$evalIuran = ($penghuniId > 0 && isset($pdo)) ? evaluasiStatusIuranPenghuni($pdo, $penghuniId) : null;

$peringatanTagihan = $evalSewa ?? $evalIuran;
if (($evalSewa['status_akun'] ?? '') === 'freeze' || ($evalIuran['status_akun'] ?? '') === 'freeze') {
    $_SESSION['user']['status_akun'] = 'freeze';
    header('Location: /?page=akun_freeze');
    exit;
}

$isPeringatanIuran = (($peringatanTagihan['jenis_tunggakan'] ?? 'sewa') === 'iuran');
$judulPeringatan   = $isPeringatanIuran ? 'Pengingat Iuran' : 'Pengingat Sewa';

$pk_id = function_exists('resolvePenghuniKamarId')
    ? resolvePenghuniKamarId($pdo, $currentUser)
    : (int)($currentUser['penghuni_kamar_id'] ?? 0);

// 4. Ambil Daftar Tagihan Riil dari Handler
$daftar_tagihan = function_exists('fetchDaftarTagihan')
    ? fetchDaftarTagihan($pdo, $pk_id)
    : [];

// Susun Ringkasan Dinamis
$ringkasan = [
    'sewa' => [
        'id'            => null,
        'status_label'  => 'Belum Ada Tagihan',
        'badge_class'   => 'pill-neutral',
        'tenggat'       => '-',
        'action_label'  => 'Bayar Sekarang',
        'is_actionable' => false
    ],
    'iuran' => [
        'id'            => null,
        'status_label'  => 'Belum Ada Tagihan',
        'badge_class'   => 'pill-neutral',
        'tenggat'       => '-',
        'action_label'  => 'Bayar Sekarang',
        'is_actionable' => false
    ]
];

foreach ($daftar_tagihan as $t) {
    $key = (stripos($t['jenis'], 'sewa') !== false) ? 'sewa' : 'iuran';

    $is_lunas   = ($t['status_tagihan'] === 'lunas');
    $is_pending = (($t['status_verif'] ?? '') === 'pending' || $t['status_tagihan'] === 'sebagian');
    $is_ditolak = (($t['status_verif'] ?? '') === 'ditolak');

    if ($is_lunas) {
        $status_txt    = 'Lunas';
        $badge_class   = 'pill-lunas';
        $action_label  = 'Lunas';
        $is_actionable = false;
    } elseif ($is_pending) {
        $status_txt    = 'Menunggu Verifikasi';
        $badge_class   = 'pill-pending';
        $action_label  = 'Sedang Diproses';
        $is_actionable = false;
    } elseif ($is_ditolak) {
        $status_txt    = 'Ditolak';
        $badge_class   = 'pill-ditolak';
        $action_label  = 'Upload Ulang Bukti';
        $is_actionable = true;
    } else {
        $status_txt    = 'Belum Lunas';
        $badge_class   = 'pill-belum';
        $action_label  = 'Upload Bukti Pembayaran';
        $is_actionable = true;
    }

    $ringkasan[$key] = [
        'id'            => (int)$t['id'],
        'status_label'  => $status_txt,
        'badge_class'   => $badge_class,
        'tenggat'       => $t['jatuh_tempo'] ?? '-',
        'action_label'  => $action_label,
        'is_actionable' => $is_actionable
    ];
}

$targetBayarIdBanner = $isPeringatanIuran ? $ringkasan['iuran']['id'] : $ringkasan['sewa']['id'];
$labelBtnBanner      = $isPeringatanIuran ? 'Bayar Iuran' : 'Bayar Sewa';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= filemtime(__DIR__ . '/../../public/css/global.css'); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= filemtime(__DIR__ . '/../../public/css/modules.css'); ?>">
</head>

<body class="dashboard-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <!-- BANNER PENGINGAT SEWA / IURAN -->
            <?php if (!empty($peringatanTagihan)): ?>
            <?php $isTenggang = ($peringatanTagihan['fase'] === 'tenggang'); ?>
            <div class="iuran-notif-banner <?= $isTenggang ? 'notif-danger' : 'notif-warning'; ?>">
                <div class="iuran-notif-icon">
                    <i class="fa-solid <?= $isTenggang ? 'fa-triangle-exclamation' : 'fa-bell'; ?>"></i>
                </div>
                <div class="iuran-notif-body">
                    <strong><?= htmlspecialchars($judulPeringatan); ?>: <?= htmlspecialchars($peringatanTagihan['label_status']); ?></strong>
                    <p><?= htmlspecialchars($peringatanTagihan['pesan_ringkas']); ?></p>
                </div>
                <a href="/?page=tagihan_riwayat<?= $targetBayarIdBanner ? '&bayar_id=' . $targetBayarIdBanner : ''; ?>" class="iuran-notif-btn">
                    <?= htmlspecialchars($labelBtnBanner); ?>
                </a>
            </div>
            <?php endif; ?>

            <!-- Ringkasan Tagihan Penghuni / PJ Dinamis -->
            <div class="penghuni-mobile-summary">
                <!-- Pembayaran Sewa -->
                <div class="penghuni-summary-card">
                    <span class="badge-tagihan-title">Pembayaran Sewa</span>
                    <div class="tagihan-info-grid">
                        <div class="tagihan-info-row">
                            <span class="info-label">Status Pembayaran</span>
                            <span class="info-colon">:</span>
                            <span class="badge-status-pill <?= $ringkasan['sewa']['badge_class']; ?>">
                                <?= htmlspecialchars($ringkasan['sewa']['status_label']); ?>
                            </span>
                        </div>
                        <div class="tagihan-info-row">
                            <span class="info-label">Tenggat Pembayaran</span>
                            <span class="info-colon">:</span>
                            <span class="info-date"><?= htmlspecialchars($ringkasan['sewa']['tenggat']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Pembayaran Iuran -->
                <div class="penghuni-summary-card">
                    <span class="badge-tagihan-title">Pembayaran Iuran</span>
                    <div class="tagihan-info-grid">
                        <div class="tagihan-info-row">
                            <span class="info-label">Status Pembayaran</span>
                            <span class="info-colon">:</span>
                            <span class="badge-status-pill <?= $ringkasan['iuran']['badge_class']; ?>">
                                <?= htmlspecialchars($ringkasan['iuran']['status_label']); ?>
                            </span>
                        </div>
                        <div class="tagihan-info-row">
                            <span class="info-label">Tenggat Pembayaran</span>
                            <span class="info-colon">:</span>
                            <span class="info-date"><?= htmlspecialchars($ringkasan['iuran']['tenggat']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid Menu Utama Mobile -->
            <div class="menu-card-container">
                <h2 class="menu-heading">Silahkan pilih menu</h2>
                <div class="menu-grid">

                    <a href="/?page=tagihan_riwayat" class="menu-item-box quick-nav-link">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-receipt"></i></div>
                        <span class="menu-label">Tagihan &amp; Riwayat</span>
                    </a>

                    <a href="/?page=iuran" class="menu-item-box quick-nav-link">
                        <div class="menu-icon-wrapper">
                            <i class="fa-solid <?= $is_pj ? 'fa-wallet' : 'fa-chart-pie'; ?>"></i>
                        </div>
                        <span class="menu-label"><?= $is_pj ? 'Kelola Iuran' : 'Monitoring<br>Iuran'; ?></span>
                    </a>

                    <a href="/?page=peraturan_kost" class="menu-item-box quick-nav-link">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-book-open"></i></div>
                        <span class="menu-label">Peraturan Kos</span>
                    </a>

                    <a href="/?page=chat_penghuni" class="menu-item-box quick-nav-link">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-comment-dots"></i></div>
                        <span class="menu-label">Chat Penghuni</span>
                    </a>

                    <?php if ($is_pj): ?>
                    <a href="/?page=verifikasi_bayar" class="menu-item-box quick-nav-link">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-list-check"></i></div>
                        <span class="menu-label">Verifikasi<br>Pembayaran</span>
                    </a>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Dashboard</h2>
            <p class="desktop-header-subtitle">Ringkasan tagihan dan akses cepat untukmu</p>

            <!-- BANNER PENGINGAT SEWA / IURAN DESKTOP -->
            <?php if (!empty($peringatanTagihan)): ?>
            <?php $isTenggang = ($peringatanTagihan['fase'] === 'tenggang'); ?>
            <div class="iuran-notif-banner dash-banner-max <?= $isTenggang ? 'notif-danger' : 'notif-warning'; ?>">
                <div class="iuran-notif-icon">
                    <i class="fa-solid <?= $isTenggang ? 'fa-triangle-exclamation' : 'fa-bell'; ?>"></i>
                </div>
                <div class="iuran-notif-body">
                    <strong><?= htmlspecialchars($judulPeringatan); ?>: <?= htmlspecialchars($peringatanTagihan['label_status']); ?></strong>
                    <p><?= htmlspecialchars($peringatanTagihan['pesan_ringkas']); ?> Total tagihan: <strong>Rp <?= number_format((float)$peringatanTagihan['total_tunggakan'], 0, ',', '.'); ?></strong></p>
                </div>
                <a href="/?page=tagihan_riwayat<?= $targetBayarIdBanner ? '&bayar_id=' . $targetBayarIdBanner : ''; ?>" class="iuran-notif-btn">
                    <?= htmlspecialchars($labelBtnBanner); ?> Sekarang
                </a>
            </div>
            <?php endif; ?>

            <div class="desktop-tagihan-row">
                <!-- Card Sewa Desktop -->
                <div class="desktop-tagihan-card">
                    <span class="tagihan-badge-white">Pembayaran Sewa</span>
                    <div class="tagihan-details">
                        <div class="tagihan-detail-row">
                            <span class="lbl">Status Pembayaran</span><span class="colon">:</span>
                            <span class="dash-pill-desktop <?= $ringkasan['sewa']['badge_class']; ?>">
                                <?= htmlspecialchars($ringkasan['sewa']['status_label']); ?>
                            </span>
                        </div>
                        <div class="tagihan-detail-row">
                            <span class="lbl">Tenggat</span><span class="colon">:</span>
                            <span><?= htmlspecialchars($ringkasan['sewa']['tenggat']); ?></span>
                        </div>
                    </div>
                    <?php if ($ringkasan['sewa']['is_actionable'] && $ringkasan['sewa']['id']): ?>
                    <a href="/?page=tagihan_riwayat&bayar_id=<?= $ringkasan['sewa']['id']; ?>" class="btn-upload-bukti quick-nav-link">
                        <?= htmlspecialchars($ringkasan['sewa']['action_label']); ?>
                    </a>
                    <?php else: ?>
                    <a href="/?page=tagihan_riwayat" class="btn-upload-bukti dash-btn-muted quick-nav-link">
                        <?= htmlspecialchars($ringkasan['sewa']['action_label']); ?>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Card Iuran Desktop -->
                <div class="desktop-tagihan-card">
                    <span class="tagihan-badge-white">Pembayaran Iuran</span>
                    <div class="tagihan-details">
                        <div class="tagihan-detail-row">
                            <span class="lbl">Status Pembayaran</span><span class="colon">:</span>
                            <span class="dash-pill-desktop <?= $ringkasan['iuran']['badge_class']; ?>">
                                <?= htmlspecialchars($ringkasan['iuran']['status_label']); ?>
                            </span>
                        </div>
                        <div class="tagihan-detail-row">
                            <span class="lbl">Tenggat</span><span class="colon">:</span>
                            <span><?= htmlspecialchars($ringkasan['iuran']['tenggat']); ?></span>
                        </div>
                    </div>
                    <?php if ($ringkasan['iuran']['is_actionable'] && $ringkasan['iuran']['id']): ?>
                    <a href="/?page=tagihan_riwayat&bayar_id=<?= $ringkasan['iuran']['id']; ?>" class="btn-upload-bukti quick-nav-link">
                        <?= htmlspecialchars($ringkasan['iuran']['action_label']); ?>
                    </a>
                    <?php else: ?>
                    <a href="/?page=tagihan_riwayat" class="btn-upload-bukti dash-btn-muted quick-nav-link">
                        <?= htmlspecialchars($ringkasan['iuran']['action_label']); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <h3 class="menu-utama-title">Menu utama</h3>
            <div class="desktop-menu-grid">
                <a href="/?page=tagihan_riwayat" class="desktop-menu-card quick-nav-link">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-receipt"></i></div>
                    <span class="desktop-menu-label">Tagihan &amp;<br>Riwayat</span>
                </a>

                <a href="/?page=peraturan_kost" class="desktop-menu-card quick-nav-link">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-book-open"></i></div>
                    <span class="desktop-menu-label">Peraturan<br>Kos</span>
                </a>

                <a href="/?page=chat_penghuni" class="desktop-menu-card quick-nav-link">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-comment-dots"></i></div>
                    <span class="desktop-menu-label">Chat<br>Penghuni</span>
                </a>

                <a href="/?page=iuran" class="desktop-menu-card quick-nav-link">
                    <div class="desktop-menu-icon">
                        <i class="fa-solid <?= $is_pj ? 'fa-wallet' : 'fa-chart-pie'; ?>"></i>
                    </div>
                    <span class="desktop-menu-label"><?= $is_pj ? 'Kelola <br>Iuran' : 'Monitoring &amp;<br>Tagihan Iuran'; ?></span>
                </a>

                <?php if ($is_pj): ?>
                <a href="/?page=verifikasi_bayar" class="desktop-menu-card quick-nav-link">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-list-check"></i></div>
                    <span class="desktop-menu-label">Verifikasi<br>Pembayaran</span>
                </a>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
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