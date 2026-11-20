<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$response = [
    'verifikasi' => 0,
    'tagihan'    => 0,
    'kamar'      => 0,
    'iuran'      => 0,
];

if (empty($_SESSION['user'])) {
    echo json_encode($response);
    exit;
}

$user       = $_SESSION['user'];
$role       = strtolower(trim((string)($user['role'] ?? 'penghuni')));
$isPemilik  = ($role === 'pemilik');
$isKetua    = !empty($user['is_ketua_kos']) || !empty($user['is_pj']) || ($role === 'pj');
$penghuniId = (int)($user['id'] ?? 0);

// Pakai cache session yang sama (5 detik) agar tidak menembak DB ganda saat baru pindah halaman
$cacheKey = 'sidebar_notif_' . ($user['role'] ?? 'penghuni') . '_' . $penghuniId;
if (isset($_SESSION[$cacheKey], $_SESSION[$cacheKey . '_ts']) && (time() - (int)$_SESSION[$cacheKey . '_ts']) < 5) {
    echo json_encode($_SESSION[$cacheKey]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

global $pdo;
$db = ($pdo instanceof PDO) ? $pdo : ($conn ?? null);

try {
    if ($db instanceof PDO) {
        // 1. Pemilik & Ketua Kos: Jumlah bukti pembayaran berstatus 'pending'
        if ($isPemilik || $isKetua) {
            $stmtVerif = $db->query("
                SELECT COUNT(*)
                FROM pembayaran
                WHERE LOWER(TRIM(COALESCE(status_verifikasi, 'pending'))) = 'pending'
            ");
            $response['verifikasi'] = (int)$stmtVerif->fetchColumn();
        }

        // 2. Pemilik Kos: Jumlah tagihan sewa jatuh tempo / belum lunas
        if ($isPemilik) {
            $stmtKamar = $db->query("
                SELECT COUNT(*)
                FROM tagihan t
                JOIN penghuni_kamar pk ON pk.id = t.penghuni_kamar_id
                JOIN kamar k ON k.id = pk.kamar_id
                WHERE t.kategori_iuran_id IS NULL
                  AND LOWER(TRIM(COALESCE(k.status_kamar, 'kosong'))) = 'terisi'
                  AND LOWER(TRIM(COALESCE(t.status_tagihan, 'belum_bayar'))) <> 'lunas'
                  AND t.tanggal_jatuh_tempo <= CURRENT_DATE
            ");
            $response['kamar'] = (int)$stmtKamar->fetchColumn();
        }

        // 3. Pemilik & Ketua Kos: Jumlah tagihan iuran bersama yang belum lunas
        if ($isPemilik || $isKetua) {
            $stmtIuran = $db->query("
                SELECT COUNT(*)
                FROM tagihan t
                JOIN penghuni_kamar pk ON pk.id = t.penghuni_kamar_id
                JOIN kamar k ON k.id = pk.kamar_id
                WHERE t.kategori_iuran_id IS NOT NULL
                  AND LOWER(TRIM(COALESCE(k.status_kamar, 'kosong'))) = 'terisi'
                  AND LOWER(TRIM(COALESCE(t.status_tagihan, 'belum_bayar'))) <> 'lunas'
            ");
            $response['iuran'] = (int)$stmtIuran->fetchColumn();
        }

        // 4. Penghuni & Ketua Kos: Jumlah tagihan pribadi yang belum lunas
        if (!$isPemilik && $penghuniId > 0) {
            $stmtTagihan = $db->prepare("
                SELECT COUNT(*)
                FROM tagihan t
                JOIN penghuni_kamar pk ON pk.id = t.penghuni_kamar_id
                WHERE pk.penghuni_id = :pid
                  AND LOWER(TRIM(COALESCE(t.status_tagihan, 'belum_bayar'))) <> 'lunas'
            ");
            $stmtTagihan->execute([':pid' => $penghuniId]);
            $response['tagihan'] = (int)$stmtTagihan->fetchColumn();
        }

        $_SESSION[$cacheKey]         = $response;
        $_SESSION[$cacheKey . '_ts'] = time();
    }
} catch (Throwable $e) {
    // Kembalikan 0 jika koneksi terganggu sesaat
}

echo json_encode($response);