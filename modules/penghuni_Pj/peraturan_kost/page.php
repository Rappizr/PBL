<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nama penghuni dari session atau dummy
// $nama_penghuni = $_SESSION['user']['nama'];
$nama_penghuni = "Mas Rafi";

// Data Peraturan Kos sesuai Mockup
$peraturan_list = [
    [
        'icon' => 'fa-regular fa-clock',
        'judul' => 'Jam Malam & Keamanan',
        'deskripsi' => 'Pintu gerbang ditutup pukul 22.00 WIB. Penghuni yang pulang lewat jam tersebut wajib menghubungi pemilik kos untuk dibukakan pintu.'
    ],
    [
        'icon' => 'fa-solid fa-user-group',
        'judul' => 'Tamu & Kunjungan',
        'deskripsi' => 'Tamu diperbolehkan berkunjung hingga pukul 20.00 WIB. Tamu menginap harus seizin pemilik kos dan dikenakan biaya tambahan.'
    ],
    [
        'icon' => 'fa-solid fa-receipt',
        'judul' => 'Pembayaran',
        'deskripsi' => 'Sewa dan iuran dibayarkan paling lambat tanggal 10 setiap bulan. Keterlambatan lebih dari 7 hari dikenakan denda sesuai kesepakatan awal.'
    ],
    [
        'icon' => 'fa-solid fa-broom',
        'judul' => 'Kebersihan dan Fasilitas',
        'deskripsi' => 'Setiap penghuni wajib menjaga kebersihan kamar dan area kos bersama. Kerusakan fasilitas akibat kelalaian pribadi menjadi tanggung jawab penghuni.'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Peraturan Kos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>

<body class="dashboard-body">
    <div class="mobile-container">
        <div>
            <!-- Header Top Bar -->
            <header class="dashboard-header">
                <h1 class="brand-title-dashboard">SITEMAN - KOS</h1>
                <div class="kotak-kapsul-pemilik">
                    <span class="lingkaran-user-abu">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <span class="tulisan-nama"><?= htmlspecialchars($nama_penghuni); ?></span>
                </div>
            </header>

            <main class="page-content">
                <!-- Banner Peraturan Kos + Tombol Kembali -->
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title">Peraturan Kos</h2>
                    <a href="/dashboard/penghuni" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- Kartu Putih Peraturan Kos -->
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

        <!-- Footer Modular -->
        <?php require_once __DIR__ . '/../../components/footer.php'; ?>
    </div>
</body>
</html>