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