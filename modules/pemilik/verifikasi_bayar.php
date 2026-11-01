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

// FILTER RENTANG TANGGAL (format: YYYY-MM-DD to YYYY-MM-DD)
$date_range = trim($_GET['date_range'] ?? '');

// Abaikan kalau formatnya tidak valid
if (!preg_match('/^\d{4}-\d{2}-\d{2}( to \d{4}-\d{2}-\d{2})?$/', $date_range)) {
    $date_range = '';
}

$tgl_mulai   = '';
$tgl_selesai = '';

if ($date_range !== '') {
    $parts       = explode(' to ', $date_range);
    $tgl_mulai   = trim($parts[0]);
    $tgl_selesai = trim($parts[1] ?? $tgl_mulai);
}

// Proses filter array berdasarkan tanggal transaksi
$daftar_verifikasi = array_filter($daftar_verifikasi, function ($v) use ($tgl_mulai, $tgl_selesai) {
    if ($tgl_mulai === '') {
        return true;
    }
    // Mengambil substring tanggal YYYY-MM-DD dari string 'Transfer ~ YYYY-MM-DD HH:MM'
    preg_match('/\d{4}-\d{2}-\d{2}/', $v['tanggal'], $matches);
    $tgl_v = $matches[0] ?? '';
    if ($tgl_v === '') {
        return false;
    }
    return ($tgl_v >= $tgl_mulai && $tgl_v <= $tgl_selesai);
});

$is_filtered = ($date_range !== '');
$current_page = $_GET['page'] ?? 'verifikasi_pembayaran';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Verifikasi Pembayaran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Flatpickr CSS untuk kalender range -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

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

                <!-- Filter tanggal mobile -->
                <form method="GET" action="" class="filter-range-form" style="margin-bottom: 20px;">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                    <div class="flatpickr-box-wrapper">
                        <i class="fa-solid fa-filter date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                               placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                            <a href="/?page=<?= urlencode($current_page); ?>" class="btn-reset-range" title="Reset">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="card-list">
                    <?php if (empty($daftar_verifikasi)): ?>
                        <div class="empty-state-card" style="width: 100%;">
                            <i class="fa-solid fa-folder-open"></i>
                            <span>Tidak ada data verifikasi pada rentang tanggal yang dipilih</span>
                        </div>
                    <?php else: ?>
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
                    <?php endif; ?>
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
            <p class="desktop-header-subtitle" style="margin-bottom: 16px;">Daftar konfirmasi bukti transfer dari penghuni kos</p>

            <!-- Filter tanggal desktop -->
            <form method="GET" action="" class="filter-range-form-desktop" style="max-width: 250px; width: 100%;">
                <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                <div class="flatpickr-box-wrapper" style="width: 100%;">
                    <i class="fa-solid fa-filter date-icon"></i>
                    <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                           placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly style="width: 100%;">
                    <?php if ($is_filtered): ?>
                        <a href="/?page=<?= urlencode($current_page); ?>" class="btn-reset-range" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Berikan margin-top di sini supaya card-nya turun dan ada space -->
            <div class="card-list" style="max-width: 650px; margin-top: 28px;">
                <?php if (empty($daftar_verifikasi)): ?>
                    <div class="empty-state-card" style="width: 100%;">
                        <i class="fa-solid fa-folder-open"></i>
                        <span>Tidak ada data verifikasi pada rentang tanggal yang dipilih</span>
                    </div>
                <?php else: ?>
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
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Script Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.querySelectorAll('.flatpickr-range-input').forEach(function(input) {
            flatpickr(input, {
                mode: "range",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d/m/Y",
                onClose: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        instance.element.closest('form').submit();
                    }
                }
            });
        });
    </script>
</body>
</html>