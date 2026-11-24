<?php
/**
- HELPER PENGINGAT JATUH TEMPO IURAN, MASA TENGGANG (+7 HARI), & AUTO-FREEZE IURAN
- Mendukung auto-rollover periode saat ganti bulan, akumulasi tunggakan lintas periode, cicilan, cache session 30 detik, dan prioritas pengajuan janji bayar.
- Lokasi: modules/shared/handler/iuran_reminder_helper.php
 */

if (!function_exists('bersihkanCacheNotifSiteman')) {
    function bersihkanCacheNotifSiteman(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        unset(
            $_SESSION['cache_sewa_all'],
            $_SESSION['cache_sewa_all_ts'],
            $_SESSION['cache_iuran_all'],
            $_SESSION['cache_iuran_all_ts']
        );
        foreach (array_keys($_SESSION) as $key) {
            if (str_starts_with($key, 'sidebar_notif_')) {
                unset($_SESSION[$key]);
            }
        }
    }
}

if (!function_exists('majukanPeriodeIuranOtomatisJikaGantiBulan')) {
    function majukanPeriodeIuranOtomatisJikaGantiBulan(PDO $pdo, array $ki): array
    {
        if (empty($ki['id']) || empty($ki['tanggal_mulai']) || empty($ki['tanggal_selesai'])) {
            return $ki;
        }

        $todayYm   = date('Y-m');
        $periodeYm = date('Y-m', strtotime($ki['tanggal_mulai']));

        // Jika bulan periode aktif masih sama atau di masa depan, tidak perlu dimajukan
        if ($periodeYm >= $todayYm) {
            return $ki;
        }

        $dtMulai   = new DateTime($ki['tanggal_mulai']);
        $dtSelesai = new DateTime($ki['tanggal_selesai']);
        $selisihHariPeriode = (int)$dtMulai->diff($dtSelesai)->days;

        // Majukan bulan per bulan (+1 month) sampai masuk ke bulan kalender berjalan (Y-m)
        while ($dtMulai->format('Y-m') < $todayYm) {
            $dtMulai->modify('+1 month');
        }

        $tglMulaiBaru   = $dtMulai->format('Y-m-d');
        $tglSelesaiBaru = (clone $dtMulai)->modify("+{$selisihHariPeriode} days")->format('Y-m-d');

        $stmtUpd = $pdo->prepare("
            UPDATE kategori_iuran
            SET tanggal_mulai = :tgl_m,
                tanggal_selesai = :tgl_s
            WHERE id = :id
        ");
        $stmtUpd->execute([
            ':tgl_m' => $tglMulaiBaru,
            ':tgl_s' => $tglSelesaiBaru,
            ':id'    => (int)$ki['id'],
        ]);

        // Reset flag reminder untuk tagihan yang masih belum lunas agar reminder periode baru bisa terkirim
        $pdo->exec("
            UPDATE tagihan
            SET reminder_sent_by = NULL, reminder_sent_at = NULL, reminder_fase = NULL
            WHERE kategori_iuran_id IS NOT NULL
              AND LOWER(TRIM(status_tagihan)) <> 'lunas'
        ");

        bersihkanCacheNotifSiteman();

        $ki['tanggal_mulai']   = $tglMulaiBaru;
        $ki['tanggal_selesai'] = $tglSelesaiBaru;

        return $ki;
    }
}

if (!function_exists('sinkronTagihanIuranPeriodeAktif')) {
    function sinkronTagihanIuranPeriodeAktif(PDO $pdo, array $ki): void
    {
        if (empty($ki['id']) || empty($ki['tanggal_selesai'])) {
            return;
        }

        $kiId       = (int)$ki['id'];
        $tglMulai   = $ki['tanggal_mulai'] ?? date('Y-m-01');
        $tglSelesai = $ki['tanggal_selesai'];
        $periodeYm  = date('Y-m', strtotime($tglSelesai));

        // 0. Jika admin sempat menggeser periode ke bulan depan lalu mengembalikannya ke bulan lebih awal,
        //    hapus tagihan bulan setelah periode aktif yang belum lunas & belum memiliki riwayat pembayaran.
        $stmtCleanFuture = $pdo->prepare("
            DELETE FROM tagihan t
            WHERE t.kategori_iuran_id = :ki_id
              AND TO_CHAR(t.tanggal_jatuh_tempo, 'YYYY-MM') > :periode_ym
              AND LOWER(TRIM(COALESCE(t.status_tagihan, 'belum_bayar'))) <> 'lunas'
              AND NOT EXISTS (
                  SELECT 1 FROM pembayaran p WHERE p.tagihan_id = t.id
              )
        ");
        $stmtCleanFuture->execute([
            ':ki_id'      => $kiId,
            ':periode_ym' => $periodeYm,
        ]);

        // 1. Jika di bulan & tahun yang sama (YYYY-MM) sudah pernah terbit tagihan,
        //    perbarui tanggal awal & jatuh temponya (berguna saat admin mengedit tanggal periode berjalan)
        $stmtUpdPeriode = $pdo->prepare("
            UPDATE tagihan
            SET tanggal_awal_tagihan = :tgl_m,
                tanggal_jatuh_tempo  = :tgl_s
            WHERE kategori_iuran_id = :ki_id
              AND TO_CHAR(tanggal_jatuh_tempo, 'YYYY-MM') = :periode_ym
        ");
        $stmtUpdPeriode->execute([
            ':tgl_m'      => $tglMulai,
            ':tgl_s'      => $tglSelesai,
            ':ki_id'      => $kiId,
            ':periode_ym' => $periodeYm,
        ]);

        // 2. Terbitkan tagihan baru untuk penghuni wajib bayar yang belum punya tagihan di bulan (YYYY-MM) ini
        $stmtSync = $pdo->prepare("
            INSERT INTO tagihan (penghuni_kamar_id, kategori_iuran_id, tanggal_awal_tagihan, tanggal_jatuh_tempo, status_tagihan)
            SELECT pk.id, :ki_id, :tgl_m, :tgl_s, 'belum_bayar'
            FROM penghuni_kamar pk
            JOIN penghuni p ON p.id = pk.penghuni_id
            JOIN kamar k ON k.id = pk.kamar_id
            WHERE LOWER(TRIM(k.status_kamar)) = 'terisi'
              AND LOWER(TRIM(COALESCE(p.status_akun, 'aktif'))) IN ('aktif', 'freeze')
              AND pk.tanggal_masuk <= (:tgl_m_batas::date - INTERVAL '7 days')
              AND NOT EXISTS (
                  SELECT 1 FROM tagihan t
                  WHERE t.penghuni_kamar_id = pk.id
                    AND t.kategori_iuran_id = :ki_id_cek
                    AND TO_CHAR(t.tanggal_jatuh_tempo, 'YYYY-MM') = :periode_ym_cek
              )
        ");
        $stmtSync->execute([
            ':ki_id'          => $kiId,
            ':tgl_m'          => $tglMulai,
            ':tgl_s'          => $tglSelesai,
            ':tgl_m_batas'    => $tglMulai,
            ':ki_id_cek'      => $kiId,
            ':periode_ym_cek' => $periodeYm,
        ]);
    }
}

if (!function_exists('evaluasiStatusIuranSemuaKamar')) {
    function evaluasiStatusIuranSemuaKamar(PDO $pdo): array
    {
        static $memo = null;
        $isGetRequest = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET');

        if ($isGetRequest && $memo !== null) {
            return $memo;
        }

        date_default_timezone_set('Asia/Jakarta');
        $today = new DateTimeImmutable('today');

        try {
            $ki = $pdo->query("
                SELECT id, nama_iuran, nominal, tanggal_mulai, tanggal_selesai 
                FROM kategori_iuran 
                ORDER BY id ASC 
                LIMIT 1
            ")->fetch(PDO::FETCH_ASSOC);

            if (!$ki || empty($ki['tanggal_selesai'])) {
                $memo = [];
                return [];
            }

            // Pastikan periode otomatis maju ke bulan berjalan jika sudah ganti bulan
            $ki = majukanPeriodeIuranOtomatisJikaGantiBulan($pdo, $ki);

            if ($isGetRequest
                && isset($_SESSION['cache_iuran_all'], $_SESSION['cache_iuran_all_ts'])
                && (time() - (int)$_SESSION['cache_iuran_all_ts']) < 30
            ) {
                $memo = $_SESSION['cache_iuran_all'];
                return $memo;
            }

            sinkronTagihanIuranPeriodeAktif($pdo, $ki);

            $nominalAktif = (float)$ki['nominal'];

            $sql = "
                SELECT
                    k.id AS kamar_id,
                    k.no_kamar,
                    pk.id AS penghuni_kamar_id,
                    p.id AS penghuni_id,
                    p.nama AS nama_penghuni,
                    COALESCE(p.no_wa, '') AS no_wa,
                    COALESCE(p.is_ketua_kos, false) AS is_ketua_kos,
                    LOWER(TRIM(COALESCE(p.status_akun, 'aktif'))) AS status_akun,
                    LOWER(TRIM(COALESCE(p.freeze_reason, ''))) AS freeze_reason,
                    MIN(t.tanggal_jatuh_tempo) AS jatuh_tempo_tertua,
                    MAX(t.tanggal_janji_bayar) AS tanggal_janji_bayar,
                    MAX(t.catatan_tunggakan) AS catatan_tunggakan,
                    COUNT(t.id) AS jumlah_periode_nunggak,
                    COALESCE(SUM(GREATEST(0, :nom_aktif - COALESCE(bayar_ok.total_diterima, 0))), 0) AS total_tunggakan,
                    COALESCE(SUM(COALESCE(bayar_pend.jml_pending, 0)), 0) AS jumlah_pending
                FROM kamar k
                INNER JOIN penghuni_kamar pk ON pk.kamar_id = k.id
                INNER JOIN penghuni p ON p.id = pk.penghuni_id
                INNER JOIN tagihan t ON t.penghuni_kamar_id = pk.id
                    AND t.kategori_iuran_id IS NOT NULL
                    AND LOWER(TRIM(COALESCE(t.status_tagihan, 'belum_bayar'))) <> 'lunas'
                LEFT JOIN (
                    SELECT tagihan_id, SUM(jumlah_bayar) AS total_diterima
                    FROM pembayaran
                    WHERE LOWER(TRIM(status_verifikasi)) IN ('diterima', 'terverifikasi', 'lunas')
                    GROUP BY tagihan_id
                ) bayar_ok ON bayar_ok.tagihan_id = t.id
                LEFT JOIN (
                    SELECT tagihan_id, COUNT(*) AS jml_pending
                    FROM pembayaran
                    WHERE LOWER(TRIM(status_verifikasi)) = 'pending'
                    GROUP BY tagihan_id
                ) bayar_pend ON bayar_pend.tagihan_id = t.id
                WHERE LOWER(TRIM(k.status_kamar)) = 'terisi'
                  AND LOWER(TRIM(COALESCE(p.status_akun, 'aktif'))) IN ('aktif', 'freeze')
                GROUP BY
                    k.id, k.no_kamar, pk.id, p.id, p.nama, p.no_wa, p.is_ketua_kos, p.status_akun, p.freeze_reason
                HAVING COALESCE(SUM(GREATEST(0, :nom_aktif_hav - COALESCE(bayar_ok.total_diterima, 0))), 0) > 0
                ORDER BY
                    CASE WHEN MAX(t.tanggal_janji_bayar) IS NOT NULL THEN 0 ELSE 1 END ASC,
                    NULLIF(regexp_replace(k.no_kamar, '\D', '', 'g'), '')::INTEGER ASC NULLS LAST
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom_aktif'     => $nominalAktif,
                ':nom_aktif_hav' => $nominalAktif,
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtFreezeIuran = $pdo->prepare("
                UPDATE penghuni 
                SET status_akun = 'freeze', freeze_reason = 'iuran' 
                WHERE id = :pid
            ");
            $hasil = [];

            foreach ($rows as $row) {
                $totalTunggakan = (float)$row['total_tunggakan'];
                $isPending      = ((int)$row['jumlah_pending'] > 0);

                if ($totalTunggakan <= 0 || $isPending) {
                    continue;
                }

                $dtSelesai     = new DateTimeImmutable($row['jatuh_tempo_tertua']);
                $dtBatasFreeze = $dtSelesai->modify('+7 days');

                if (!empty($row['tanggal_janji_bayar'])) {
                    $dtJanji = new DateTimeImmutable($row['tanggal_janji_bayar']);
                    if ($dtJanji > $dtBatasFreeze) {
                        $dtBatasFreeze = $dtJanji;
                    }
                }

                $diffJatuhTempo = (int)$today->diff($dtSelesai)->format('%r%a');
                $sisaHariFreeze = (int)$today->diff($dtBatasFreeze)->format('%r%a');
                $penghuniId     = (int)$row['penghuni_id'];
                $statusAkun     = $row['status_akun'];
                $freezeReason   = $row['freeze_reason'];
                $jmlPeriode     = (int)$row['jumlah_periode_nunggak'];

                $fase         = null;
                $labelStatus  = '';
                $pesanRingkas = '';

                if ($sisaHariFreeze < 0 || ($statusAkun === 'freeze' && $freezeReason === 'iuran')) {
                    if ($statusAkun !== 'freeze') {
                        $stmtFreezeIuran->execute([':pid' => $penghuniId]);
                        $statusAkun = 'freeze';
                    }
                    $fase         = 'freeze';
                    $labelStatus  = 'Akun Dibekukan (Iuran)';
                    $pesanRingkas = "Akumulasi {$jmlPeriode} periode {$ki['nama_iuran']} melewati batas tenggang ({$dtBatasFreeze->format('d/m/Y')}).";
                } elseif ($diffJatuhTempo < 0) {
                    $hariTelat    = abs($diffJatuhTempo);
                    $fase         = 'tenggang';
                    $labelStatus  = "Iuran Telat {$hariTelat} Hari (Sisa {$sisaHariFreeze} hari)";
                    $pesanRingkas = "Masa tenggang {$ki['nama_iuran']} ({$jmlPeriode} periode) hingga {$dtBatasFreeze->format('d/m/Y')}.";
                } elseif ($diffJatuhTempo === 0) {
                    $fase         = 'pengingat';
                    $labelStatus  = 'Hari H Jatuh Tempo Iuran';
                    $pesanRingkas = "Batas akhir pembayaran {$ki['nama_iuran']} hari ini ({$dtSelesai->format('d/m/Y')}).";
                } elseif ($diffJatuhTempo <= 2) {
                    $fase         = 'pengingat';
                    $labelStatus  = "H-{$diffJatuhTempo} Jatuh Tempo Iuran";
                    $pesanRingkas = "Batas pembayaran {$ki['nama_iuran']} {$diffJatuhTempo} hari lagi ({$dtSelesai->format('d/m/Y')}).";
                }

                if ($fase !== null) {
                    $row['jenis_tunggakan']      = 'iuran';
                    $row['status_akun']          = $statusAkun;
                    $row['fase']                 = $fase;
                    $row['nama_iuran']           = $ki['nama_iuran'];
                    $row['total_tunggakan']      = $totalTunggakan;
                    $row['jumlah_bulan_nunggak'] = $jmlPeriode;
                    $row['diff_jatuh_tempo']     = $diffJatuhTempo;
                    $row['sisa_hari_freeze']     = $sisaHariFreeze;
                    $row['jatuh_tempo_fmt']      = $dtSelesai->format('d/m/Y');
                    $row['batas_freeze_fmt']     = $dtBatasFreeze->format('d/m/Y');
                    $row['label_status']         = $labelStatus;
                    $row['pesan_ringkas']        = $pesanRingkas;
                    $row['wa_pengingat_url']     = buatLinkWaPengingatIuran($row);
                    $hasil[] = $row;
                }
            }

            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['cache_iuran_all']    = $hasil;
                $_SESSION['cache_iuran_all_ts'] = time();
            }

            $memo = $hasil;
            return $hasil;
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('evaluasiStatusIuranPenghuni')) {
    function evaluasiStatusIuranPenghuni(PDO $pdo, int $penghuniId): ?array
    {
        if ($penghuniId <= 0) return null;

        $semuaIuran = evaluasiStatusIuranSemuaKamar($pdo);
        foreach ($semuaIuran as $item) {
            if ((int)$item['penghuni_id'] === $penghuniId) {
                return $item;
            }
        }

        return null;
    }
}

if (!function_exists('buatLinkWaPengingatIuran')) {
    function buatLinkWaPengingatIuran(array $data): string
    {
        $nomor = preg_replace('/\D/', '', (string)($data['no_wa'] ?? ''));
        if ($nomor === '') return '#';
        if (str_starts_with($nomor, '0')) {
            $nomor = '62' . substr($nomor, 1);
        }

        $nama       = $data['nama_penghuni'] ?? 'Penghuni';
        $kamar      = $data['no_kamar'] ?? '-';
        $namaIuran  = $data['nama_iuran'] ?? 'Iuran Kas Bersama';
        $nominalFmt = 'Rp ' . number_format((float)($data['total_tunggakan'] ?? 0), 0, ',', '.');
        $batasFmt   = $data['batas_freeze_fmt'] ?? '-';
        $fase       = $data['fase'] ?? 'pengingat';

        if ($fase === 'freeze') {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}),\n\n"
                   . "Akun SITEMAN - KOS Anda saat ini *dibekukan (Freeze)* karena sisa tunggakan *{$namaIuran}* sebesar *{$nominalFmt}* telah melewati batas masa tenggang (+7 hari).\n\n"
                   . "Silakan balas pesan ini untuk konfirmasi rencana pelunasan agar akun dapat kami aktifkan kembali. Terima kasih.\n\n"
                   . "_Pengurus / Pemilik SITEMAN - KOS_";
        } else {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}),\n\n"
                   . "Mengingatkan sisa tagihan *{$namaIuran}* (*{$data['label_status']}*) sebesar *{$nominalFmt}*.\n"
                   . "Mohon segera melakukan pembayaran (lunas/cicilan) sebelum *{$batasFmt}* agar akun SITEMAN - KOS Anda tetap aktif. Terima kasih.\n\n"
                   . "_Pengurus / Pemilik SITEMAN - KOS_ ";
        }

        return "https://wa.me/{$nomor}?text=" . rawurlencode($pesan);
    }
}

if (!function_exists('buatLinkWaPermohonanUnfreezeIuran')) {
    function buatLinkWaPermohonanUnfreezeIuran(PDO $pdo, array $dataPenghuni, string $tanggalJanjiInput = ''): string
    {
        $penghuniId = (int)($dataPenghuni['penghuni_id'] ?? 0);
        $noWaTujuan = '';
        $sapaan     = 'Kak Ketua Kos / Bapak Ibu Pemilik Kos';

        try {
            $stmtPj = $pdo->prepare("
                SELECT no_wa, nama 
                FROM penghuni 
                WHERE is_ketua_kos = true 
                  AND LOWER(TRIM(COALESCE(status_akun, 'aktif'))) = 'aktif'
                  AND id != :pid
                LIMIT 1
            ");
            $stmtPj->execute([':pid' => $penghuniId]);
            $pjRow = $stmtPj->fetch(PDO::FETCH_ASSOC);

            if ($pjRow && !empty($pjRow['no_wa'])) {
                $noWaTujuan = preg_replace('/\D/', '', (string)$pjRow['no_wa']);
                $sapaan     = "Kak {$pjRow['nama']} (Ketua Kos)";
            } else {
                $stmtPem = $pdo->query("SELECT no_wa FROM pemilik ORDER BY id ASC LIMIT 1");
                $noWaTujuan = preg_replace('/\D/', '', (string)$stmtPem->fetchColumn());
                $sapaan     = 'Bapak/Ibu Pemilik Kos';
            }
        } catch (Throwable $e) {
            $noWaTujuan = '';
        }

        if ($noWaTujuan === '') return '#';
        if (str_starts_with($noWaTujuan, '0')) {
            $noWaTujuan = '62' . substr($noWaTujuan, 1);
        }

        $nama       = $dataPenghuni['nama_penghuni'] ?? 'Penghuni';
        $kamar      = $dataPenghuni['no_kamar'] ?? '-';
        $namaIuran  = $dataPenghuni['nama_iuran'] ?? 'Iuran Kas Bersama';
        $nominalFmt = 'Rp ' . number_format((float)($dataPenghuni['total_tunggakan'] ?? 0), 0, ',', '.');
        $tglJanji   = $tanggalJanjiInput !== '' ? date('d/m/Y', strtotime($tanggalJanjiInput)) : '[Isi Tanggal Bayar]';

        $pesan = "Halo {$sapaan},\n\n"
               . "Saya *{$nama}* penghuni *Kamar {$kamar}*. Akun SITEMAN - KOS saya saat ini ter-freeze karena sisa tunggakan *{$namaIuran}* sebesar *{$nominalFmt}*.\n\n"
               . "Saya memohon kebijaksanaannya untuk membuka akses akun saya sementara, dan saya berjanji akan melunasi iuran tersebut pada tanggal *{$tglJanji}*.\n\n"
               . "Terima kasih banyak atas pengertiannya \u{1F64F}.";

        return "https://wa.me/{$noWaTujuan}?text=" . rawurlencode($pesan);
    }
}