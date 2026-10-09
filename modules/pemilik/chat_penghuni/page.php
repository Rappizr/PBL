<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
<body class="kamar-body">
    <div class="mobile-container">
        <div>
            <!-- Header Modular -->
            <?php require_once __DIR__ . '/../../components/header.php'; ?>

            <main class="chat-container">
                <!-- Card Banner Judul -->
                <div class="kamar-title-card" style="margin-bottom: 0;">
                    <h2>Chat Penghuni</h2>
                    <a href="/?page=dashboard_pemilik" class="btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- Input Pencarian -->
                <div class="search-wrapper">
                    <i class="fa-solid fa-filter"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Cari Nama / Kamar">
                </div>

                <!-- Daftar Penghuni -->
                <div class="chat-list" id="chatList">
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
    <svg width="14" height="14" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M11.9059 6.18033C11.7217 3.03866 8.86716 0.617418 5.57924 1.15908C3.31507 1.53283 1.50049 3.36907 1.14841 5.63324C0.942577 6.94407 1.21342 8.18449 1.80384 9.20824L1.32175 11.0012C1.21341 11.4074 1.58715 11.7757 1.98799 11.662L3.75383 11.1745C4.55549 11.6457 5.49257 11.9166 6.49466 11.9166C9.54966 11.9166 12.0847 9.2245 11.9059 6.18033ZM9.14342 8.51491C9.09467 8.61241 9.03507 8.7045 8.95923 8.79117C8.82382 8.93742 8.67756 9.04575 8.51506 9.11075C8.35256 9.18116 8.17382 9.21367 7.98424 9.21367C7.70799 9.21367 7.41007 9.14867 7.10132 9.01325C6.78715 8.87784 6.47842 8.69907 6.16967 8.47699C5.8555 8.24949 5.563 7.99491 5.28133 7.71866C4.99966 7.43699 4.75048 7.13907 4.52298 6.83032C4.3009 6.52157 4.12216 6.21282 3.99216 5.90407C3.86216 5.59532 3.79717 5.29742 3.79717 5.01575C3.79717 4.83158 3.82966 4.65283 3.89466 4.49033C3.95966 4.32242 4.06259 4.17075 4.20884 4.03533C4.38217 3.862 4.57174 3.78075 4.77216 3.78075C4.84799 3.78075 4.92381 3.797 4.99423 3.8295C5.06465 3.862 5.12966 3.91075 5.17841 3.98116L5.80673 4.86949C5.85548 4.93991 5.8934 4.99949 5.91507 5.05908C5.94215 5.11866 5.95299 5.17282 5.95299 5.22699C5.95299 5.29199 5.93133 5.357 5.89342 5.422C5.8555 5.487 5.80673 5.55199 5.74173 5.61699L5.5359 5.83365C5.5034 5.86615 5.49259 5.89866 5.49259 5.94199C5.49259 5.96366 5.49798 5.98533 5.5034 6.00699C5.51423 6.02866 5.51967 6.04491 5.52509 6.06116C5.57384 6.15325 5.66049 6.26699 5.77965 6.40782C5.90424 6.54865 6.03426 6.69491 6.17509 6.83574C6.32134 6.98199 6.46217 7.11199 6.60842 7.23657C6.74925 7.35574 6.8684 7.437 6.96048 7.48575C6.97673 7.49116 6.993 7.50199 7.00925 7.5074C7.03091 7.51824 7.05258 7.51825 7.07967 7.51825C7.12842 7.51825 7.16093 7.502 7.19343 7.4695L7.39923 7.26366C7.46965 7.19325 7.53467 7.1445 7.59426 7.112C7.65926 7.07408 7.71883 7.0524 7.78925 7.0524C7.84342 7.0524 7.89758 7.06324 7.95716 7.09033C8.01675 7.11741 8.08173 7.14992 8.14673 7.19867L9.04592 7.83783C9.11634 7.88658 9.16507 7.94617 9.19757 8.01117C9.22465 8.08158 9.24091 8.14657 9.24091 8.22241C9.20841 8.31449 9.18675 8.41741 9.14342 8.51491Z" fill="#32522E"/>
    </svg>
    Chat
</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>

        <!-- Footer Modular -->
        <?php require_once __DIR__ . '/../../components/footer.php'; ?>
    </div>

    <!-- Script Filter Pencarian Realtime -->
    <script>
        document.getElementById('searchInput').addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('#chatList .chat-card');

            cards.forEach(card => {
                const nama = card.getAttribute('data-nama');
                const kamar = card.getAttribute('data-kamar');
                if (nama.includes(query) || kamar.includes(query)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>