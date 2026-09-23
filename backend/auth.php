<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../public/index.php?page=login");
    exit;
}

$role   = $_POST['role'] ?? 'penghuni';
$pin    = trim($_POST['pin'] ?? '');
$number = trim($_POST['number'] ?? '');

// Dummy Data: Kamar 101 (Penghuni Biasa), Kamar 102 (PJ Kost)
$dummy_penghuni = [
    '101' => [
        'pin'   => '654321',
        'nama'  => 'Mas Rafi',
        'is_pj' => false,
    ],
    '102' => [
        'pin'   => '112233',
        'nama'  => 'Mas Dafa',
        'is_pj' => true,
    ],
];

if ($role === 'pemilik') {
    if ($pin === '123456') {
        $_SESSION['user'] = [
            'nama' => 'Bapak Kos',
            'role' => 'pemilik',
        ];
        header("Location: ../public/index.php?page=dashboard-pemilik");
        exit;
    } else {
        $_SESSION['login_error'] = 'PIN Pemilik salah!';
        header("Location: ../public/index.php?page=login&role=pemilik");
        exit;
    }
} else {
    if (empty($number) || empty($pin)) {
        $_SESSION['login_error'] = 'Nomor Kamar dan PIN wajib diisi!';
        header("Location: ../public/index.php?page=login&role=penghuni");
        exit;
    }

    if (isset($dummy_penghuni[$number]) && $dummy_penghuni[$number]['pin'] === $pin) {
        $userData = $dummy_penghuni[$number];
        $_SESSION['user'] = [
            'nama'   => $userData['nama'],
            'number' => $number,
            'role'   => 'penghuni',
            'is_pj'  => $userData['is_pj'],
        ];
        header("Location: ../public/index.php?page=dashboard-penghuni");
        exit;
    } else {
        $_SESSION['login_error'] = 'Nomor kamar atau PIN salah!';
        header("Location: ../public/index.php?page=login&role=penghuni");
        exit;
    }
}