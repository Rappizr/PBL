<?php
// modules/pemilik/handler/chat_handlers.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Pastikan koneksi $pdo dimuat
global $pdo;
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
            if (isset($pdo) && ($pdo instanceof PDO)) break;
        }
    }
}

if (!function_exists('buatLinkWaSapaan')) {
    function buatLinkWaSapaan(array $p, string $role_pengirim = 'pemilik'): string
    {
        $nomor = preg_replace('/\D/', '', (string)($p['no_wa'] ?? ''));
        if ($nomor === '' || $nomor === '-') {
            return '#';
        }
        if (str_starts_with($nomor, '0')) {
            $nomor = '62' . substr($nomor, 1);
        }

        $nama  = $p['nama'] ?? 'Penghuni';
        $kamar = $p['kamar'] ?? '-';
        $pengirim = ($role_pengirim === 'pemilik') ? 'Pemilik SITEMAN - KOS' : 'Penghuni / Pengurus SITEMAN - KOS';

        $pesan = "Halo *{$nama}* (Kamar {$kamar}), salam dari {$pengirim}. 🙏";
        return "https://wa.me/{$nomor}?text=" . rawurlencode($pesan);
    }
}

$penghuniKos = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $sql = "
            SELECT 
                p.id,
                p.nama,
                COALESCE(k.no_kamar, '-') AS kamar,
                COALESCE(p.no_wa, '-') AS no_wa
            FROM penghuni p
            LEFT JOIN penghuni_kamar pk ON p.id = pk.penghuni_id
            LEFT JOIN kamar k ON pk.kamar_id = k.id
            WHERE LOWER(COALESCE(p.status_akun, 'aktif')) IN ('aktif', 'freeze')
            ORDER BY 
                NULLIF(regexp_replace(COALESCE(k.no_kamar, '0'), '\D', '', 'g'), '')::INTEGER ASC NULLS LAST,
                p.nama ASC
        ";

        $stmt = $pdo->query($sql);
        $penghuniKos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $penghuniKos = [];
    }
}