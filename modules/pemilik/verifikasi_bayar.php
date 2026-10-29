<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$daftar_verifikasi = [
    [
        'id'       => 1,
        'nama'     => 'Ridwan',
        'kamar'    => 'Kamar 2',
        'nominal'  => 'Rp. 75.000',
        'tanggal'  => 'Transfer ~ 2026-09-07 08:00',
        'no_wa'    => '082764518892',
        'foto'     => 'https://picsum.photos/200'
    ],
    [
        'id'       => 2,
        'nama'     => 'Raffi',
        'kamar'    => 'Kamar 5',
        'nominal'  => 'Rp. 75.000',
        'tanggal'  => 'Transfer ~ 2026-10-17 18:00',
        'no_wa'    => '082765678892',
        'foto'     => 'https://picsum.photos/201'
    ],
    [
        'id'       => 3,
        'nama'     => 'Rofiq',
        'kamar'    => 'Kamar 3',
        'nominal'  => 'Rp. 75.000',
        'tanggal'  => 'Transfer ~ 2026-07-05 06:00',
        'no_wa'    => '082094518892',
        'foto'     => 'https://picsum.photos/202'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Verifikasi Pembayaran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="verifikasi-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <main class="verifikasi-container">
                <div class="banner-card">
                    <h2 class="banner-title">Verifikasi Pembayaran</h2>
                    <a href="/?page=dashboard_pemilik" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card-list">
                    <?php foreach ($daftar_verifikasi as $v): ?>
                        <div class="verifikasi-card">
                            <div class="verifikasi-card-body">
                                <div class="bukti-wrapper">
                                    <img src="<?= $v['foto']; ?>" alt="Bukti Transfer" class="bukti-img">
                                    <a href="<?= $v['foto']; ?>" target="_blank" class="btn-zoom">
                                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                                    </a>
                                </div>
                                <div class="detail-wrapper">
                                    <div class="user-info-row">
                                        <span class="nama-penghuni"><?= htmlspecialchars($v['nama']); ?></span>
                                        <span class="badge-kamar"><?= htmlspecialchars($v['kamar']); ?></span>
                                    </div>
                                    <div class="nominal-text"><?= htmlspecialchars($v['nominal']); ?></div>
                                    <div class="metode-tanggal"><?= htmlspecialchars($v['tanggal']); ?></div>
                                </div>
                            </div>
                            <div class="verifikasi-card-footer">
                                <a href="https://wa.me/<?= preg_replace('/^0/', '62', $v['no_wa']); ?>" target="_blank" class="btn-action btn-chat">
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

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Verifikasi Pembayaran</h2>
            <p class="desktop-header-subtitle">Daftar konfirmasi bukti transfer dari penghuni kos</p>

            <div class="card-list" style="max-width: 650px;">
                <?php foreach ($daftar_verifikasi as $v): ?>
                    <div class="verifikasi-card">
                        <div class="verifikasi-card-body">
                            <div class="bukti-wrapper">
                                <img src="<?= $v['foto']; ?>" alt="Bukti Transfer" class="bukti-img">
                                <a href="<?= $v['foto']; ?>" target="_blank" class="btn-zoom">
                                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                                </a>
                            </div>
                            <div class="detail-wrapper">
                                <div class="user-info-row">
                                    <span class="nama-penghuni"><?= htmlspecialchars($v['nama']); ?></span>
                                    <span class="badge-kamar"><?= htmlspecialchars($v['kamar']); ?></span>
                                </div>
                                <div class="nominal-text"><?= htmlspecialchars($v['nominal']); ?></div>
                                <div class="metode-tanggal"><?= htmlspecialchars($v['tanggal']); ?></div>
                            </div>
                        </div>
                        <div class="verifikasi-card-footer">
                            <a href="https://wa.me/<?= preg_replace('/^0/', '62', $v['no_wa']); ?>" target="_blank" class="btn-action btn-chat">
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

</body>
</html>