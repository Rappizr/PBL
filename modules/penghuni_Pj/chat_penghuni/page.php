<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nama penghuni dari session atau dummy
// $nama_penghuni = $_SESSION['user']['nama'];
$nama_penghuni = "Mas Rafi";

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Chat Penghuni</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="flex w-[23.125rem] h-[55.75rem] py-11 px-6 flex-col justify-between items-center shrink-0">
    <div class="flex flex-col items-start gap-11 self-stretch">
        
    </div>
    <?php require_once __DIR__ . '/../../components/footer.php'; ?>

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