<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role_user    =$_SESSION['user']['role'] ?? 'penghuni';
$link_kembali = ($role_user === 'pemilik') ? '/?page=dashboard_pemilik' : '/?page=dashboard_penghuni';

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
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="dashboard-body">

<!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <main class="chat-container">
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title">Chat Penghuni</h2>
                    <a href="<?= $link_kembali; ?>" class="btn-kembali-banner">
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
                            <a href="https://wa.me/<?= preg_replace('/^0/', '62', $p['no_wa']); ?>" target="_blank" class="btn-chat-wa">
                                <i class="fa-brands fa-whatsapp"></i> Chat
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Chat Penghuni</h2>
            <p class="peraturan-desktop-subtitle">Cari nama/kamar penghuni lalu hubungi</p>

            <h3 class="desktop-section-title">Data Penghuni kos</h3>

            <div class="desktop-chat-search">
                <i class="fa-solid fa-filter search-icon"></i>
                <input type="text" id="searchInputDesktop" placeholder="Cari Nama / Kamar">
            </div>

            <div class="desktop-chat-list" id="chatListDesktop">
                <?php foreach ($penghuniKos as$p): ?>
                    <article class="desktop-chat-card" data-nama="<?= strtolower($p['nama']); ?>" data-kamar="<?= strtolower($p['kamar']); ?>">
                        <div class="desktop-chat-info">
                            <div class="desktop-chat-header-row">
                                <span class="desktop-chat-nama"><?= htmlspecialchars($p['nama']); ?></span>
                                <span class="desktop-badge-kamar">Kamar <?= htmlspecialchars($p['kamar']); ?></span>
                            </div>
                            <span class="desktop-chat-telp"><?= htmlspecialchars($p['no_wa']); ?></span>
                        </div>
                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', $p['no_wa']); ?>" target="_blank" class="btn-chat-wa">
                            <i class="fa-brands fa-whatsapp"></i> Chat
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

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
                    card.style.display = (query === '' || nama.includes(query) || kamar.includes(query)) ? 'flex' : 'none';
                });
            });
        }
        setupFilter('searchInputMobile', 'chatListMobile');
        setupFilter('searchInputDesktop', 'chatListDesktop');
    </script>
</body>
</html>