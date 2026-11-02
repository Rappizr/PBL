<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil session user, jika kosong berikan nilai aman default penghuni (bukan pemilik)
$currentUser = $_SESSION['user'] ?? [
    'role'  => 'penghuni',
    'nama'  => 'Anak Kos',
    'kamar' => null,
    'is_pj' => false
];

// Deteksi Role
$is_pemilik = ($currentUser['role'] ?? '') === 'pemilik';
$is_pj      = !$is_pemilik && (!empty($currentUser['is_pj']) || ($currentUser['role'] ?? '') === 'pj');
?>

<header class="dashboard-header">
    <h1 class="brand-title-dashboard">SITEMAN - KOS</h1>

    <?php if ($is_pemilik): ?>
        <!-- Profil Pemilik Kos (Klik ke Halaman Profil) -->
        <a href="/?page=profile" class="kotak-kapsul-pemilik header-user-link">
            <span class="lingkaran-user-abu">
                <i class="fa-solid fa-user"></i>
            </span>
            <span class="tulisan-nama"><?= htmlspecialchars($currentUser['nama'] ?? 'Bapak Kos'); ?></span>
        </a>

    <?php elseif ($is_pj): ?>
        <!-- Profil PJ Kos (Klik ke Halaman Profil) -->
        <a href="/?page=profile" class="kotak-kapsul-pemilik badge-pj-header header-user-link">
            <span class="lingkaran-user-abu">
                <i class="fa-solid fa-user-shield"></i>
            </span>
            <span class="tulisan-nama">
                <?= htmlspecialchars($currentUser['nama'] ?? 'Mas Dafa'); ?>
                <?= !empty($currentUser['kamar']) ? '(' . htmlspecialchars($currentUser['kamar']) . ')' : ''; ?>
            </span>
        </a>

    <?php else: ?>
        <!-- Profil Penghuni Biasa (Klik ke Halaman Profil) -->
        <a href="/?page=profile" class="kotak-kapsul-pemilik badge-penghuni-header header-user-link">
            <span class="lingkaran-user-abu">
                <i class="fa-solid fa-user"></i>
            </span>
            <span class="tulisan-nama">
                <?= htmlspecialchars($currentUser['nama'] ?? (!empty($currentUser['kamar']) ? 'Kamar ' . $currentUser['kamar'] : 'Mas Rafi')); ?>
            </span>
        </a>
    <?php endif; ?>
</header>