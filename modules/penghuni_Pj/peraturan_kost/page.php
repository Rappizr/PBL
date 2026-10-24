<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nama penghuni dari session atau dummy
// $nama_penghuni = $_SESSION['user']['nama'];
$nama_penghuni = "Mas Rafi";
$is_pj = $_SESSION['user']['is_pj'] ?? false; 
// Inisialisasi variabel $is_pj agar tidak Undefined Variable Error

// Data Peraturan Kos sesuai Mockup
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

    <!-- LAYOUT DESKTOP -->
<div class="desktop-dashboard-wrapper">
    <aside class="desktop-sidebar">
        <div>
            <h1 class="sidebar-brand">SITEMAN - KOS</h1>

            <div class="sidebar-profile">
                <div class="sidebar-avatar"><i class="fa-solid fa-user"></i></div>
                <span class="sidebar-name"><?= htmlspecialchars($nama_penghuni); ?></span>
            </div>

            <nav class="sidebar-nav">
                <a href="/?page=dashboard_penghuni" class="nav-item">
                    <i class="fa-solid fa-table-cells-large"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/?page=tagihan_riwayat" class="nav-item">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Tagihan &amp; Riwayat</span>
                </a>
                <a href="/?page=monitoring_iuran" class="nav-item">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Monitoring &amp; Tagihan Iuran</span>
                </a>
                <a href="/?page=peraturan_kost" class="nav-item active">
                    <i class="fa-solid fa-book-open"></i>
                    <span>Peraturan Kos</span>
                </a>
                <a href="/?page=chat_penghuni" class="nav-item">
                    <i class="fa-solid fa-comments"></i>
                    <span>Chat Penghuni</span>
                </a>
                <?php if ($is_pj): ?>
                    <a href="/?page=kelola_iuran" class="nav-item">
                        <i class="fa-solid fa-wallet"></i>
                        <span>Kelola &amp; Monitor Iuran</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="sidebar-footer">
            <p class="sidebar-footer-title">SITEMAN - KOS &copy; 2g2b - 2026</p>
            <p class="sidebar-footer-desc">Sistem Manajemen Operasion Kos</p>
        </div>
    </aside>

    <main class="desktop-content">
        <h2 class="desktop-header-title">Peraturan Kos</h2>
        <p class="peraturan-desktop-subtitle">Tata tertib yang berlaku untuk seluruh penghuni kos</p>

        <!-- Container Utama Putih 1 Card Besar Bergaris Pembatas (Figma Style) -->
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