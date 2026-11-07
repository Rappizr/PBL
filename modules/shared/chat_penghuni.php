<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Ambil koneksi database secara aman
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $possible_paths = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../config/database.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/database.php',
        dirname(__DIR__, 2) . '/config/database.php',
        dirname(__DIR__, 3) . '/config/database.php'
    ];
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

// 2. Ambil data langsung dari tabel penghuni (Meskipun belum masuk penghuni_kamar, TETAP TAMPIL)
$penghuniKos = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {

$sql = "
    SELECT
        p.id,
        p.nama,
        COALESCE(k.no_kamar, '-') AS kamar,
        COALESCE(p.no_wa, '-') AS no_wa
    FROM penghuni p
    LEFT JOIN penghuni_kamar pk
        ON p.id = pk.penghuni_id
    LEFT JOIN kamar k
        ON pk.kamar_id = k.id
        AND k.status_kamar = 'terisi'
    WHERE LOWER(p.status_akun) = 'aktif'
    ORDER BY k.no_kamar ASC, p.nama ASC
";

$stmt = $pdo->query($sql);
$penghuniKos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $penghuniKos = [];
    }
}

$role_user    = $_SESSION['user']['role'] ?? 'pemilik';
$link_kembali = ($role_user === 'pemilik') ? '/?page=dashboard_pemilik' : '/?page=dashboard_penghuni';
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
            <?php 
                if (file_exists(__DIR__ . '/../components/header.php')) {
                    require_once __DIR__ . '/../components/header.php'; 
                }
            ?>

            <main class="chat-container">
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title">Chat Penghuni</h2>
                    <a href="<?= htmlspecialchars($link_kembali); ?>" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="search-wrapper">
                    <i class="fa-solid fa-filter"></i>
                    <input type="text" id="searchInputMobile" class="search-input" placeholder="Cari Nama / Kamar">
                </div>

                <div class="chat-list" id="chatListMobile">
                    <?php if (empty($penghuniKos)): ?>
                        <div class="empty-state-card" style="width: 100%; text-align: center; padding: 20px; background: #fff; border-radius: 12px; margin-top: 10px;">
                            <i class="fa-solid fa-folder-open" style="font-size: 24px; color: #4ca5b0; margin-bottom: 8px;"></i>
                            <p style="margin: 0; font-size: 13px; font-weight: 600; color: #4a5568;">Tidak ada data penghuni</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($penghuniKos as $p): ?>
                            <div class="chat-card" data-nama="<?= strtolower(htmlspecialchars($p['nama'])); ?>" data-kamar="<?= strtolower(htmlspecialchars($p['kamar'])); ?>">
                                <div class="chat-info">
                                    <div class="chat-header-row">
                                       <span class="chat-nama">
    <?= htmlspecialchars($p['nama']); ?>
</span>

<div class="badge-kamar-putih"
<span class="badge-kamar-putih">Kamar <?= htmlspecialchars($p['kamar']); ?></span>
                                    </div>
                                    <span class="chat-wa"><?= htmlspecialchars($p['no_wa']); ?></span>
                                </div>
                                <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $p['no_wa'])); ?>" target="_blank" class="btn-chat-wa">
                                    <i class="fa-brands fa-whatsapp"></i> Chat
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>

        <?php 
            if (file_exists(__DIR__ . '/../components/footer.php')) {
                require_once __DIR__ . '/../components/footer.php'; 
            }
        ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php 
            if (file_exists(__DIR__ . '/../components/sidebar.php')) {
                require_once __DIR__ . '/../components/sidebar.php'; 
            }
        ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Chat Penghuni</h2>
            <p class="peraturan-desktop-subtitle">Cari nama/kamar penghuni lalu hubungi</p>

            <h3 class="desktop-section-title">Data Penghuni kos</h3>

            <div class="desktop-chat-search">
                <i class="fa-solid fa-filter search-icon"></i>
                <input type="text" id="searchInputDesktop" placeholder="Cari Nama / Kamar">
            </div>

            <div class="desktop-chat-list" id="chatListDesktop">
                <?php if (empty($penghuniKos)): ?>
                    <div class="empty-state-card" style="width: 100%; text-align: center; padding: 30px; background: #fff; border-radius: 16px; margin-top: 10px; max-width: 820px;">
                        <i class="fa-solid fa-folder-open" style="font-size: 28px; color: #4ca5b0; margin-bottom: 10px;"></i>
                        <p style="margin: 0; font-size: 14px; font-weight: 700; color: #4a5568;">Tidak ada data penghuni</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($penghuniKos as $p): ?>
                        <article class="desktop-chat-card" data-nama="<?= strtolower(htmlspecialchars($p['nama'])); ?>" data-kamar="<?= strtolower(htmlspecialchars($p['kamar'])); ?>">
                            <div class="desktop-chat-info">
                                <div class="desktop-chat-header-row">
                                   <span class="desktop-chat-nama">
    <?= htmlspecialchars($p['nama']); ?>
</span>

<span class="desktop-badge-kamar">Kamar <?= htmlspecialchars($p['kamar']); ?></span>
                                </div>
                                <span class="desktop-chat-telp"><?= htmlspecialchars($p['no_wa']); ?></span>
                            </div>
                            <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $p['no_wa'])); ?>" target="_blank" class="btn-chat-wa">
                                <i class="fa-brands fa-whatsapp"></i> Chat
                            </a>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Skrip Filter Live Search -->
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