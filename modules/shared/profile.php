<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hubungkan file koneksi database PDO
require_once __DIR__ . '/../../config/database.php';

$role_user = $_SESSION['user']['role'] ?? 'pemilik';
$user_id   = $_SESSION['user']['id'] ?? 1;

// Flash messages
$pesan_sukses = $_SESSION['pesan_sukses'] ?? '';
$pesan_error  = $_SESSION['pesan_error'] ?? '';
unset($_SESSION['pesan_sukses'], $_SESSION['pesan_error']);

// 1. Panggil Handler Pemrosesan Form POST
require_once __DIR__ . '/handler/profile_handler.php';

// 2. Pengambilan Data Profil
$nama_user   = 'Pemilik Kos';
$email_user  = null;
$no_wa       = null;
$no_rekening = null;
$no_kamar    = null;
$is_pj       = false;
$pin_aktif   = '';

if ($role_user === 'pemilik') {
    $stmt = $pdo->prepare("SELECT id, pin, nama, no_wa, no_rekening, email FROM pemilik WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $user_id]);
    $dataPemilik = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($dataPemilik) {
        $user_id     = $dataPemilik['id'];
        $nama_user   = $dataPemilik['nama'];
        $email_user  = $dataPemilik['email'];
        $no_wa       = $dataPemilik['no_wa'];
        $no_rekening = $dataPemilik['no_rekening'];
        $pin_aktif   = trim((string)$dataPemilik['pin']);

        $_SESSION['user']['id']    = $user_id;
        $_SESSION['user']['nama']  = $nama_user;
        $_SESSION['user']['email'] = $email_user;
        $_SESSION['user']['pin']   = $pin_aktif;
    }
} else {
    $stmt = $pdo->prepare("SELECT nama, no_wa, email FROM penghuni WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $user_id]);
    $dataPenghuni = $stmt->fetch(PDO::FETCH_ASSOC);
    $is_pj      = $_SESSION['user']['is_pj'] ?? ($_SESSION['user']['is_ketua_kos'] ?? false);
    $nama_user  = $_SESSION['user']['nama'] ?? ($dataPenghuni['nama'] ?? 'Penghuni');
    $no_kamar   = $_SESSION['user']['no_kamar'] ?? null;
    $email_user = $_SESSION['user']['email'] ?? ($dataPenghuni['email'] ?? null);
    $no_wa      = $_SESSION['user']['no_wa'] ?? ($dataPenghuni['no_wa'] ?? null);
}

$link_kembali = ($role_user === 'pemilik')
    ? '/?page=dashboard_pemilik'
    : '/?page=dashboard_penghuni';

$current_page = $_GET['page'] ?? 'profile';
$show_modal   = $_GET['modal'] ?? null;

$role_label = match (true) {
    $role_user === 'pemilik' => 'Pemilik Kos',
    $is_pj                   => 'Penanggung Jawab (PJ)',
    default                  => 'Penghuni Kos',
};

$role_icon = match (true) {
    $role_user === 'pemilik' => 'fa-solid fa-user-tie',
    $is_pj                   => 'fa-solid fa-user-shield',
    default                  => 'fa-solid fa-bed',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Profil Akun</title>
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
                    <h2 class="subpage-banner-title">Profil Akun</h2>
                    <a href="<?= $link_kembali; ?>" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <?php if ($pesan_sukses): ?>
                    <div class="alert-success" style="margin-bottom: 14px;"><?= htmlspecialchars($pesan_sukses); ?></div>
                <?php endif; ?>
                <?php if ($pesan_error): ?>
                    <div class="alert-error" style="margin-bottom: 14px;"><?= htmlspecialchars($pesan_error); ?></div>
                <?php endif; ?>

                <div class="profile-card">
                    <div class="profile-avatar-wrap">
                        <div class="profile-avatar">
                            <i class="<?= $role_icon; ?>"></i>
                        </div>
                        <span class="profile-role-badge"><?= htmlspecialchars($role_label); ?></span>
                    </div>

                    <div class="profile-info-list">
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-user"></i> Nama</span>
                            <span class="profile-info-value"><?= htmlspecialchars($nama_user); ?></span>
                        </div>
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-id-badge"></i> Role</span>
                            <span class="profile-info-value"><?= htmlspecialchars($role_label); ?></span>
                        </div>
                        <?php if ($role_user !== 'pemilik' && $no_kamar): ?>
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-door-open"></i> Kamar</span>
                            <span class="profile-info-value">Kamar <?= htmlspecialchars($no_kamar); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-envelope"></i> Email</span>
                            <span class="profile-info-value <?= $email_user ? '' : 'profile-muted'; ?>">
                                <?= $email_user ? htmlspecialchars($email_user) : 'Belum diatur'; ?>
                            </span>
                        </div>

                        <?php if ($role_user === 'pemilik'): ?>
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-phone"></i> WhatsApp</span>
                            <span class="profile-info-value"><?= htmlspecialchars($no_wa ?? '-'); ?></span>
                        </div>
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-credit-card"></i> No. Rekening</span>
                            <span class="profile-info-value"><?= htmlspecialchars($no_rekening ?? '-'); ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-hashtag"></i> ID Akun</span>
                            <span class="profile-info-value profile-muted"><?= htmlspecialchars((string) $user_id); ?></span>
                        </div>
                    </div>
                </div>

                <div class="profile-actions">
                    <a href="/?page=profile&modal=ubah_profil" class="profile-action-btn">
                        <span class="profile-action-icon"><i class="fa-solid fa-user-pen"></i></span>
                        <span>Edit Data Profil</span>
                    </a>
                    <a href="/?page=profile&modal=ubah_password" class="profile-action-btn">
                        <span class="profile-action-icon"><i class="fa-solid fa-key"></i></span>
                        <span>Ubah Pin</span>
                    </a>
                    <a href="/?page=profile&modal=ubah_email" class="profile-action-btn">
                        <span class="profile-action-icon"><i class="fa-solid fa-envelope-open-text"></i></span>
                        <span>Ubah Email</span>
                    </a>
                </div>
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Profil Akun</h2>
            <p class="desktop-header-subtitle">Informasi akun dan pengaturan keamanan</p>

            <?php if ($pesan_sukses): ?>
                <div class="alert-success" style="max-width: 780px; margin-bottom: 16px;"><?= htmlspecialchars($pesan_sukses); ?></div>
            <?php endif; ?>
            <?php if ($pesan_error): ?>
                <div class="alert-error" style="max-width: 780px; margin-bottom: 16px;"><?= htmlspecialchars($pesan_error); ?></div>
            <?php endif; ?>

            <div class="profile-desktop-grid">
                <div class="profile-card profile-card-desktop">
                    <div class="profile-avatar-wrap">
                        <div class="profile-avatar profile-avatar-lg">
                            <i class="<?= $role_icon; ?>"></i>
                        </div>
                        <span class="profile-role-badge"><?= htmlspecialchars($role_label); ?></span>
                    </div>

                    <div class="profile-info-list">
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-user"></i> Nama</span>
                            <span class="profile-info-value"><?= htmlspecialchars($nama_user); ?></span>
                        </div>
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-id-badge"></i> Role</span>
                            <span class="profile-info-value"><?= htmlspecialchars($role_label); ?></span>
                        </div>
                        <?php if ($role_user !== 'pemilik' && $no_kamar): ?>
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-door-open"></i> Kamar</span>
                            <span class="profile-info-value">Kamar <?= htmlspecialchars($no_kamar); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-envelope"></i> Email</span>
                            <span class="profile-info-value <?= $email_user ? '' : 'profile-muted'; ?>">
                                <?= $email_user ? htmlspecialchars($email_user) : 'Belum diatur'; ?>
                            </span>
                        </div>

                        <?php if ($role_user === 'pemilik'): ?>
                            <div class="profile-info-row">
                                <span class="profile-info-label"><i class="fa-solid fa-credit-card"></i> No. Rekening</span>
                                <span class="profile-info-value"><?= htmlspecialchars($no_rekening ?? '-'); ?></span>
                            </div>
                            <?php endif; ?>
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-phone"></i> WhatsApp</span>
                            <span class="profile-info-value"><?= htmlspecialchars($no_wa ?? '-'); ?></span>
                        </div>

                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-hashtag"></i> ID Akun</span>
                            <span class="profile-info-value profile-muted"><?= htmlspecialchars((string) $user_id); ?></span>
                        </div>
                    </div>
                </div>

                <div class="profile-actions profile-actions-desktop">
                    <a href="/?page=profile&modal=ubah_profil" class="profile-action-btn">
                        <span class="profile-action-icon"><i class="fa-solid fa-user-pen"></i></span>
                        <span>Edit Data Profil</span>
                    </a>
                    <a href="/?page=profile&modal=ubah_password" class="profile-action-btn">
                        <span class="profile-action-icon"><i class="fa-solid fa-key"></i></span>
                        <span>Ubah Pin</span>
                    </a>
                    <a href="/?page=profile&modal=ubah_email" class="profile-action-btn">
                        <span class="profile-action-icon"><i class="fa-solid fa-envelope-open-text"></i></span>
                        <span>Ubah Email</span>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <!-- 3. Panggil File Modals -->
    <?php require_once __DIR__ . '/modals/modal_profile.php'; ?>

</body>
</html>