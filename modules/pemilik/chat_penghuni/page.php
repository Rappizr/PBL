<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nama_penghuni = "Mas Rafi";
$is_pj = true;

// Data dummy daftar anak kos
$penghuniKos = [
    ['nama' => 'Ridwan', 'kamar' => '01', 'no_wa' => '082764518892'],
    ['nama' => 'Raffi',  'kamar' => '05', 'no_wa' => '082765678892'],
    ['nama' => 'Rofiq',  'kamar' => '03', 'no_wa' => '082094518892'],
    ['nama' => 'Omara',  'kamar' => '04', 'no_wa' => '082764533892'],
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
            <?php 
            $header_path = __DIR__ . '/../../components/header.php';
            if (file_exists($header_path)) {
                require_once $header_path;
            } 
            ?>

            <main class="chat-container">
                <div class="kamar-title-card" style="margin-bottom: 0;">
                    <h2>Chat Penghuni</h2>
                    <a href="/?page=dashboard_pemilik" class="btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="search-wrapper">
                    <i class="fa-solid fa-filter"></i>
                    <input type="text" id="searchInputMobile" class="search-input" placeholder="Cari Nama / Kamar">
                </div>

                <div class="chat-list" id="chatListMobile">
                    <?php foreach ($penghuniKos as $p): ?>
                        <div class="chat-card" data-nama="<?= strtolower($p['nama']); ?>" data-kamar="<?= strtolower($p['kamar']); ?>">
                            <div class="chat-info">
                                <div class="chat-header-row">
                                    <span class="chat-nama"><?= htmlspecialchars($p['nama']); ?></span>
                                    <span class="badge-kamar-putih">Kamar <?= htmlspecialchars($p['kamar']); ?></span>
                                </div>
                                <span class="chat-wa"><?= htmlspecialchars($p['no_wa']); ?></span>
                            </div>
                            <a href="https://wa.me/<?= preg_replace('/^0/', '62', $p['no_wa']); ?>" target="_blank" class="btn-wa-chat">
                                <i class="fa-brands fa-whatsapp"></i> Chat
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>

        <?php 
        $footer_path = __DIR__ . '/../../components/footer.php';
        if (file_exists($footer_path)) {
            require_once $footer_path;
        } 
        ?>
    </div>


    <!-- ==========================================
         LAYOUT DESKTOP (FIGMA MATCHING)
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
                    <a href="/?page=dashboard_pemilik" class="nav-item">
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
                    <a href="/?page=kelola_iuran" class="nav-item">
                        <i class="fa-solid fa-wallet"></i>
                        <span>Kelola &amp; Monitor Iuran</span>
                    </a>
                </nav>
            </div>

            <div class="sidebar-footer">
                <p class="sidebar-footer-title">SITEMAN - KOS &copy; 2g2b - 2026</p>
                <p class="sidebar-footer-desc">Sistem Manajemen Operasion Kos</p>
            </div>
        </aside>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Chat Penghuni</h2>
            <p class="peraturan-desktop-subtitle">Cari nama/kamar penghuni lalu hubungi</p>

            <h3 class="desktop-section-title">Data Penghuni kos</h3>

            <!-- Search Bar Desktop -->
            <div class="desktop-chat-search">
                <i class="fa-solid fa-filter search-icon"></i>
                <input type="text" id="searchInputDesktop" placeholder="Cari Nama / Kamar">
            </div>

            <!-- List Desktop 1 Kolom Auto Layout -->
            <div class="desktop-chat-list" id="chatListDesktop">
                <?php foreach ($penghuniKos as $p): ?>
                    <article class="desktop-chat-card" data-nama="<?= strtolower($p['nama']); ?>" data-kamar="<?= strtolower($p['kamar']); ?>">
                        <div class="desktop-chat-info">
                            <div class="desktop-chat-header-row">
                                <span class="desktop-chat-nama"><?= htmlspecialchars($p['nama']); ?></span>
                                <span class="desktop-badge-kamar">Kamar <?= htmlspecialchars($p['kamar']); ?></span>
                            </div>
                            <span class="desktop-chat-telp"><?= htmlspecialchars($p['no_wa']); ?></span>
                        </div>
                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', $p['no_wa']); ?>" target="_blank" class="desktop-btn-wa">
                            <i class="fa-brands fa-whatsapp"></i> Chat
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

   <!-- Script Filter Pencarian Realtime (Mobile & Desktop) -->
    <script>
        function setupFilter(inputId, listId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            input.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                const container = document.getElementById(listId);
                if (!container) return;

                const cards = container.querySelectorAll('.chat-card, .desktop-chat-card');

                cards.forEach(card => {
                    const nama = card.getAttribute('data-nama') || '';
                    const kamar = card.getAttribute('data-kamar') || '';

                    // Jika query kosong, tampilkan kembali. Jika cocok, tampilkan, jika tidak sembunyikan.
                    if (query === '' || nama.includes(query) || kamar.includes(query)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }

        // Jalankan untuk kedua input (Mobile & Desktop)
        setupFilter('searchInputMobile', 'chatListMobile');
        setupFilter('searchInputDesktop', 'chatListDesktop');
    </script>
</body>

</html>