<?php
// Pastikan sesi sudah berjalan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = $_SESSION['user'] ?? [
    'role'  => 'pemilik',       
    'nama'  => 'Bapak Kos',
    'kamar' => null
];

$role = $currentUser['role'] ?? 'pemilik';
?>

<header class="dashboard-header">
    <h1 class="brand-title-dashboard">SITEMAN - KOS</h1>

    <?php if ($role === 'pemilik'): ?>
        <div class="kotak-kapsul-pemilik">
            <span class="lingkaran-user-abu">
                <i class="fa-solid fa-user"></i>
            </span>
            <span class="tulisan-nama"><?= htmlspecialchars($currentUser['nama'] ?? 'Bapak Kos'); ?></span>
        </div>

    <?php elseif ($role === 'pj'): ?>
        <div class="kotak-kapsul-pemilik badge-pj-header">
            <span class="lingkaran-user-abu">
                <i class="fa-solid fa-user-shield"></i>
            </span>
            <span class="tulisan-nama">
                <?= htmlspecialchars($currentUser['nama'] ?? 'PJ Kos'); ?> 
                <?= !empty($currentUser['kamar']) ? '(' . htmlspecialchars($currentUser['kamar']) . ')' : ''; ?>
            </span>
        </div>

    <?php else: ?>
        <div class="kotak-kapsul-pemilik badge-penghuni-header">
            <span class="lingkaran-user-abu">
                <i class="fa-solid fa-bed"></i>
            </span>
            <span class="tulisan-nama">
                <?= !empty($currentUser['kamar']) ? 'Kamar ' . htmlspecialchars($currentUser['kamar']) : htmlspecialchars($currentUser['nama'] ?? 'Penghuni'); ?>
            </span>
        </div>
    <?php endif; ?>
</header>