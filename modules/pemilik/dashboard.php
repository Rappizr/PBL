<?php
// Proteksi sederhana sesi pemilik
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'pemilik') {
    // header("Location: index.php?page=login");
    // exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Dashboard Pemilik</title>
    <!-- Font Awesome CDN untuk Ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- CSS Murni Terpisah -->
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="dashboard-body">

    <div class="mobile-container">
        <!-- Header: Judul & Badge Role -->
        <header class="dashboard-header">
            <h1 class="brand-title-dashboard">SITEMAN - KOS</h1>
            <span class="badge-role">Bapak Kos</span>
        </header>

        <!-- Kartu Menu Utama -->
        <main class="menu-card-container">
            <h2 class="menu-heading">Silahkan pilih menu</h2>

            <div class="menu-grid">
                <!-- 1. Manajemen Kamar & Penghuni -->
                <a href="index.php?page=kamar-penghuni" class="menu-item-box">
                    <div class="menu-icon-wrapper">
                        <i class="fa-solid fa-warehouse"></i>
                    </div>
                    <span class="menu-label">Manajemen<br>Kamar & Penghuni</span>
                </a>

                <!-- 2. Analisis Keuangan -->
                <a href="index.php?page=analisis-keuangan" class="menu-item-box">
                    <div class="menu-icon-wrapper">
                        <i class="fa-solid fa-chart-simple"></i>
                    </div>
                    <span class="menu-label">Analisis<br>Keuangan</span>
                </a>

                <!-- 3. Verifikasi Pembayaran -->
                <a href="index.php?page=verifikasi-pembayaran" class="menu-item-box">
                    <div class="menu-icon-wrapper">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <span class="menu-label">Verifikasi<br>Pembayaran</span>
                </a>

                <!-- 4. Chat Penghuni -->
                <a href="index.php?page=chat-penghuni" class="menu-item-box">
                    <div class="menu-icon-wrapper">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <span class="menu-label">Chat Penghuni</span>
                </a>

                <!-- 5. Keluhan Penghuni -->
                <a href="index.php?page=keluhan-penghuni" class="menu-item-box">
                    <div class="menu-icon-wrapper">
                        <i class="fa-solid fa-inbox"></i>
                    </div>
                    <span class="menu-label">Keluhan Penghuni</span>
                </a>
            </div>
        </main>
    </div>

</body>
</html>