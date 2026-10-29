]<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sudah login? Sama seperti login.php, arahkan ke dashboard
if (isset($_SESSION['user']) && !empty($_SESSION['user']['role'])) {
    if ($_SESSION['user']['role'] === 'pemilik') {
        header('Location: /?page=dashboard_pemilik');
    } elseif ($_SESSION['user']['role'] === 'penghuni') {
        header('Location: /?page=dashboard_penghuni');
    }
    exit;
}

const PIN_PANJANG = 6;            // samakan dengan aturan PIN di login.php
const RESET_BERLAKU_DETIK = 600;  // proses ubah password berlaku 10 menit

// Token CSRF
if (empty($_SESSION['csrf_reset'])) {
    $_SESSION['csrf_reset'] = bin2hex(random_bytes(16));
}

// Link "Ulangi" dari langkah 2
if (isset($_GET['batal'])) {
    unset($_SESSION['reset_email'], $_SESSION['reset_expire']);
    header('Location: /?page=reset');
    exit;
}

/* ---------- DATA & FUNGSI ---------- */
function cari_akun_by_email(string $email): ?array
{
   
    $akun_dummy = [
        'rafi@siteman.kos'  => ['role' => 'penghuni'],
        'bapak@siteman.kos' => ['role' => 'pemilik'],
    ];
    return $akun_dummy[$email] ?? null;
}

function simpan_sandi_baru(string $email, string $sandi): bool
{

    return true;
}

/* ---------- PROSES FORM ---------- */
$error  = '';
$sukses = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_reset'], $_POST['csrf'] ?? '')) {
        $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
    } else {
        $aksi = $_POST['aksi'] ?? '';

        // LANGKAH 1: cek email
        if ($aksi === 'kirim_email') {
            $email = strtolower(trim($_POST['email'] ?? ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Format email tidak valid.';
            } elseif (cari_akun_by_email($email) === null) {
                $error = 'Email tidak terdaftar.';
            } else {
                $_SESSION['reset_email']  = $email;
                $_SESSION['reset_expire'] = time() + RESET_BERLAKU_DETIK;
            }
        }

        // LANGKAH 2: simpan sandi baru
        if ($aksi === 'simpan_sandi') {
            $email = $_SESSION['reset_email'] ?? '';
            $sandi = $_POST['sandi'] ?? '';

            if ($email === '' || ($_SESSION['reset_expire'] ?? 0) < time()) {
                $error = 'Waktu habis. Silakan masukkan email lagi.';
                unset($_SESSION['reset_email'], $_SESSION['reset_expire']);
            } elseif (!preg_match('/^\d{' . PIN_PANJANG . '}$/', $sandi)) {
                $error = 'Kata sandi harus ' . PIN_PANJANG . ' digit angka.';
            } elseif (!simpan_sandi_baru($email, $sandi)) {
                $error = 'Gagal menyimpan kata sandi. Coba lagi.';
            } else {
                unset($_SESSION['reset_email'], $_SESSION['reset_expire']);
                $_SESSION['csrf_reset'] = bin2hex(random_bytes(16));
                $sukses = true;
            }
        }
    }
}

// Tentukan tampilan yang muncul: 1 = email, 2 = sandi baru, 3 = sukses
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
    <title>SITEMAN - KOS | Ubah Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Link CSS Modular -->
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="dashboard-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">

    <div class="login-card">
        <h1 class="brand-title">SITEMAN - KOS</h1>
        <p class="brand-subtitle">Ubah password</p>

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
                    <input type="email" name="email" class="input-field" placeholder="Email"
                           value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>"
                           required autocomplete="off">
                </div>

                <button type="submit" class="btn-submit">Kirim</button>
            </form>
            <a href="/?page=login" class="link-kembali">Kembali ke login</a>

        <?php elseif ($langkah === 2): ?>
            <!-- LANGKAH 2: INPUT KATA SANDI BARU -->
            <p class="reset-info">Akun: <strong><?= htmlspecialchars($_SESSION['reset_email']); ?></strong></p>

            <form action="" method="POST">
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_reset']; ?>">
                <input type="hidden" name="aksi" value="simpan_sandi">

                <div class="input-wrapper">
                    <i class="fa-solid fa-key input-icon"></i>
                    <input type="password" name="sandi" class="input-field" placeholder="Kata Sandi"
                           pattern="[0-9]{<?= PIN_PANJANG; ?>}" inputmode="numeric"
                           minlength="<?= PIN_PANJANG; ?>" maxlength="<?= PIN_PANJANG; ?>"
                           autocomplete="new-password" required>
                </div>
                <p class="input-hint">Isi <?= PIN_PANJANG; ?> digit angka, sama seperti PIN saat login.</p>

                <button type="submit" class="btn-submit">Simpan</button>
            </form>
            <a href="/?page=reset&batal=1" class="link-kembali">Bukan email ini? Ulangi</a>

        <?php else: ?>
            <!-- LANGKAH 3: SUKSES -->
            <div class="reset-sukses-ikon"><i class="fa-solid fa-circle-check"></i></div>
            <div class="alert-success">Kata sandi berhasil diubah.</div>
            <a href="/?page=login" class="btn-submit btn-as-link">Ke halaman login</a>
        <?php endif; ?>
    </div>

</body>
</html>