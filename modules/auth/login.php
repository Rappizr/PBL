<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role  = $_GET['role'] ?? 'penghuni';
$error = '';

if (isset($_SESSION['user']) && !empty($_SESSION['user']['role'])) {
    if ($_SESSION['user']['role'] === 'pemilik') {
        header('Location: /?page=dashboard_pemilik');
    } elseif ($_SESSION['user']['role'] === 'penghuni') {
        header('Location: /?page=dashboard_penghuni');
    }
    exit;
}

if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Masuk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Link CSS Modular -->
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="dashboard-body" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; padding: 20px;">

    <div class="login-card">
        <h1 class="brand-title">SITEMAN - KOS</h1>
        <p class="brand-subtitle">
            <?= $role === 'pemilik' ? 'Masuk sebagai Pemilik Kos' : 'Masuk menggunakan Nomor Kamar'; ?>
        </p>

        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Form action diarahkan langsung ke file /api/auth.php -->
        <form action="/api/auth.php" method="POST" id="loginForm">
            <input type="hidden" name="role" value="<?= htmlspecialchars($role); ?>">

            <?php if ($role === 'penghuni'): ?>
                <div class="input-wrapper">
                    <i class="fa-solid fa-house input-icon"></i>
                    <input type="text" inputmode="numeric" pattern="[0-9]+" name="number"
                           class="input-field" placeholder="Nomor Kamar" required autocomplete="off">
                </div>
            <?php endif; ?>

            <div class="input-wrapper">
                <i class="fa-solid fa-key input-icon"></i>
                <input type="password" pattern="[0-9]{6}" inputmode="numeric"
                       minlength="6" maxlength="6" autocomplete="off" required
                       name="pin" class="input-field" placeholder="PIN (6 Digit)">
            </div>
            <!-- link reset pw -->
            <a href="/?page=reset" class="link-lupa">Lupa password?</a>

            <button type="submit" class="btn-submit" id="submitBtn">
                <span class="btn-text">Kirim</span>
            </button>
        </form>

        <p class="role-section-text"><strong>Klik</strong> disini sebagai:</p>

        <div class="role-selection">
            <a href="/?page=login&role=pemilik"
               class="btn-role <?= $role === 'pemilik' ? 'active' : ''; ?>">Pemilik Kos</a>
            <a href="/?page=login&role=penghuni"
               class="btn-role <?= $role === 'penghuni' ? 'active' : ''; ?>">Penghuni Kos</a>
        </div>
    </div>
    

    <script>
        document.getElementById('loginForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.querySelector('.btn-text').textContent = 'Memproses...';
            // Hindari disabled = true seketika agar request POST tidak digugurkan oleh browser
            setTimeout(() => {
                btn.disabled = true;
            }, 10);
        });
    </script>

</body>
</html>