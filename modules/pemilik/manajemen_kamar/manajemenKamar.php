<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'pemilik') {
    header("Location: ../../public/index.php?page=login&role=pemilik");
    exit;
}

// data dummy Kamar
$daftar_kamar = [
    ['nomor' => '01', 'penghuni'=> 'Ridwan', 'status' => 'lunas'],
    ['nomor' => '02', 'penghuni'=> 'Rafi', 'status' => 'lunas'],
    ['nomor' => '03', 'penghuni'=> 'Bahlil', 'status' => 'belum'],
    ['nomor' => '04', 'penghuni'=> 'Belum terisi', 'status' => 'kosong'],
    ['nomor' => '05', 'penghuni'=> 'Joko', 'status' => 'lunas'],
    ['nomor' => '06', 'penghuni'=> 'Bowo', 'status' => 'lunas'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | manajemen Kamar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="kamar-body">
    <div class="mobile-container">

    <div class="kamar-page-header">
        <h1 class="brand-title-dashboard">SITEMAN - KOS</h1>
        <div class="kotak-kapsul-pemilik">
            <span class="lingkaran-user-abu">
                <i class="fa-solid fa-user"></i>
            </span>
            <span class="tulisan-nama">Bapak Kos</span>
        </div>
    </div>

    <div class ="kamar-title-card">
        <h2>Manajemen Kamar &amp; Penghuni</h2>
        <a href="/?page=dashboard_pemilik" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

     <div class="kamar-actions">
        <a href="#" class="btn-add-outline">
            <span class="plus-icon">+</span> Tambah Kamar
        </a>
        <a href="#" class="btn-add-outline">
            <span class="plus-icon">+</span> Tambah Penghuni
        </a>
    </div>

     <div class="kamar-grid">
        <?php foreach ($daftar_kamar as $kamar): ?>
            <div class="kamar-card <?= $kamar['status'] === 'kosong' ? 'is-kosong' : '' ?>">
                <?php if ($kamar['status'] === 'lunas'): ?>
                    <span class="badge-status badge-lunas">Lunas</span>
                <?php elseif ($kamar['status'] === 'belum'): ?>
                    <span class="badge-status badge-belum">Belum</span>
                <?php else: ?>
                    <span class="badge-status badge-kosong">Kosong</span>
                <?php endif; ?>

                <div class="kamar-nomor"><?= htmlspecialchars($kamar['nomor']) ?></div>
                <div class="kamar-nama"><?= htmlspecialchars($kamar['penghuni']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>

 <?php require_once __DIR__ . '/../../components/footer.php'; ?>

</div>

</body>
</html>