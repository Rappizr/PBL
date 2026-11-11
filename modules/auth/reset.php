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

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mail.php';

const PIN_PANJANG = 6;
const RESET_BERLAKU_DETIK = 300; // 5 Menit (300 detik)

// Token CSRF
if (empty($_SESSION['csrf_reset'])) {
    $_SESSION['csrf_reset'] = bin2hex(random_bytes(16));
}

// Tombol ulangi / ganti email tujuan
if (isset($_GET['batal'])) {
    unset(
        $_SESSION['reset_email'],
        $_SESSION['reset_nama'],
        $_SESSION['reset_otp'],
        $_SESSION['reset_expire'],
        $_SESSION['reset_table'],
        $_SESSION['reset_id']
    );
    header('Location: /?page=reset');
    exit;
}

function cari_akun_database(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare("SELECT id, nama, email, 'pemilik' AS role FROM pemilik WHERE LOWER(email) = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) return $user;

    $stmt = $pdo->prepare("SELECT id, nama, email, 'penghuni' AS role FROM penghuni WHERE LOWER(email) = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) return $user;

    return null;
}

$error        = $_SESSION['flash_error'] ?? '';
$pesan_sukses = $_SESSION['flash_sukses'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_sukses']);

$sukses = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_reset'], $_POST['csrf'] ?? '')) {
        $_SESSION['flash_error'] = 'Sesi tidak valid. Silakan muat ulang halaman.';
        header('Location: /?page=reset');
        exit;
    } else {
        $aksi = $_POST['aksi'] ?? '';

        // LANGKAH 1: Validasi Email & Kirim Kode OTP
        if ($aksi === 'kirim_email') {
            $email = strtolower(trim($_POST['email'] ?? ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['flash_error'] = 'Format email tidak valid. Silakan periksa kembali alamat email Anda.';
            } else {
                $akun = cari_akun_database($pdo, $email);

                if ($akun === null) {
                    $_SESSION['flash_error'] = 'Email tidak terdaftar di sistem. Silakan periksa kembali email Anda.';
                } else {
                    $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

                    if (kirim_email_otp($akun['email'], $akun['nama'], $otp)) {
                        $_SESSION['reset_email']  = $akun['email'];
                        $_SESSION['reset_nama']   = $akun['nama'];
                        $_SESSION['reset_otp']    = $otp;
                        $_SESSION['reset_table']  = $akun['role'];
                        $_SESSION['reset_id']     = $akun['id'];
                        $_SESSION['reset_expire'] = time() + RESET_BERLAKU_DETIK;
                    } else {
                        $detail_error = $_SESSION['debug_mail_error'] ?? 'Tidak ada log error.';
                        $_SESSION['flash_error'] = 'Gagal mengirim kode OTP: ' . $detail_error;
                        unset($_SESSION['debug_mail_error']);
                    }   
                }
            }
            // PRG Pattern: Redirect ke GET agar Refresh browser aman
            header('Location: /?page=reset');
            exit;
        }

        // AKSI KHUSUS: Kirim Ulang OTP
        if ($aksi === 'kirim_ulang_otp') {
            $email_sesi = $_SESSION['reset_email'] ?? '';
            $nama_sesi  = $_SESSION['reset_nama'] ?? 'Pengguna';

            if ($email_sesi === '') {
                $_SESSION['flash_error'] = 'Sesi telah berakhir. Silakan masukkan email Anda kembali.';
            } else {
                $otp_baru = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

                if (kirim_email_otp($email_sesi, $nama_sesi, $otp_baru)) {
                    $_SESSION['reset_otp']    = $otp_baru;
                    $_SESSION['reset_expire'] = time() + RESET_BERLAKU_DETIK;
                    $_SESSION['flash_sukses'] = 'Kode OTP baru berhasil dikirimkan ke email Anda!';
                } else {
                    $detail_error = $_SESSION['debug_mail_error'] ?? 'Tidak ada log error.';
                    $_SESSION['flash_error'] = 'Gagal mengirim ulang kode OTP: ' . $detail_error;
                    unset($_SESSION['debug_mail_error']);
                }
            }
            // PRG Pattern: Redirect ke GET agar Refresh browser aman
            header('Location: /?page=reset');
            exit;
        }

        // LANGKAH 2: Verifikasi OTP & Simpan PIN Baru
        if ($aksi === 'simpan_sandi') {
            $email_sesi = $_SESSION['reset_email'] ?? '';
            $otp_input  = preg_replace('/[^0-9]/', '', trim($_POST['otp'] ?? ''));
            $sandi      = preg_replace('/[^0-9]/', '', trim($_POST['sandi'] ?? ''));

            if ($email_sesi === '') {
                $error = 'Sesi telah berakhir. Silakan masukkan email kembali.';
            } elseif (($_SESSION['reset_expire'] ?? 0) < time()) {
                $error = 'Waktu verifikasi habis. Silakan klik tombol Kirim Ulang Kode OTP di bawah.';
            } elseif ($otp_input !== ($_SESSION['reset_otp'] ?? '')) {
                $error = 'Kode OTP verifikasi salah.';
            } elseif (strlen($sandi) !== PIN_PANJANG) {
                $error = 'PIN baru harus tepat ' . PIN_PANJANG . ' digit angka.';
            } else {
                $tabel = $_SESSION['reset_table'] === 'pemilik' ? 'pemilik' : 'penghuni';
                $stmt  = $pdo->prepare("UPDATE {$tabel} SET pin = :pin WHERE id = :id");
                $stmt->execute([
                    'pin' => $sandi,
                    'id'  => $_SESSION['reset_id']
                ]);

                unset(
                    $_SESSION['reset_email'],
                    $_SESSION['reset_nama'],
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

$langkah = 1;
$sisa_detik = 0;

if ($sukses) {
    $langkah = 3;
} elseif (!empty($_SESSION['reset_email'])) {
    $langkah = 2;
    // Hitung sisa detik secara presisi dari sesi
    $sisa_detik = max(0, (int)($_SESSION['reset_expire'] ?? 0) - time());
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

    <div class="auth-spacer"></div>

    <div class="login-card">
        <h1 class="brand-title">SITEMAN - KOS</h1>
        <p class="brand-subtitle">Ubah kata sandi</p>

        <?php if ($error !== ''): ?>
            <div class="alert-error reset-alert-gap"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($pesan_sukses !== ''): ?>
            <div class="alert-success reset-alert-gap"><?= htmlspecialchars($pesan_sukses); ?></div>
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

            <div class="otp-timer-box">
                <i class="fa-solid fa-clock-rotate-left"></i> Sisa Waktu: <span id="countdownDisplay" class="countdown-timer-text">--:--</span>
            </div>

            <!-- Form Simpan Sandi -->
            <form action="" method="POST" id="formResetOtp">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_reset']; ?>">
                <input type="hidden" name="aksi" value="simpan_sandi">

                <div class="input-wrapper">
                    <i class="fa-solid fa-shield-halved input-icon"></i>
                    <input type="text" name="otp" id="inputOtp" class="input-field" placeholder="6 Digit Kode OTP"
                           pattern="[0-9]{6}" inputmode="numeric" maxlength="6"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)"
                           required autofocus <?= $sisa_detik <= 0 ? 'disabled' : ''; ?>>
                </div>

                <!-- Input PIN Baru -->
                <div class="input-wrapper reset-pin-container">
                    <i class="fa-solid fa-key input-icon"></i>
                    <input type="password" name="sandi" id="inputSandi" class="input-field reset-pin-field" placeholder="PIN Baru (6 Digit)"
                           pattern="[0-9]{<?= PIN_PANJANG; ?>}" inputmode="numeric"
                           minlength="<?= PIN_PANJANG; ?>" maxlength="<?= PIN_PANJANG; ?>"
                           oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, <?= PIN_PANJANG; ?>)"
                           autocomplete="new-password" data-lpignore="true" required <?= $sisa_detik <= 0 ? 'disabled' : ''; ?>>
                    <button type="button" id="btnToggleEye" class="reset-eye-button" onclick="togglePinReset(event)" title="Lihat PIN">
                        <i id="iconMata" class="fa-solid fa-eye-slash"></i>
                    </button>
                </div>
                <p class="input-hint">Cek Inbox atau folder Spam email Anda.</p>

                <button type="submit" class="btn-submit btn-submit-reset <?= $sisa_detik <= 0 ? 'btn-reset-disabled' : ''; ?>" id="btnSubmitOtp" <?= $sisa_detik <= 0 ? 'disabled' : ''; ?>>Verifikasi &amp; Simpan</button>
            </form>

            <!-- Form Kirim Ulang OTP -->
            <form action="" method="POST" id="formKirimUlang" class="form-kirim-ulang-otp" style="<?= $sisa_detik <= 0 ? 'display:block;' : 'display:none;'; ?>">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_reset']; ?>">
                <input type="hidden" name="aksi" value="kirim_ulang_otp">
                <button type="submit" class="btn-submit btn-kirim-ulang-otp">
                    <i class="fa-solid fa-rotate-right"></i> Kirim Ulang Kode OTP
                </button>
            </form>

            <a href="/?page=reset&batal=1" class="link-kembali" id="linkUlangi">Bukan email ini? Ganti Email</a>

            <script>
                function togglePinReset(e) {
                    if (e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    const input = document.getElementById('inputSandi');
                    const icon = document.getElementById('iconMata');
                    if (!input || !icon) return;

                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.className = 'fa-solid fa-eye';
                    } else {
                        input.type = 'password';
                        icon.className = 'fa-solid fa-eye-slash';
                    }
                }

                document.addEventListener('DOMContentLoaded', function () {
                    const otpInput = document.getElementById('inputOtp');
                    const sandiInput = document.getElementById('inputSandi');

                    [otpInput, sandiInput].forEach(function (el) {
                        if (!el) return;
                        el.addEventListener('paste', function () {
                            setTimeout(() => {
                                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
                            }, 10);
                        });
                    });
                });

                // Perhitungan countdown waktu absolut
                let remainingSeconds = <?= (int)$sisa_detik; ?>;
                const targetTimestamp = Date.now() + (remainingSeconds * 1000);

                const display = document.getElementById('countdownDisplay');
                const btnSubmit = document.getElementById('btnSubmitOtp');
                const inputOtp = document.getElementById('inputOtp');
                const inputSandi = document.getElementById('inputSandi');
                const formKirimUlang = document.getElementById('formKirimUlang');

                function updateCountdown() {
                    const now = Date.now();
                    const secondsLeft = Math.max(0, Math.ceil((targetTimestamp - now) / 1000));

                    if (secondsLeft <= 0) {
                        display.textContent = 'Kedaluwarsa';
                        display.style.color = '#c91818';

                        btnSubmit.disabled = true;
                        btnSubmit.classList.add('btn-reset-disabled');
                        inputOtp.disabled = true;
                        inputSandi.disabled = true;

                        if (formKirimUlang) {
                            formKirimUlang.style.display = 'block';
                        }
                        return;
                    }

                    const minutes = Math.floor(secondsLeft / 60);
                    const seconds = secondsLeft % 60;
                    display.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    setTimeout(updateCountdown, 1000);
                }

                updateCountdown();
            </script>

        <?php else: ?>
            <!-- LANGKAH 3: SUKSES -->
            <div class="reset-sukses-ikon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="alert-success">PIN akun berhasil diatur ulang.</div>
            <a href="/?page=login" class="btn-submit btn-as-link">Ke halaman login</a>
        <?php endif; ?>
    </div>

    <div class="auth-spacer"></div>

    <?php require_once __DIR__ . '/../components/footer.php'; ?>
</body>
</html>