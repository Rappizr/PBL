<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

$peraturan_list = [];
try {
    $peraturan_raw = $pdo->query('SELECT id, peraturan AS judul, deskripsi, icon FROM peraturan_kos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($peraturan_raw as $r) {
        if (empty($r['icon'])) {
            $r['icon'] = 'fa-solid fa-clock'; // Default fallback icon
        }
        $peraturan_list[] = $r;
    }
} catch (PDOException $e) {
    $peraturan_list = [];
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Peraturan Kos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>

<body class="dashboard-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <main class="page-content">
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title">Peraturan Kos</h2>
                    <a href="/?page=dashboard_penghuni" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="peraturan-card-wrapper">
                    <?php foreach ($peraturan_list as $rule): ?>
                    <div class="peraturan-item">
                        <div class="peraturan-icon-col">
                            <i class="<?= $rule['icon'] ?>"></i>
                        </div>
                        <div class="peraturan-content">
                            <h3 class="peraturan-title"><?= htmlspecialchars($rule['judul']) ?></h3>
                            <p class="peraturan-desc"><?= htmlspecialchars($rule['deskripsi']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Peraturan Kos</h2>
            <p class="peraturan-desktop-subtitle">Peraturan kos wajib ditaati oleh seluruh penghuni</p>

            <div class="desktop-rules-container">
                <?php foreach ($peraturan_list as $p): ?>
                <div class="rule-item">
                    <div class="rule-icon-box">
                        <i class="<?= htmlspecialchars($p['icon']) ?>"></i>
                    </div>
                    <div class="rule-text-box">
                        <h4 class="rule-title"><?= htmlspecialchars($p['judul']) ?></h4>
                        <p class="rule-desc"><?= htmlspecialchars($p['deskripsi']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

</body>

</html>