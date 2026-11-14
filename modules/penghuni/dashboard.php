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

// 2. Load Handler Tagihan Penghuni
$handler_path = __DIR__ . '/handler/tagihan_handler.php';
if (file_exists($handler_path)) {
    require_once $handler_path;
}

// 3. Identifikasi Akun & Resolusi ID Kamar via Handler Resmi
$currentUser = $_SESSION['user'] ?? [];
$is_pj       = !empty($currentUser['is_pj']) || !empty($currentUser['is_ketua_kos']) || ($currentUser['role'] ?? '') === 'pj';

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
        'id'           => null,
        'status_label' => 'Belum Ada Tagihan',
        'badge_style'  => 'background-color: #cbd5e1; color: #475569; font-weight: 800;',
        'tenggat'      => '-',
        'action_label' => 'Bayar Sekarang',
        'is_actionable'=> false
    ],
    'iuran' => [
        'id'           => null,
        'status_label' => 'Belum Ada Tagihan',
        'badge_style'  => 'background-color: #cbd5e1; color: #475569; font-weight: 800;',
        'tenggat'      => '-',
        'action_label' => 'Bayar Sekarang',
        'is_actionable'=> false
    ]
];

foreach ($daftar_tagihan as $t) {
    $key = (stripos($t['jenis'], 'sewa') !== false) ? 'sewa' : 'iuran';

    $is_lunas   = ($t['status_tagihan'] === 'lunas');
    $is_pending = (($t['status_verif'] ?? '') === 'pending' || $t['status_tagihan'] === 'sebagian');
    $is_ditolak = (($t['status_verif'] ?? '') === 'ditolak');

    if ($is_lunas) {
        $status_txt    = 'Lunas';
        $badge_style   = 'background-color: #8ce3b9; color: #065f46; font-weight: 800;';
        $action_label  = 'Lunas';
        $is_actionable = false;
    } elseif ($is_pending) {
        $status_txt    = 'Menunggu Verifikasi';
        $badge_style   = 'background-color: #e0f2fe; color: #0369a1; font-weight: 800;';
        $action_label  = 'Sedang Diproses';
        $is_actionable = false;
    } elseif ($is_ditolak) {
        $status_txt    = 'Ditolak';
        $badge_style   = 'background-color: #fee2e2; color: #dc2626; font-weight: 800;';
        $action_label  = 'Upload Ulang Bukti';
        $is_actionable = true;
    } else {
        $status_txt    = 'Belum Lunas';
        $badge_style   = 'background-color: #fce1b8; color: #7c4103; font-weight: 800;';
        $action_label  = 'Upload Bukti Pembayaran';
        $is_actionable = true;
    }

    $ringkasan[$key] = [
        'id'           => (int)$t['id'],
        'status_label' => $status_txt,
        'badge_style'  => $badge_style,
        'tenggat'      => $t['jatuh_tempo'] ?? '-',
        'action_label' => $action_label,
        'is_actionable'=> $is_actionable
    ];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>

<body class="dashboard-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <!-- Ringkasan Tagihan Penghuni / PJ Dinamis -->
            <div class="penghuni-mobile-summary">
                <!-- Pembayaran Sewa -->
                <div class="penghuni-summary-card">
                    <span class="badge-tagihan-title">Pembayaran Sewa</span>
                    <div class="tagihan-info-grid">
                        <div class="tagihan-info-row">
                            <span class="info-label">Status Pembayaran</span>
                            <span class="info-colon">:</span>
                            <span class="badge-status-pill" style="<?= $ringkasan['sewa']['badge_style']; ?>">
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
                            <span class="badge-status-pill" style="<?= $ringkasan['iuran']['badge_style']; ?>">
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

                    <a href="/?page=tagihan_riwayat" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-receipt"></i></div>
                        <span class="menu-label">Tagihan &amp; Riwayat</span>
                    </a>

                    <a href="/?page=<?= $is_pj ? 'kelola_iuran' : 'monitoring_iuran'; ?>" class="menu-item-box">
                        <div class="menu-icon-wrapper">
                            <i class="fa-solid <?= $is_pj ? 'fa-wallet' : 'fa-chart-pie'; ?>"></i>
                        </div>
                        <span class="menu-label"><?= $is_pj ? 'Kelola Iuran' : 'Monitoring<br>Iuran'; ?></span>
                    </a>

                    <a href="/?page=peraturan_kost" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-book-open"></i></div>
                        <span class="menu-label">Peraturan Kos</span>
                    </a>

                    <a href="/?page=chat_penghuni" class="menu-item-box">
                        <div class="menu-icon-wrapper"><i class="fa-solid fa-comment-dots"></i></div>
                        <span class="menu-label">Chat Penghuni</span>
                    </a>

                    <?php if ($is_pj): ?>
                    <a href="/?page=verifikasi_bayar" class="menu-item-box">
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

            <div class="desktop-tagihan-row">
                <!-- Card Sewa Desktop -->
                <div class="desktop-tagihan-card">
                    <span class="tagihan-badge-white">Pembayaran Sewa</span>
                    <div class="tagihan-details">
                        <div class="tagihan-detail-row">
                            <span class="lbl">Status Pembayaran</span><span class="colon">:</span>
                            <span
                                style="<?= $ringkasan['sewa']['badge_style']; ?> padding: 2px 10px; border-radius: 999px; font-size: 11px;">
                                <?= htmlspecialchars($ringkasan['sewa']['status_label']); ?>
                            </span>
                        </div>
                        <div class="tagihan-detail-row">
                            <span class="lbl">Tenggat</span><span class="colon">:</span>
                            <span><?= htmlspecialchars($ringkasan['sewa']['tenggat']); ?></span>
                        </div>
                    </div>
                    <?php if ($ringkasan['sewa']['is_actionable'] && $ringkasan['sewa']['id']): ?>
                    <a href="/?page=tagihan_riwayat&bayar_id=<?= $ringkasan['sewa']['id']; ?>" class="btn-upload-bukti">
                        <?= htmlspecialchars($ringkasan['sewa']['action_label']); ?>
                    </a>
                    <?php else: ?>
                    <a href="/?page=tagihan_riwayat" class="btn-upload-bukti" style="opacity: 0.85;">
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
                            <span
                                style="<?= $ringkasan['iuran']['badge_style']; ?> padding: 2px 10px; border-radius: 999px; font-size: 11px;">
                                <?= htmlspecialchars($ringkasan['iuran']['status_label']); ?>
                            </span>
                        </div>
                        <div class="tagihan-detail-row">
                            <span class="lbl">Tenggat</span><span class="colon">:</span>
                            <span><?= htmlspecialchars($ringkasan['iuran']['tenggat']); ?></span>
                        </div>
                    </div>
                    <?php if ($ringkasan['iuran']['is_actionable'] && $ringkasan['iuran']['id']): ?>
                    <a href="/?page=tagihan_riwayat&bayar_id=<?= $ringkasan['iuran']['id']; ?>"
                        class="btn-upload-bukti">
                        <?= htmlspecialchars($ringkasan['iuran']['action_label']); ?>
                    </a>
                    <?php else: ?>
                    <a href="/?page=tagihan_riwayat" class="btn-upload-bukti" style="opacity: 0.85;">
                        <?= htmlspecialchars($ringkasan['iuran']['action_label']); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <h3 class="menu-utama-title">Menu utama</h3>
            <div class="desktop-menu-grid">
                <a href="/?page=tagihan_riwayat" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-receipt"></i></div>
                    <span class="desktop-menu-label">Tagihan &amp;<br>Riwayat</span>
                </a>

                <a href="/?page=peraturan_kost" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-book-open"></i></div>
                    <span class="desktop-menu-label">Peraturan<br>Kos</span>
                </a>

                <a href="/?page=chat_penghuni" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-comment-dots"></i></div>
                    <span class="desktop-menu-label">Chat<br>Penghuni</span>
                </a>

                <a href="/?page=<?= $is_pj ? 'kelola_iuran' : 'monitoring_iuran'; ?>" class="desktop-menu-card">
                    <div class="desktop-menu-icon">
                        <i class="fa-solid <?= $is_pj ? 'fa-wallet' : 'fa-chart-pie'; ?>"></i>
                    </div>
                    <span
                        class="desktop-menu-label"><?= $is_pj ? 'Kelola <br>Iuran' : 'Monitoring &amp;<br>Tagihan Iuran'; ?></span>
                </a>

                <?php if ($is_pj): ?>
                <a href="/?page=verifikasi_bayar" class="desktop-menu-card">
                    <div class="desktop-menu-icon"><i class="fa-solid fa-list-check"></i></div>
                    <span class="desktop-menu-label">Verifikasi<br>Pembayaran</span>
                </a>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>

</html>