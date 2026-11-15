<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Panggil Handler (hapus duplikasi query di file view)
$handler_paths = [
    __DIR__ . '/handler/chat_handlers.php',
    __DIR__ . '/../pemilik/handler/chat_handlers.php'
];
foreach ($handler_paths as $hp) {
    if (file_exists($hp)) {
        require_once $hp;
        break;
    }
}

$role_user    = $_SESSION['user']['role'] ?? 'pemilik';
$link_kembali = ($role_user === 'pemilik') ? '/?page=dashboard_pemilik' : '/?page=dashboard_penghuni';
$penghuniKos  = $penghuniKos ?? [];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Chat Penghuni</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= filemtime(__DIR__ . '/../../public/css/global.css'); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= filemtime(__DIR__ . '/../../public/css/modules.css'); ?>">
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
                    <div class="empty-state-card"
                        style="width: 100%; text-align: center; padding: 20px; background: #fff; border-radius: 12px; margin-top: 10px;">
                        <i class="fa-solid fa-folder-open"
                            style="font-size: 24px; color: #4ca5b0; margin-bottom: 8px;"></i>
                        <p style="margin: 0; font-size: 13px; font-weight: 600; color: #4a5568;">Tidak ada data penghuni</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($penghuniKos as $p): ?>
                    <div class="chat-card" data-nama="<?= strtolower(htmlspecialchars($p['nama'])); ?>"
                        data-kamar="<?= strtolower(htmlspecialchars($p['kamar'])); ?>">
                        <div class="chat-info">
                            <div class="chat-header-row">
                                <span class="chat-nama"><?= htmlspecialchars($p['nama']); ?></span>
                                <span class="badge-kamar-putih">Kamar <?= htmlspecialchars($p['kamar']); ?></span>
                            </div>
                            <span class="chat-wa"><?= htmlspecialchars($p['no_wa']); ?></span>
                        </div>
                        <a href="<?= htmlspecialchars(buatLinkWaSapaan($p, $role_user)); ?>"
                            target="_blank" class="btn-chat-wa">
                            <i class="fa-brands fa-whatsapp"></i> Chat
                        </a>
                    </div>
                    <?php endforeach; ?>
                    <div id="emptySearchMobile" class="empty-state-card empty-search-mobile">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <p>Penghuni atau kamar tidak ditemukan</p>
                    </div>
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

            <h3 class="desktop-section-title">Data Penghuni Kos</h3>

            <div class="desktop-chat-search">
                <i class="fa-solid fa-filter search-icon"></i>
                <input type="text" id="searchInputDesktop" placeholder="Cari Nama / Kamar">
            </div>

            <div class="desktop-chat-list" id="chatListDesktop">
                <?php if (empty($penghuniKos)): ?>
                <div class="empty-state-card"
                    style="width: 100%; text-align: center; padding: 30px; background: #fff; border-radius: 16px; margin-top: 10px; max-width: 820px;">
                    <i class="fa-solid fa-folder-open"
                        style="font-size: 28px; color: #4ca5b0; margin-bottom: 10px;"></i>
                    <p style="margin: 0; font-size: 14px; font-weight: 700; color: #4a5568;">Tidak ada data penghuni</p>
                </div>
                <?php else: ?>
                <?php foreach ($penghuniKos as $p): ?>
                <article class="desktop-chat-card" data-nama="<?= strtolower(htmlspecialchars($p['nama'])); ?>"
                    data-kamar="<?= strtolower(htmlspecialchars($p['kamar'])); ?>">
                    <div class="desktop-chat-info">
                        <div class="desktop-chat-header-row">
                            <span class="desktop-chat-nama"><?= htmlspecialchars($p['nama']); ?></span>
                            <span class="desktop-badge-kamar">Kamar <?= htmlspecialchars($p['kamar']); ?></span>
                        </div>
                        <span class="desktop-chat-telp"><?= htmlspecialchars($p['no_wa']); ?></span>
                    </div>
                    <a href="<?= htmlspecialchars(buatLinkWaSapaan($p, $role_user)); ?>"
                        target="_blank" class="btn-chat-wa">
                        <i class="fa-brands fa-whatsapp"></i> Chat
                    </a>
                </article>
                <?php endforeach; ?>
                <div id="emptySearchDesktop" class="empty-state-card empty-search-desktop">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <p>Penghuni atau kamar tidak ditemukan</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Script Filter Live Search -->
    <script>
    function cariPenghuni(idInput, idList, idPesanKosong, namaClassKartu) {
        const kotakCari = document.getElementById(idInput);
        const wadahList = document.getElementById(idList);
        const pesanKosong = document.getElementById(idPesanKosong);
        if (!kotakCari || !wadahList) return;

        kotakCari.addEventListener('input', function() {
            const kataKunci = kotakCari.value.toLowerCase().trim();
            const semuaKartu = wadahList.getElementsByClassName(namaClassKartu);
            let jumlahTampil = 0;

            for (let i = 0; i < semuaKartu.length; i++) {
                const kartu = semuaKartu[i];
                const nama = kartu.getAttribute('data-nama') || '';
                const kamar = kartu.getAttribute('data-kamar') || '';

                if (nama.includes(kataKunci) || kamar.includes(kataKunci)) {
                    kartu.style.display = 'flex';
                    jumlahTampil++;
                } else {
                    kartu.style.display = 'none';
                }
            }

            if (pesanKosong) {
                pesanKosong.style.display = (semuaKartu.length > 0 && jumlahTampil === 0) ? 'block' : 'none';
            }
        });
    }
    cariPenghuni('searchInputMobile', 'chatListMobile', 'emptySearchMobile', 'chat-card');
    cariPenghuni('searchInputDesktop', 'chatListDesktop', 'emptySearchDesktop', 'desktop-chat-card');
    </script>
</body>

</html>