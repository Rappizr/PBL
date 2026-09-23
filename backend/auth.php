<?php
session_start();

include __DIR__ . '/../config/database.php';

$data_pemilik = $pdo->query("SELECT * FROM pemilik LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$raw_penghuni = $pdo->query("SELECT * FROM penghuni")->fetchAll(PDO::FETCH_ASSOC);
$data_penghuni = [];
foreach ($raw_penghuni as $row) {

    $data_penghuni[$row['nomor_kamar']] = $row;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');
    $selected_role = $_POST['role'] ?? 'penghuni';

    if ($selected_role === 'pemilik') {
        if (empty($pin)) {
            $error = 'PIN Pemilik wajib diisi!';
        } elseif (!$data_pemilik || $pin !== $data_pemilik['pin']) {
            $error = 'PIN Pemilik salah!';
        } else {
            $_SESSION['user'] = [
                'nama' => 'Bapak Kos',
                'role' => 'pemilik'
            ];
            header("Location: ../public/index.php?page=dashboard_pemilik");
            exit;
        }
    } else {
        $number = trim($_POST['number'] ?? '');

        if (empty($number) || empty($pin)) {
            $error = 'Nomor Kamar dan PIN wajib diisi!';
        } elseif (!isset($data_penghuni[$number]) || $data_penghuni[$number]['pin'] !== $pin) {
            $error = 'Nomor Kamar atau PIN tidak sesuai!';
        } else {
            $_SESSION['user'] = [
                'nama'   => $data_penghuni[$number]['nama'],
                'number' => $number,
                'role'   => 'penghuni'
            ];
            header("Location: ../public/index.php?page=dashboard-penghuni");
            exit;
        }
    }


    if ($error) {
        $_SESSION['login_error'] = $error;
        $role = $selected_role;
        header("Location: ../public/index.php?page=login&role=" . urlencode($role));
        exit;
    }
}

header("Location: ../public/index.php?page=login");
exit;