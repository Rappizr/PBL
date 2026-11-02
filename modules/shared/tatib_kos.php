<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulasi data peraturan kos (bisa diubah dengan koneksi database nantinya)
if (!isset($_SESSION['daftar_peraturan'])) {
    $_SESSION['daftar_peraturan'] = [
        [
            'id'        => 1,
            'judul'     => 'Jam Malam & Keamanan',
            'deskripsi' => 'Pintu gerbang ditutup pukul 22.00 WIB. Penghuni yang pulang lewat jam tersebut wajib menghubungi pemilik kos.',
            'icon'      => 'fa-solid fa-clock'
        ],
        [
            'id'        => 2,
            'judul'     => 'Pembayaran Iuran Bulanan',
            'deskripsi' => 'Iuran bulanan dibayar maksimal tanggal 10 setiap bulan. Keterlambatan akan dikenakan denda sesuai kesepakatan.',
            'icon'      => 'fa-solid fa-wallet'
        ],
        [
            'id'        => 3,
            'judul'     => 'Kebersihan Area Bersama',
            'deskripsi' => 'Setiap penghuni wajib menjaga kebersihan kamar dan area kos bersama. Kerusakan fasilitas akibat kelalaian pribadi menjadi tanggung jawab penghuni.',
            'icon'      => 'fa-solid fa-broom'
        ]
    ];
}

// Tangani Aksi CRUD (Tambah, Edit, Hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. TAMBAH PERATURAN
    if ($action === 'tambah') {
        $newItem = [
            'id'        => time(),
            'judul'     => trim($_POST['judul'] ?? ''),
            'deskripsi' => trim($_POST['deskripsi'] ?? ''),
            'icon'      => trim($_POST['icon'] ?? 'fa-solid fa-circle-info')
        ];
        $_SESSION['daftar_peraturan'][] = $newItem;
        header("Location: /?page=peraturan_kost");
        exit;
    }

    // 2. EDIT PERATURAN
    if ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        foreach ($_SESSION['daftar_peraturan'] as &$p) {
            if ($p['id'] === $id) {
                $p['judul']     = trim($_POST['judul'] ?? '');
                $p['deskripsi'] = trim($_POST['deskripsi'] ?? '');
                $p['icon']      = trim($_POST['icon'] ?? '');
                break;
            }
        }
        unset($p);
        header("Location: /?page=peraturan_kost");
        exit;
    }

    // 3. HAPUS PERATURAN
    if ($action === 'hapus') {
        $id = intval($_POST['id'] ?? 0);
        $_SESSION['daftar_peraturan'] = array_values(array_filter($_SESSION['daftar_peraturan'], function($p) use ($id) {
            return $p['id'] !== $id;
        }));
        header("Location: /?page=peraturan_kost");
        exit;
    }
}
?>