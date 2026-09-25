<?php

require_once __DIR__ . '/../../config/database.php';

// Hitung pembayaran yang masih menunggu verifikasi
$stmtPendingPembayaran = $pdo->query("
    SELECT COUNT(*) AS total
    FROM pembayaran
    WHERE status_verifikasi = 'pending'
");
$pendingPembayaranCount = (int) $stmtPendingPembayaran->fetchColumn();

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
    <div class="mobile-container">
        <header class="dashboard-header">
            <h1 class="brand-title-dashboard">SITEMAN - KOS</h1>
            <div class="kotak-kapsul-pemilik">
                <span class="lingkaran-user-abu">
                    <i class="fa-solid fa-user"></i>
                </span>
                <span class="tulisan-bapak-kos">Bapak Kos</span>
            </div>
        </header>

        <main class="menu-card-container">
            <h2 class="menu-heading">Silahkan pilih menu</h2>

            <div class="menu-grid">
                <a href="/?page=kamar-penghuni" class="menu-item-box">
                    <div class="menu-icon-wrapper">
                        <i class="fa-solid fa-warehouse"></i>
                    </div>
                    <span class="menu-label">Manajemen<br>Kamar & Penghuni</span>
                </a>

                <a href="/?page=analisis-keuangan" class="menu-item-box">
                    <div class="menu-icon-wrapper">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <span class="menu-label">Buku Kas<br>Sewa</span>
                </a>

                <a href="/?page=verifikasi-pembayaran" class="menu-item-box">
                    <div class="menu-icon-wrapper">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <span class="menu-label">Verifikasi<br>Pembayaran</span>
                    <?php if ($pendingPembayaranCount > 0): ?>
                        <span class="badge-notif">
                            <i class="fa-solid fa-bell"></i>
                            <?= $pendingPembayaranCount ?> Notifikasi
                        </span>
                    <?php endif; ?>
                </a>

                <a href="#" class="menu-item-box">
                    <div class="menu-icon-wrapper">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <span class="menu-label">Monitoring &<br>Tagihan Iuran</span>
                </a>

                <a href="/?page=chat-penghuni" class="menu-item-box">
                    <div class="menu-icon-wrapper">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <span class="menu-label">Chat Penghuni</span>
                </a>
            </div>
        </main>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>
</body>
</html>