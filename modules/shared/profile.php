<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hubungkan file koneksi database PDO
require_once __DIR__ . '/../../config/database.php';

$role_user = $_SESSION['user']['role'] ?? 'pemilik';
$user_id   = $_SESSION['user']['id'] ?? 1;

// Ambil pesan flash dari session jika ada, lalu hapus agar hilang saat di-reload
$pesan_sukses = $_SESSION['pesan_sukses'] ?? '';
$pesan_error  = $_SESSION['pesan_error'] ?? '';
unset($_SESSION['pesan_sukses'], $_SESSION['pesan_error']);

// --------------------------------------------------------------------------
// 1. PROSES FORM (UBAH PASSWORD, UBAH EMAIL, & UBAH PROFIL)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // A. Ubah Password / PIN
    if ($action === 'ubah_password') {
        $password_lama = trim($_POST['password_lama'] ?? '');
        $password_baru = trim($_POST['password_baru'] ?? '');
        $konfirmasi    = trim($_POST['password_konfirmasi'] ?? '');

        if ($role_user === 'pemilik') {
            $stmt = $pdo->prepare("SELECT pin FROM pemilik WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $user_id]);
            $pinDatabase = $stmt->fetchColumn();

            if ($pinDatabase !== $password_lama) {
                $_SESSION['pesan_error'] = 'PIN lama yang Anda masukkan salah.';
            } elseif ($password_baru !== $konfirmasi) {
                $_SESSION['pesan_error'] = 'Konfirmasi PIN baru tidak cocok.';
            } elseif (!preg_match('/^[0-9]{6}$/', $password_baru)) {
                $_SESSION['pesan_error'] = 'PIN harus terdiri dari 6 digit angka.';
            } else {
                $updateStmt = $pdo->prepare("UPDATE pemilik SET pin = :pin WHERE id = :id");
                $updateStmt->execute(['pin' => $password_baru, 'id' => $user_id]);
                $_SESSION['pesan_sukses'] = 'PIN akun berhasil diperbarui!';
            }
        }
        header('Location: /?page=profile');
        exit;
    }

    // B. Ubah Email
    if ($action === 'ubah_email') {
        $email_baru          = strtolower(trim($_POST['email_baru'] ?? ''));
        $password_verifikasi = trim($_POST['password_verifikasi'] ?? '');

        if ($role_user === 'pemilik') {
            $stmt = $pdo->prepare("SELECT pin FROM pemilik WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $user_id]);
            $pinDatabase = $stmt->fetchColumn();

            if ($pinDatabase !== $password_verifikasi) {
                $_SESSION['pesan_error'] = 'Konfirmasi PIN salah. Perubahan email dibatalkan.';
            } elseif (!filter_var($email_baru, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['pesan_error'] = 'Format email baru tidak valid.';
            } else {
                try {
                    $updateStmt = $pdo->prepare("UPDATE pemilik SET email = :email WHERE id = :id");
                    $updateStmt->execute(['email' => $email_baru, 'id' => $user_id]);
                    $_SESSION['user']['email'] = $email_baru;
                    $_SESSION['pesan_sukses'] = 'Email akun berhasil diperbarui!';
                } catch (PDOException $e) {
                    $_SESSION['pesan_error'] = 'Email tersebut sudah digunakan oleh akun lain.';
                }
            }
        }
        header('Location: /?page=profile');
        exit;
    }

    // C. Ubah Data Profil (Nama, WhatsApp, No Rekening)
    if ($action === 'ubah_profil') {
        $nama_baru        = trim($_POST['nama'] ?? '');
        $no_wa_baru       = trim($_POST['no_wa'] ?? '');
        $no_rekening_baru = trim($_POST['no_rekening'] ?? '');

        if ($role_user === 'pemilik') {
            if ($nama_baru === '') {
                $_SESSION['pesan_error'] = 'Nama tidak boleh kosong.';
            } else {
                $updateStmt = $pdo->prepare("UPDATE pemilik SET nama = :nama, no_wa = :no_wa, no_rekening = :no_rekening WHERE id = :id");
                $updateStmt->execute([
                    'nama'        => $nama_baru,
                    'no_wa'       => $no_wa_baru,
                    'no_rekening' => $no_rekening_baru,
                    'id'          => $user_id
                ]);

                $_SESSION['user']['nama'] = $nama_baru;
                $_SESSION['pesan_sukses'] = 'Data profil berhasil diperbarui!';
            }
        }
        header('Location: /?page=profile');
        exit;
    }
}

// --------------------------------------------------------------------------
// 2. PENGAMBILAN DATA RIIL DARI DATABASE
// --------------------------------------------------------------------------
$nama_user   = 'Pemilik Kos';
$email_user  = null;
$no_wa       = null;
$no_rekening = null;
$no_kamar    = null;
$is_pj       = false;

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

        $_SESSION['user']['id']    = $user_id;
        $_SESSION['user']['nama']  = $nama_user;
        $_SESSION['user']['email'] = $email_user;
    }
} else {
    $is_pj      = $_SESSION['user']['is_pj'] ?? ($_SESSION['user']['is_ketua_kos'] ?? false);
    $nama_user  = $_SESSION['user']['nama'] ?? 'Penghuni';
    $no_kamar   = $_SESSION['user']['no_kamar'] ?? null;
    $email_user = $_SESSION['user']['email'] ?? null;
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

                <!-- Kartu Informasi Akun (Mobile) -->
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

                <!-- Tombol Aksi (Mobile) -->
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
                <!-- Kartu Informasi Akun (Desktop) -->
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

                <!-- Tombol Aksi (Desktop) -->
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

    <!-- MODAL 1: UBAH DATA PROFIL (NAMA, NO WA, NO REKENING) -->
    <?php if ($show_modal === 'ubah_profil'): ?>
        <div class="modal-overlay active">
            <div class="modal-card">
                <h3 class="modal-title">Edit Data Profil</h3>
                <form action="/?page=profile" method="POST" class="modal-form">
                    <input type="hidden" name="action" value="ubah_profil">
                    
                    <div class="modal-field">
                        <label class="modal-label" for="edit_nama">Nama Lengkap</label>
                        <input type="text" id="edit_nama" name="nama"
                               class="modal-input" placeholder="Masukkan nama lengkap"
                               value="<?= htmlspecialchars($nama_user); ?>" required>
                    </div>

                    <div class="modal-field">
                        <label class="modal-label" for="edit_wa">Nomor WhatsApp</label>
                        <input type="tel" id="edit_wa" name="no_wa"
                               class="modal-input" placeholder="Contoh: 0812345678"
                               value="<?= htmlspecialchars($no_wa ?? ''); ?>">
                    </div>

                    <div class="modal-field">
                        <label class="modal-label" for="edit_rek">Nomor Rekening</label>
                        <input type="text" id="edit_rek" name="no_rekening"
                               class="modal-input" placeholder="Contoh: 0000000000"
                               value="<?= htmlspecialchars($no_rekening ?? ''); ?>">
                    </div>

                    <div class="modal-actions">
                        <a href="/?page=profile" class="btn-modal-cancel">Batal</a>
                        <button type="submit" class="btn-modal-submit">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL 2: UBAH PASSWORD -->
    <?php if ($show_modal === 'ubah_password'): ?>
        <div class="modal-overlay active">
            <div class="modal-card">
                <h3 class="modal-title">Ubah Pin</h3>
                <form action="/?page=profile" method="POST" class="modal-form">
                    <input type="hidden" name="action" value="ubah_password">
                    <div class="modal-field">
                        <label class="modal-label" for="password_lama">Pin Lama</label>
                        <input type="password" id="password_lama" name="password_lama"
                               class="modal-input" pattern="[0-9]{6}" inputmode="numeric"
                               maxlength="6" placeholder="6 digit PIN lama" required>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label" for="password_baru">Pin Baru</label>
                        <input type="password" id="password_baru" name="password_baru"
                               class="modal-input" pattern="[0-9]{6}" inputmode="numeric"
                               maxlength="6" placeholder="6 digit PIN baru" required>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label" for="password_konfirmasi">Konfirmasi Pin Baru</label>
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

    <!-- MODAL 3: UBAH EMAIL -->
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
                               maxlength="6" placeholder="Masukkan 6 digit PIN akun" required>
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