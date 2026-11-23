<?php
// modules/pemilik/handler/chat_handlers.php
require_once __DIR__ . '/../../../config/init.php';
/** @var PDO $pdo */

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

        $nama     = $p['nama'] ?? 'Penghuni';
        $kamar    = $p['kamar'] ?? '-';
        $pengirim = ($role_pengirim === 'pemilik') ? 'Pemilik SITEMAN - KOS' : 'Penghuni / Pengurus SITEMAN - KOS';

        $pesan = "Halo *{$nama}* (Kamar {$kamar}), salam dari {$pengirim}. 🙏";
        return "https://wa.me/{$nomor}?text=" . rawurlencode($pesan);
    }
}

$penghuniKos = [];

if (isset($pdo) && ($pdo instanceof PDO)) {
    try {
        $sql = "
            SELECT 
                p.id,
                p.nama,
                p.is_ketua_kos,
                COALESCE(k.no_kamar, '-') AS kamar,
                COALESCE(p.no_wa, '-') AS no_wa
            FROM penghuni p
            LEFT JOIN LATERAL (
                SELECT pk.kamar_id
                FROM penghuni_kamar pk
                JOIN kamar km ON km.id = pk.kamar_id
                WHERE pk.penghuni_id = p.id
                  AND LOWER(TRIM(COALESCE(km.status_kamar, 'kosong'))) = 'terisi'
                ORDER BY pk.id DESC
                LIMIT 1
            ) pk_aktif ON true
            LEFT JOIN kamar k ON pk_aktif.kamar_id = k.id
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