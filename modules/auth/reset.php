<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect ke dashboard jika sudah login
if (isset($_SESSION['user']) && !empty($_SESSION['user']['role'])) {
    if ($_SESSION['user']['role'] === 'pemilik') {
        header('Location: /?page=dashboard_pemilik');
    } elseif ($_SESSION['user']['role'] === 'penghuni') {
        header('Location: /?page=dashboard_penghuni');
    }
    exit;
}

// Hubungkan file koneksi database dan pengirim email (mundur 2 level folder jika di modules/auth/)
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mail.php';

const PIN_PANJANG = 6;
const RESET_BERLAKU_DETIK = 600; // 10 menit

// Token CSRF
if (empty($_SESSION['csrf_reset'])) {
    $_SESSION['csrf_reset'] = bin2hex(random_bytes(16));
}

// Tombol ulangi proses reset
if (isset($_GET['batal'])) {
    unset(
        $_SESSION['reset_email'],
        $_SESSION['reset_otp'],
        $_SESSION['reset_expire'],
        $_SESSION['reset_table'],
        $_SESSION['reset_id']
    );
    header('Location: /?page=reset');
    exit;
}

// Fungsi cek akun di database PostgreSQL (cek tabel pemilik & penghuni)
function cari_akun_database(PDO $pdo, string $email): ?array
{
    // 1. Cek tabel pemilik
    $stmt = $pdo->prepare("SELECT id, nama, email, 'pemilik' AS role FROM pemilik WHERE LOWER(email) = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) return $user;

    // 2. Cek tabel penghuni
    $stmt = $pdo->prepare("SELECT id, nama, email, 'penghuni' AS role FROM penghuni WHERE LOWER(email) = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) return $user;

    return null;
}

$error  = '';
$sukses = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_reset'], $_POST['csrf'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    } else {
        $aksi = $_POST['aksi'] ?? '';

        // LANGKAH 1: Validasi Email & Kirim Kode OTP
        if ($aksi === 'kirim_email') {
            $email = strtolower(trim($_POST['email'] ?? ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Format email tidak valid.';
            } else {
                $akun = cari_akun_database($pdo, $email);

                if ($akun === null) {
                    $error = 'Email tidak terdaftar di sistem.';
                } else {
                    // Buat 6 digit angka OTP acak
                    $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

                    if (kirim_email_otp($akun['email'], $akun['nama'], $otp)) {
                        $_SESSION['reset_email']  = $akun['email'];
                        $_SESSION['reset_otp']    = $otp;
                        $_SESSION['reset_table']  = $akun['role'];
                        $_SESSION['reset_id']     = $akun['id'];
                        $_SESSION['reset_expire'] = time() + RESET_BERLAKU_DETIK;
                    } else {
                        // Tampilkan pesan error teknis asli dari PHPMailer
                        $detail_error = $_SESSION['debug_mail_error'] ?? 'Tidak ada log error.';
                        $error = 'Gagal kirim OTP: ' . $detail_error;
                        unset($_SESSION['debug_mail_error']);
                    }   
                }
            }
        }

        // LANGKAH 2: Verifikasi OTP & Simpan PIN Baru
        if ($aksi === 'simpan_sandi') {
            $email_sesi = $_SESSION['reset_email'] ?? '';
            $otp_input  = trim($_POST['otp'] ?? '');
            $sandi      = trim($_POST['sandi'] ?? '');

            if ($email_sesi === '' || ($_SESSION['reset_expire'] ?? 0) < time()) {
                $error = 'Waktu verifikasi habis. Silakan masukkan email lagi.';
                unset($_SESSION['reset_email'], $_SESSION['reset_otp'], $_SESSION['reset_expire']);
            } elseif ($otp_input !== ($_SESSION['reset_otp'] ?? '')) {
                $error = 'Kode OTP verifikasi salah.';
            } elseif (!preg_match('/^\d{' . PIN_PANJANG . '}$/', $sandi)) {
                $error = 'PIN baru harus ' . PIN_PANJANG . ' digit angka.';
            } else {
                // Update PIN ke tabel database PostgreSQL
                $tabel = $_SESSION['reset_table'] === 'pemilik' ? 'pemilik' : 'penghuni';
                $stmt  = $pdo->prepare("UPDATE {$tabel} SET pin = :pin WHERE id = :id");
                $stmt->execute([
                    'pin' => $sandi,
                    'id'  => $_SESSION['reset_id']
                ]);

                // Bersihkan session reset
                unset(
                    $_SESSION['reset_email'],
                    $_SESSION['reset_otp'],
                    $_SESSION['reset_expire'],
                    $_SESSION['reset_table'],
                    $_SESSION['reset_id']
                );

                $_SESSION['csrf_reset'] = bin2hex(random_bytes(16));
                $sukses = true;
            }
        }
    }
}

// Menentukan tahapan form: 1 = Email, 2 = Input OTP + PIN, 3 = Sukses
$langkah = 1;
if ($sukses) {
    $langkah = 3;
} elseif (!empty($_SESSION['reset_email']) && ($_SESSION['reset_expire'] ?? 0) >= time()) {
    $langkah = 2;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Ubah kata sandi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="login-body">

    <div class="login-card">
        <h1 class="brand-title">SITEMAN - KOS</h1>
        <p class="brand-subtitle">Ubah kata sandi</p>

        <?php if ($error !== ''): ?>
            <div class="alert-error"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($langkah === 1): ?>
            <!-- LANGKAH 1: INPUT EMAIL -->
            <form action="" method="POST">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_reset']; ?>">
                <input type="hidden" name="aksi" value="kirim_email">

                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" name="email" class="input-field" placeholder="Email terdaftar"
                           value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>"
                           required autocomplete="off">
                </div>

                <button type="submit" class="btn-submit">Kirim Kode OTP</button>
            </form>
            <a href="/?page=login" class="link-kembali">Kembali ke login</a>

        <?php elseif ($langkah === 2): ?>
            <!-- LANGKAH 2: VERIFIKASI OTP & PIN BARU -->
            <p class="reset-info">Kode dikirim ke: <strong><?= htmlspecialchars($_SESSION['reset_email']); ?></strong></p>

            <form action="" method="POST">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_reset']; ?>">
                <input type="hidden" name="aksi" value="simpan_sandi">

                <div class="input-wrapper">
                    <i class="fa-solid fa-shield-halved input-icon"></i>
                    <input type="text" name="otp" class="input-field" placeholder="6 Digit Kode OTP"
                           pattern="[0-9]{6}" inputmode="numeric" maxlength="6" required autofocus>
                </div>

                <div class="input-wrapper">
                    <i class="fa-solid fa-key input-icon"></i>
                    <input type="password" name="sandi" class="input-field" placeholder="PIN Baru (6 Digit)"
                           pattern="[0-9]{<?= PIN_PANJANG; ?>}" inputmode="numeric"
                           minlength="<?= PIN_PANJANG; ?>" maxlength="<?= PIN_PANJANG; ?>"
                           autocomplete="new-password" required>
                </div>
                <p class="input-hint">Cek Inbox atau folder Spam email Anda.</p>

                <button type="submit" class="btn-submit">Verifikasi &amp; Simpan</button>
            </form>
            <a href="/?page=reset&batal=1" class="link-kembali">Bukan email ini? Ulangi</a>

        <?php else: ?>
            <!-- LANGKAH 3: SUKSES -->
            <div class="reset-sukses-ikon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="alert-success">PIN akun berhasil diatur ulang.</div>
            <a href="/?page=login" class="btn-submit btn-as-link">Ke halaman login</a>
        <?php endif; ?>
    </div>

    <?php require_once __DIR__ . '/../components/footer.php'; ?>
</body>
</html>