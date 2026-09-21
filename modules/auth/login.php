<?php
$role = $_GET['role'] ?? 'penghuni';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $number = trim($_POST['number'] ?? '');
    $pin = $_POST['pin'] ?? '';
    $selected_role = $_POST['role'] ?? $role;

    if ($selected_role === 'pemilik') {
        $number = 'pemilik'; // Set number to 'pemilik' for pemilik role
    }
    if (empty($number) || empty($pin)) {
        $error = 'Nomor Kamar dan PIN wajib diisi!';
    } else {
        $_SESSION['user'] = [
            'number' => $number,
            'role'   => $selected_role
        ];

        if ($selected_role === 'pemilik') {
            header("Location: index.php?page=dashboard-pemilik");
            exit;
        } else {
            header("Location: index.php?page=dashboard-penghuni");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Masuk</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>

    <div class="login-card">
        <h1 class="brand-title">SITEMAN - KOS</h1>
        <p class="brand-subtitle">Masuk menggunakan Nomor Kamar</p>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="hidden" name="role" value="<?= htmlspecialchars($role); ?>">

            <?php if ($role === 'penghuni'): ?>
                <div class="input-wrapper">
                    <i class="fa-solid fa-home input-icon"></i>
                    <input type="text" inputmode="numeric" pattern="[0-9]" name="number" class="input-field" placeholder="Nomor Kamar" required>
                </div>
            <?php endif; ?>

            <div class="input-wrapper">
                <i class="fa-solid fa-key input-icon"></i>
                <input type="password" pattern="[0-9]{6}" inputmode="numeric" minlength="6" maxlength="6" autocomplete="off" required name="pin" class="input-field" placeholder="PIN" required>
            </div>

            <button type="submit" class="btn-submit">Kirim</button>
        </form>

        <p class="role-section-text"><strong>Klik</strong> disini sebagai:</p>

        <div class="role-selection">
            <a href="index.php?page=login&role=pemilik" class="btn-role <?= $role === 'pemilik' ? 'active' : ''; ?>">Pemilik Kos</a>
            <a href="index.php?page=login&role=penghuni" class="btn-role <?= $role === 'penghuni' ? 'active' : ''; ?>">Penghuni Kos</a>
        </div>
    </div>

</body>

</html>