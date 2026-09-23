<?php
$role = $_GET['role'] ?? 'penghuni';
$error = '';
$success = '';

// Data dummy
$dummy_pemilik = [
    'pin' => '123456' // PIN akun dummy pemilik
];

$dummy_penghuni = [
    '101' => '654321', // Kamar 101, PIN: 654321
    '102' => '112233'  // Kamar 102, PIN: 112233
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');
    $selected_role = $_POST['role'] ?? $role;

    if ($selected_role === 'pemilik') {
        if (empty($pin)) {
            $error = 'PIN Pemilik wajib diisi!';
        } elseif ($pin !== $dummy_pemilik['pin']) {
            $error = 'PIN Pemilik salah! (Gunakan dummy: 123456)';
        } else {
            $_SESSION['user'] = [
                'nama' => 'Bapak Kos',
                'role' => 'pemilik'
            ];
            header("Location: index.php?page=dashboard-pemilik");
            exit;
        }
    } else {
        $number = trim($_POST['number'] ?? '');

        if (empty($number) || empty($pin)) {
            $error = 'Nomor Kamar dan PIN wajib diisi!';
        } elseif (!isset($dummy_penghuni[$number]) || $dummy_penghuni[$number] !== $pin) {
            $error = 'Nomor Kamar atau PIN tidak sesuai!';
        } else {
            $_SESSION['user'] = [
                'number' => $number,
                'role'   => 'penghuni'
            ];
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
        <p class="brand-subtitle">
            <?= $role === 'pemilik' ? 'Masuk sebagai Pemilik Kos' : 'Masuk menggunakan Nomor Kamar'; ?>
        </p>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="hidden" name="role" value="<?= htmlspecialchars($role); ?>">

            <?php if ($role === 'penghuni'): ?>
                <div class="input-wrapper">
                    <i class="fa-solid fa-home input-icon"></i>
                    <input type="text" inputmode="numeric" pattern="[0-9]+" name="number" class="input-field" placeholder="Nomor Kamar" required>
                </div>
            <?php endif; ?>

            <div class="input-wrapper">
                <i class="fa-solid fa-key input-icon"></i>
                <input type="password" pattern="[0-9]{6}" inputmode="numeric" minlength="6" maxlength="6" autocomplete="off" required name="pin" class="input-field" placeholder="PIN (6 Digit)">
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