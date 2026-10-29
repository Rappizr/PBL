<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$peraturan_list = [
    [
        'icon' => 'fa-solid fa-smoking',
        'judul' => 'Aturan Merokok & Keamanan',
        'deskripsi' => 'Bagi yang merokok, buanglah puntung dan abu rokok di asbak. Pastikan api rokok sudah mati sebelum dibuang demi mencegah risiko kebakaran.'
    ],
    [
        'icon' => 'fa-solid fa-broom',
        'judul' => 'Kebersihan Kamar & Kebersamaan',
        'deskripsi' => 'Jika menyapu kamar, kotoran jangan dibiarkan di depan pintu. Buanglah sampah pada tempat sampah yang telah disediakan dengan cara yang benar.'
    ],
    [
        'icon' => 'fa-solid fa-utensils',
        'judul' => 'Dapur & Peralatan Makan',
        'deskripsi' => 'Selesai makan atau memasak, harap segera mencuci piring, panci, dan peralatan yang digunakan. Pastikan wastafel dalam kondisi bersih dan kembalikan alat pada tempatnya.'
    ],
    [
        'icon' => 'fa-solid fa-car-side',
        'judul' => 'Parkir & Kunjungan Tamu',
        'deskripsi' => 'Bagi tamu yang berkunjung di malam hari, mohon kendaraan diparkir di luar (depan rumah / lantai bawah).'
    ],
    [
        'icon' => 'fa-solid fa-clock',
        'judul' => 'Jam Malam & Keamanan Pagar',
        'deskripsi' => 'Setiap malam mulai pukul 22:00, pastikan pintu pagar dalam keadaan terkunci atau digembok.'
    ],
    [
        'icon' => 'fa-solid fa-volume-xmark',
        'judul' => 'Ketenangan & Penggunaan Suara',
        'deskripsi' => 'Jangan menyalakan sound system terlalu keras/bising (cukup untuk dinikmati sendiri). Hindari bergurau keras pada waktu istirahat agar tidak mengganggu rekan yang sedang istirahat atau belajar.'
    ],
    [
        'icon' => 'fa-solid fa-shirt',
        'judul' => 'Jemuran Pakaian',
        'deskripsi' => 'Jika pakaian yang dijemur sudah kering, harap segera diambil dan disimpan di kamar masing-masing agar tempat jemuran dapat digunakan oleh penghuni lain.'
    ],
    [
        'icon' => 'fa-solid fa-receipt',
        'judul' => 'Pembayaran Sewa & Iuran',
        'deskripsi' => 'Sewa dan iuran dibayarkan paling lambat tanggal 10 setiap bulan. Keterlambatan lebih dari 7 hari dikenakan denda sesuai kesepakatan awal.'
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Peraturan Kos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="dashboard-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <main class="page-content">
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title">Peraturan Kos</h2>
                    <a href="/?page=dashboard_penghuni" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="peraturan-card-wrapper">
                    <?php foreach ($peraturan_list as $rule): ?>
                        <div class="peraturan-item">
                            <div class="peraturan-icon-col">
                                <i class="<?= $rule['icon'] ?>"></i>
                            </div>
                            <div class="peraturan-content">
                                <h3 class="peraturan-title"><?= htmlspecialchars($rule['judul']) ?></h3>
                                <p class="peraturan-desc"><?= htmlspecialchars($rule['deskripsi']) ?></p>
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
            <h2 class="desktop-header-title">Peraturan Kos</h2>
            <p class="peraturan-desktop-subtitle">Tata tertib yang berlaku untuk seluruh penghuni kos</p>

            <div class="desktop-rules-container">
                <?php foreach ($peraturan_list as $p): ?>
                    <div class="rule-item">
                        <div class="rule-icon-box">
                            <i class="<?= htmlspecialchars($p['icon']) ?>"></i>
                        </div>
                        <div class="rule-text-box">
                            <h4 class="rule-title"><?= htmlspecialchars($p['judul']) ?></h4>
                            <p class="rule-desc"><?= htmlspecialchars($p['deskripsi']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

</body>
</html>