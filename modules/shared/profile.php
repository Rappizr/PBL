<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role_user   = $_SESSION['user']['role'] ?? 'penghuni';
$is_pj       = $_SESSION['user']['is_pj'] ?? ($_SESSION['user']['is_ketua_kos'] ?? false);
$nama_user   = $_SESSION['user']['nama'] ?? ($role_user === 'pemilik' ? 'Bapak Kos' : 'Penghuni');
$no_kamar    = $_SESSION['user']['no_kamar'] ?? null;
$email_user  = $_SESSION['user']['email'] ?? null;
$user_id     = $_SESSION['user']['id'] ?? '-';

$link_kembali = ($role_user === 'pemilik')
    ? '/?page=dashboard_pemilik'
    : '/?page=dashboard_penghuni';

$current_page = $_GET['page'] ?? 'profile';
$show_modal   = $_GET['modal'] ?? null; // ubah_password | ubah_email

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

                <!-- Kartu Informasi Akun -->
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
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-hashtag"></i> ID Akun</span>
                            <span class="profile-info-value profile-muted"><?= htmlspecialchars((string) $user_id); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Aksi -->
                <div class="profile-actions">
                    <a href="/?page=profile&modal=ubah_password" class="profile-action-btn">
                        <span class="profile-action-icon"><i class="fa-solid fa-key"></i></span>
                        <span>Ubah Password</span>
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
                        <div class="profile-info-row">
                            <span class="profile-info-label"><i class="fa-solid fa-hashtag"></i> ID Akun</span>
                            <span class="profile-info-value profile-muted"><?= htmlspecialchars((string) $user_id); ?></span>
                        </div>
                    </div>
                </div>

                <div class="profile-actions profile-actions-desktop">
                    <a href="/?page=profile&modal=ubah_password" class="profile-action-btn">
                        <span class="profile-action-icon"><i class="fa-solid fa-key"></i></span>
                        <span>Ubah Password</span>
                    </a>
                    <a href="/?page=profile&modal=ubah_email" class="profile-action-btn">
                        <span class="profile-action-icon"><i class="fa-solid fa-envelope-open-text"></i></span>
                        <span>Ubah Email</span>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL UBAH PASSWORD -->
    <?php if ($show_modal === 'ubah_password'): ?>
        <div class="modal-overlay active">
            <div class="modal-card">
                <h3 class="modal-title">Ubah Password</h3>
                <form action="/?page=profile" method="POST" class="modal-form">
                    <input type="hidden" name="action" value="ubah_password">
                    <div class="modal-field">
                        <label class="modal-label" for="password_lama">Password Lama (PIN)</label>
                        <input type="password" id="password_lama" name="password_lama"
                               class="modal-input" pattern="[0-9]{6}" inputmode="numeric"
                               maxlength="6" placeholder="6 digit PIN" required>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label" for="password_baru">Password Baru (PIN)</label>
                        <input type="password" id="password_baru" name="password_baru"
                               class="modal-input" pattern="[0-9]{6}" inputmode="numeric"
                               maxlength="6" placeholder="6 digit PIN baru" required>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label" for="password_konfirmasi">Konfirmasi Password Baru</label>
                        <input type="password" id="password_konfirmasi" name="password_konfirmasi"
                               class="modal-input" pattern="[0-9]{6}" inputmode="numeric"
                               maxlength="6" placeholder="Ulangi PIN baru" required>
                    </div>
                    <div class="modal-actions">
                        <a href="/?page=profile" class="btn-modal-cancel">Batal</a>
                        <button type="submit" class="btn-modal-submit">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL UBAH EMAIL -->
    <?php if ($show_modal === 'ubah_email'): ?>
        <div class="modal-overlay active">
            <div class="modal-card">
                <h3 class="modal-title">Ubah Email</h3>
                <form action="/?page=profile" method="POST" class="modal-form">
                    <input type="hidden" name="action" value="ubah_email">
                    <div class="modal-field">
                        <label class="modal-label" for="email_baru">Email Baru</label>
                        <input type="email" id="email_baru" name="email_baru"
                               class="modal-input" placeholder="contoh@email.com"
                               value="<?= htmlspecialchars($email_user ?? ''); ?>" required>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label" for="password_verifikasi">Konfirmasi dengan PIN</label>
                        <input type="password" id="password_verifikasi" name="password_verifikasi"
                               class="modal-input" pattern="[0-9]{6}" inputmode="numeric"
                               maxlength="6" placeholder="Masukkan PIN akun" required>
                    </div>
                    <div class="modal-actions">
                        <a href="/?page=profile" class="btn-modal-cancel">Batal</a>
                        <button type="submit" class="btn-modal-submit">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>