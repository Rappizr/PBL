<?php
/**
 * HELPER PENGINGAT JATUH TEMPO IURAN, MASA TENGGANG (+7 HARI), & AUTO-FREEZE IURAN
 * Mendukung akumulasi tunggakan lintas periode, cicilan, dan prioritas pengajuan janji bayar.
 * Lokasi: modules/shared/handler/iuran_reminder_helper.php
 */

if (!function_exists('sinkronTagihanIuranPeriodeAktif')) {
    function sinkronTagihanIuranPeriodeAktif(PDO $pdo, array $ki): void
    {
        if (empty($ki['id']) || empty($ki['tanggal_selesai'])) {
            return;
        }

        $kiId       = (int)$ki['id'];
        $tglMulai   = $ki['tanggal_mulai'] ?? date('Y-m-01');
        $tglSelesai = $ki['tanggal_selesai'];

        $stmtSync = $pdo->prepare("
            INSERT INTO tagihan (penghuni_kamar_id, kategori_iuran_id, tanggal_awal_tagihan, tanggal_jatuh_tempo, status_tagihan)
            SELECT pk.id, :ki_id, :tgl_m, :tgl_s, 'belum_bayar'
            FROM penghuni_kamar pk
            JOIN penghuni p ON p.id = pk.penghuni_id
            JOIN kamar k ON k.id = pk.kamar_id
            WHERE LOWER(TRIM(k.status_kamar)) = 'terisi'
              AND LOWER(TRIM(COALESCE(p.status_akun, 'aktif'))) IN ('aktif', 'freeze')
              AND NOT EXISTS (
                  SELECT 1 FROM tagihan t
                  WHERE t.penghuni_kamar_id = pk.id
                    AND t.kategori_iuran_id = :ki_id_cek
                    AND t.tanggal_jatuh_tempo = :tgl_s_cek
              )
        ");
        $stmtSync->execute([
            ':ki_id'     => $kiId,
            ':tgl_m'     => $tglMulai,
            ':tgl_s'     => $tglSelesai,
            ':ki_id_cek' => $kiId,
            ':tgl_s_cek' => $tglSelesai,
        ]);
    }
}

if (!function_exists('evaluasiStatusIuranSemuaKamar')) {
    function evaluasiStatusIuranSemuaKamar(PDO $pdo): array
    {
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
                return [];
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
                    WHERE LOWER(TRIM(status_verifikasi)) = 'diterima'
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
                   . "Silakan balas pesan ini untuk konfirmasi rencana pelunasan agar akun dapat kami aktifkan kembali. Terima kasih. 🙏\n\n"
                   . "_Pengurus / Pemilik SITEMAN - KOS_";
        } else {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}),\n\n"
                   . "Mengingatkan sisa tagihan *{$namaIuran}* (*{$data['label_status']}*) sebesar *{$nominalFmt}*.\n"
                   . "Mohon segera melakukan pembayaran (lunas/cicilan) sebelum *{$batasFmt}* agar akun SITEMAN - KOS Anda tetap aktif. Terima kasih. 🙏\n\n"
                   . "_Pengurus / Pemilik SITEMAN - KOS_";
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
               . "Terima kasih banyak atas pengertiannya. 🙏";

        return "https://wa.me/{$noWaTujuan}?text=" . rawurlencode($pesan);
    }
}