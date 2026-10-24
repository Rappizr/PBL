<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nama penghuni dari session atau dummy
$nama_penghuni = "Mas Rafi";
$is_pj = $_SESSION['user']['is_pj'] ?? false;

// Data Kontak Penghuni Kos
$daftar_penghuni = [
    [
        'nama' => 'Ridwan',
        'kamar' => 'Kamar 01',
        'telepon' => '082764518892',
        'wa' => '6282764518892'
    ],
    [
        'nama' => 'Raffi',
        'kamar' => 'Kamar 05',
        'telepon' => '082765678892',
        'wa' => '6282765678892'
    ],
    [
        'nama' => 'Rofiq',
        'kamar' => 'Kamar 03',
        'telepon' => '082094518892',
        'wa' => '6282094518892'
    ],
    [
        'nama' => 'Omara',
        'kamar' => 'Kamar 04',
        'telepon' => '082764533892',
        'wa' => '6282764533892'
    ],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Chat Penghuni</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>

<body class="dashboard-body">

    <!-- ==========================================
         LAYOUT MOBILE
         ========================================== -->
    <div class="mobile-container">
        <div>
            <!-- Header Top Bar Mobile -->
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
                <!-- Banner Chat Penghuni -->
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title">Chat Penghuni</h2>
                    <a href="/?page=dashboard_penghuni" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- Input Search / Filter Nama atau Kamar -->
                <div class="chat-search-box">
                    <i class="fa-solid fa-filter icon-filter"></i>
                    <input type="text" id="mobileSearchInput" placeholder="Cari Nama / Kamar" onkeyup="filterKontakMobile()">
                </div>

                <!-- Daftar Kartu Kontak Penghuni (Mobile) -->
                <div class="chat-list-wrapper" id="mobileChatList">
                    <?php foreach ($daftar_penghuni as $penghuni): ?>
                        <div class="chat-card-item">
                            <div class="chat-card-info">
                                <div class="chat-card-header">
                                    <span class="chat-nama"><?= htmlspecialchars($penghuni['nama']) ?></span>
                                    <span class="badge-kamar"><?= htmlspecialchars($penghuni['kamar']) ?></span>
                                </div>
                                <span class="chat-telp"><?= htmlspecialchars($penghuni['telepon']) ?></span>
                            </div>
                            <a href="https://wa.me/<?= $penghuni['wa'] ?>" target="_blank" class="btn-chat-wa">
                                <i class="fa-brands fa-whatsapp"></i> Chat
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>

        <!-- Footer Modular Mobile -->
        <?php if (file_exists(__DIR__ . '/../../components/footer.php')) require_once __DIR__ . '/../../components/footer.php'; ?>
    </div>

    <!-- ==========================================
         LAYOUT DESKTOP
         ========================================== -->
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
                    <a href="/?page=peraturan_kost" class="nav-item">
                        <i class="fa-solid fa-book-open"></i>
                        <span>Peraturan Kos</span>
                    </a>
                    <a href="/?page=chat_penghuni" class="nav-item active">
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
                <p class="sidebar-footer-desc">Sistem Manajemen Operasional Kos</p>
            </div>
        </aside>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Chat Penghuni</h2>
            <p class="peraturan-desktop-subtitle">Hubungi langsung sesama penghuni kos via WhatsApp</p>

            <!-- Search Bar Desktop -->
            <div class="desktop-chat-search">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="desktopSearchInput" placeholder="Cari nama penghuni atau nomor kamar..." onkeyup="filterKontakDesktop()">
            </div>

            <!-- Grid Kontak Desktop -->
            <div class="desktop-chat-grid" id="desktopChatGrid">
                <?php foreach ($daftar_penghuni as $penghuni): ?>
                    <article class="desktop-chat-card">
                        <div class="desktop-chat-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="desktop-chat-details">
                            <div class="desktop-chat-header">
                                <h4 class="desktop-chat-name"><?= htmlspecialchars($penghuni['nama']) ?></h4>
                                <span class="badge-kamar"><?= htmlspecialchars($penghuni['kamar']) ?></span>
                            </div>
                            <p class="desktop-chat-phone"><?= htmlspecialchars($penghuni['telepon']) ?></p>
                        </div>
                        <a href="https://wa.me/<?= $penghuni['wa'] ?>" target="_blank" class="btn-chat-wa">
                            <i class="fa-brands fa-whatsapp"></i> Chat WhatsApp
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Script Sederhana untuk Pencarian Real-time -->
    <script>
        function filterKontakMobile() {
            let input = document.getElementById('mobileSearchInput').value.toLowerCase();
            let cards = document.querySelectorAll('#mobileChatList .chat-card-item');
            cards.forEach(card => {
                let text = card.innerText.toLowerCase();
                card.style.display = text.includes(input) ? 'flex' : 'none';
            });
        }

        function filterKontakDesktop() {
            let input = document.getElementById('desktopSearchInput').value.toLowerCase();
            let cards = document.querySelectorAll('#desktopChatGrid .desktop-chat-card');
            cards.forEach(card => {
                let text = card.innerText.toLowerCase();
                card.style.display = text.includes(input) ? 'flex' : 'none';
            });
        }
    </script>
</body>
</html>