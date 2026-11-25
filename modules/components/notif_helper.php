<?php
// modules/components/notif_helper.php

if (!function_exists('getSitemanSidebarNotif')) {
    function getSitemanSidebarNotif(?PDO $pdo): array
    {
        $initNotif = [
            'verifikasi' => 0,
            'tagihan'    => 0,
            'kamar'      => 0,
            'iuran'      => 0,
        ];

        if (!($pdo instanceof PDO) || empty($_SESSION['user'])) {
            return $initNotif;
        }

        $role_user = $_SESSION['user']['role'] ?? 'penghuni';
        $is_pj     = !empty($_SESSION['user']['is_pj']) || !empty($_SESSION['user']['is_ketua_kos']) || ($role_user === 'pj');
        $uid       = (int)($_SESSION['user']['id'] ?? 0);

        $cacheKey = 'sidebar_notif_' . $role_user . '_' . $uid;
        $isGet    = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET');

        if ($isGet && isset($_SESSION[$cacheKey], $_SESSION[$cacheKey . '_ts']) && (time() - (int)$_SESSION[$cacheKey . '_ts']) < 5) {
            return $_SESSION[$cacheKey];
        }

        $isPemilikInit = ($role_user === 'pemilik');
        $isKetuaInit   = $is_pj;

        try {
            if ($isPemilikInit || $isKetuaInit) {
                $initNotif['verifikasi'] = (int)$pdo->query("
                    SELECT COUNT(*) FROM pembayaran
                    WHERE LOWER(TRIM(COALESCE(status_verifikasi, 'pending'))) = 'pending'
                ")->fetchColumn();

                $initNotif['iuran'] = (int)$pdo->query("
                    SELECT COUNT(*) FROM tagihan t
                    JOIN penghuni_kamar pk ON pk.id = t.penghuni_kamar_id
                    JOIN kamar k ON k.id = pk.kamar_id
                    WHERE t.kategori_iuran_id IS NOT NULL
                      AND LOWER(TRIM(COALESCE(k.status_kamar, 'kosong'))) = 'terisi'
                      AND LOWER(TRIM(COALESCE(t.status_tagihan, 'belum_bayar'))) <> 'lunas'
                ")->fetchColumn();
            }

            if ($isPemilikInit) {
                $initNotif['kamar'] = (int)$pdo->query("
                    SELECT COUNT(DISTINCT k.id)
                    FROM kamar k
                    JOIN penghuni_kamar pk ON pk.kamar_id = k.id
                    JOIN penghuni p ON p.id = pk.penghuni_id
                    LEFT JOIN tagihan t ON t.penghuni_kamar_id = pk.id
                      AND t.kategori_iuran_id IS NULL
                      AND LOWER(TRIM(COALESCE(t.status_tagihan, 'belum_bayar'))) <> 'lunas'
                    WHERE LOWER(TRIM(COALESCE(k.status_kamar, 'kosong'))) = 'terisi'
                      AND (
                          t.id IS NOT NULL
                          OR LOWER(TRIM(COALESCE(p.status_akun, 'aktif'))) = 'freeze'
                      )
                ")->fetchColumn();
            } elseif ($uid > 0) {
                $stmtInitTag = $pdo->prepare("
                    SELECT COUNT(*) FROM tagihan t
                    JOIN penghuni_kamar pk ON pk.id = t.penghuni_kamar_id
                    WHERE pk.penghuni_id = :pid
                      AND LOWER(TRIM(COALESCE(t.status_tagihan, 'belum_bayar'))) <> 'lunas'
                      AND t.tanggal_jatuh_tempo::date <= (CURRENT_DATE + INTERVAL '7 days')
                ");
                $stmtInitTag->execute([':pid' => $uid]);
                $initNotif['tagihan'] = (int)$stmtInitTag->fetchColumn();
            }

            $_SESSION[$cacheKey]         = $initNotif;
            $_SESSION[$cacheKey . '_ts'] = time();
        } catch (Throwable $e) {}

        return $initNotif;
    }
}

if (!function_exists('formatBadgeCount')) {
    function formatBadgeCount(int $n): string
    {
        return $n > 99 ? '99+' : (string)$n;
    }
}