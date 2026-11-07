<?php
// modules/pemilik/handler/chat_handlers.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Pastikan koneksi $pdo dimuat menggunakan pencarian path absolut/fleksibel
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $possible_paths = [
        __DIR__ . '/../../../config/database.php',
        __DIR__ . '/../../config/database.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/database.php',
        dirname(__DIR__, 2) . '/config/database.php',
        dirname(__DIR__, 3) . '/config/database.php'
    ];
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

$penghuniKos = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // Query mengambil seluruh penghuni aktif dan nomor kamarnya jika ada

$sql = "
    SELECT
        p.id,
        p.nama,
        COALESCE(k.no_kamar, '-') AS kamar,
        COALESCE(p.no_wa, '-') AS no_wa
    FROM penghuni p
    LEFT JOIN penghuni_kamar pk
        ON p.id = pk.penghuni_id
    LEFT JOIN kamar k
        ON pk.kamar_id = k.id
        AND k.status_kamar = 'terisi'
    WHERE LOWER(p.status_akun) = 'aktif'
    ORDER BY k.no_kamar ASC, p.nama ASC
";

$stmt = $pdo->query($sql);
$penghuniKos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $penghuniKos = [];
    }
}