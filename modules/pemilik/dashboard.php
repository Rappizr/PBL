<?php
// Optional auth guard (uncomment when ready)
// if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'pemilik') {
//     $_SESSION['login_error'] = 'Anda harus masuk sebagai pemilik!';
//     header('Location: /?page=login&role=pemilik');
//     exit;
// }
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
            <span class="badge-role">Bapak Kos</span>
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
                    <span class="badge-notif">
                        <i class="fa-solid fa-bell"></i> 3 Notifikasi
                    </span>
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
