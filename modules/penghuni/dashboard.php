<?php

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'penghuni') {
    $_SESSION['login_error'] = 'Anda harus masuk terlebih dahulu!';
    header("Location: index.php?page=login&role=penghuni");
    exit;
}

$pageTitle = "Dashboard Penghuni - Siteman-Kos";

// Data dummy nama penghuni
$nama_penghuni = $_SESSION['user']['nama'] ?? header("Location: index.php?page=login&role=penghuni");;

// Data dummy tagihan
$tagihan = [
    'sewa' => [
        'status' => 'belum_lunas',
        'tanggal_jatuh_tempo' => '2026-10-11',
    ],
    'iuran' => [
        'status' => 'belum_lunas',
        'tanggal_jatuh_tempo' => '2026-10-11',
    ],
];

function formatTanggal(?string $tanggal): string {
    if (!$tanggal) return '-';
    $bulan = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
              7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
    $ts = strtotime($tanggal);
    return date('D', $ts) . ', ' . date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Dashboard</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>
    <div class="app-shell">

    <header class="topbar">
        <span class="brand">SITEMAN&nbsp;-&nbsp;KOS</span>
        <span class="badge-name"><?= htmlspecialchars($nama_penghuni) ?></span>
    </header>

<div class="page-content">
     <?php foreach (['sewa' => 'Pembayaran Sewa', 'iuran' => 'Pembayaran Iuran'] as $jenis => $label): ?>
        <div class="tagihan-card">
            <span class="label"><?= $label ?></span>
            <div class="row">
                <span>Status Pembayaran</span>
                <?php $t = $tagihan[$jenis]; ?>
                <?php if ($t && $t['status'] === 'lunas'): ?>
                    <span class="status-lunas">Lunas</span>
                <?php else: ?>
                    <span class="status-belum">Belum Lunas</span>
                <?php endif; ?>
            </div>
            <div class="row">
                <span>Tenggat Pembayaran</span>
                <span><?= formatTanggal($t['tanggal_jatuh_tempo'] ?? null) ?></span>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="menu-grid">
    <a href="?page=penghuni-tagihan" class="menu-card">
        <div class="icon">&#128179;</div>
        <div class="title">Tagihan & Riwayat &amp; Riwayat</div>
    </a>
    <a href="?page=penghuni-peraturan" class="menu-card">
        <div class="icon">&#128220;</div>
        <div class="title">Peraturan Kos</div>
    </a>
    <a href="?page=penghuni-chat" class="menu-card">
        <div class="icon">&#128172;</div>
        <div class="title">Chat Penghuni</div>
    </a>
</div>