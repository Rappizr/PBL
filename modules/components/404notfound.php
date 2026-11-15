<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role_user    = $_SESSION['user']['role'] ?? '';
$link_kembali = '/?page=login';
$label_tombol = 'Kembali ke Login';

if ($role_user === 'pemilik') {
    $link_kembali = '/?page=dashboard_pemilik';
    $label_tombol = 'Kembali ke Dashboard';
} elseif ($role_user !== '') {
    $link_kembali = '/?page=dashboard_penghuni';
    $label_tombol = 'Kembali ke Dashboard';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | 404 Not Found</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= filemtime(__DIR__ . '/../../public/css/global.css'); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= filemtime(__DIR__ . '/../../public/css/modules.css'); ?>">
</head>

<body class="login-body">

    <div class="auth-spacer"></div>

    <div class="login-card">
        <div class="reset-sukses-ikon" style="color: #c91818;">
            <i class="fa-solid fa-signs-post"></i>
        </div>
        <h1 class="brand-title">404 - Tidak Ditemukan</h1>
        <p class="brand-subtitle">Maaf, halaman yang Anda cari tidak tersedia atau telah dipindahkan.</p>

        <a href="<?= htmlspecialchars($link_kembali); ?>" class="btn-submit btn-as-link">
            <?= htmlspecialchars($label_tombol); ?>
        </a>
    </div>

    <div class="auth-spacer"></div>

    <?php require_once __DIR__ . '/footer.php'; ?>

</body>

</html>