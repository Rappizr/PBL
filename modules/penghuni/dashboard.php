<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'penghuni') {
    header("Location: index.php?page=login&role=penghuni");
    exit;
}

$pageTitle = "Dashboard Penghuni - Siteman-Kos";

// Data dummy nama penghuni
$nama_penghuni = 'Mas Rafi';

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
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/tagihan-card.css">
</head>

<body>
    <div class="app-shell">
