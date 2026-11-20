<?php
/**
 * HELPER PENGINGAT JATUH TEMPO SEWA, MASA TENGGANG (+7 HARI), & AUTO-FREEZE SEWA
 * Mendukung perhitungan sisa tunggakan setelah cicilan diterima serta cache session 30 detik.
 * Lokasi: modules/shared/handler/sewa_reminder_helper.php
 */

if (!function_exists('evaluasiStatusSewaSemuaKamar')) {
    function evaluasiStatusSewaSemuaKamar(PDO $pdo): array
    {
        static $memo = null;
        if ($memo !== null) {
            return $memo;
        }

        $isGetRequest = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET');
        if ($isGetRequest
            && isset($_SESSION['cache_sewa_all'], $_SESSION['cache_sewa_all_ts'])
            && (time() - (int)$_SESSION['cache_sewa_all_ts']) < 30
        ) {
            $memo = $_SESSION['cache_sewa_all'];
            return $memo;
        }

        date_default_timezone_set('Asia/Jakarta');
        $today = new DateTimeImmutable('today');

        $sql = "
            SELECT
                k.id AS kamar_id,
                k.no_kamar,
                pk.id AS penghuni_kamar_id,
                p.id AS penghuni_id,
                p.nama AS nama_penghuni,
                COALESCE(p.no_wa, '') AS no_wa,
                LOWER(TRIM(COALESCE(p.status_akun, 'aktif'))) AS status_akun,
                LOWER(TRIM(COALESCE(p.freeze_reason, ''))) AS freeze_reason,
                MAX(t.tanggal_janji_bayar) AS tanggal_janji_bayar,
                MAX(t.catatan_tunggakan) AS catatan_tunggakan,
                COUNT(t.id) AS jumlah_bulan_nunggak,
                COALESCE(SUM(GREATEST(0, COALESCE(s.nominal, 0) - COALESCE(bayar_ok.total_diterima, 0))), 0) AS total_tunggakan,
                MIN(t.tanggal_jatuh_tempo) AS jatuh_tempo_tertua
            FROM kamar k
            INNER JOIN penghuni_kamar pk ON pk.kamar_id = k.id
            INNER JOIN penghuni p ON p.id = pk.penghuni_id
            LEFT JOIN skema_sewa s ON pk.skema_id = s.id
            LEFT JOIN tagihan t ON t.penghuni_kamar_id = pk.id
                AND t.kategori_iuran_id IS NULL
                AND LOWER(TRIM(t.status_tagihan)) != 'lunas'
            LEFT JOIN (
                SELECT tagihan_id, SUM(jumlah_bayar) AS total_diterima
                FROM pembayaran
                WHERE LOWER(TRIM(status_verifikasi)) IN ('diterima', 'terverifikasi', 'lunas')
                GROUP BY tagihan_id
            ) bayar_ok ON bayar_ok.tagihan_id = t.id
            WHERE LOWER(TRIM(k.status_kamar)) = 'terisi'
              AND LOWER(TRIM(COALESCE(p.status_akun, 'aktif'))) IN ('aktif', 'freeze')
            GROUP BY
                k.id, k.no_kamar, pk.id, p.id, p.nama, p.no_wa, p.status_akun, p.freeze_reason
            ORDER BY NULLIF(regexp_replace(k.no_kamar, '\D', '', 'g'), '')::INTEGER ASC NULLS LAST
        ";

        try {
            $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }

        $hasil = [];
        $stmtFreeze = $pdo->prepare("UPDATE penghuni SET status_akun = 'freeze', freeze_reason = 'sewa' WHERE id = :pid");

        foreach ($rows as $row) {
            $penghuniId     = (int)$row['penghuni_id'];
            $jmlNunggak     = (int)$row['jumlah_bulan_nunggak'];
            $totalTunggakan = (float)$row['total_tunggakan'];
            $statusAkun     = $row['status_akun'];
            $freezeReason   = $row['freeze_reason'];
            $jatuhTempo     = $row['jatuh_tempo_tertua'];
            $janjiBayar     = $row['tanggal_janji_bayar'];

            if ($jmlNunggak <= 0 || $totalTunggakan <= 0 || empty($jatuhTempo)) {
                continue;
            }

            $dtJatuhTempo = new DateTimeImmutable($jatuhTempo);
            $dtBatasFreeze = $dtJatuhTempo->modify('+7 days');
            if (!empty($janjiBayar)) {
                $dtJanji = new DateTimeImmutable($janjiBayar);
                if ($dtJanji > $dtBatasFreeze) {
                    $dtBatasFreeze = $dtJanji;
                }
            }

            $diffJatuhTempo = (int)$today->diff($dtJatuhTempo)->format('%r%a');
            $sisaHariFreeze = (int)$today->diff($dtBatasFreeze)->format('%r%a');

            $fase = null;
            $labelStatus = '';
            $pesanRingkas = '';

            if ($sisaHariFreeze < 0 || ($statusAkun === 'freeze' && $freezeReason === 'sewa')) {
                if ($statusAkun !== 'freeze') {
                    $stmtFreeze->execute([':pid' => $penghuniId]);
                    $statusAkun = 'freeze';
                }
                $fase = 'freeze';
                $labelStatus = 'Akun Dibekukan (Sewa)';
                $pesanRingkas = "Menunggak {$jmlNunggak} periode sewa (melewati batas tenggang).";
            } elseif ($diffJatuhTempo < 0) {
                $hariTelat = abs($diffJatuhTempo);
                $fase = 'tenggang';
                $labelStatus = "Sewa Telat {$hariTelat} Hari (Sisa {$sisaHariFreeze} hari)";
                $pesanRingkas = "Masa tenggang sewa berjalan hingga " . $dtBatasFreeze->format('d/m/Y') . ".";
            } elseif ($diffJatuhTempo === 0) {
                $fase = 'pengingat';
                $labelStatus = 'Hari H Jatuh Tempo Sewa';
                $pesanRingkas = 'Jatuh tempo pembayaran sewa hari ini (' . $dtJatuhTempo->format('d/m/Y') . ').';
            } elseif ($diffJatuhTempo <= 2) {
                $fase = 'pengingat';
                $labelStatus = "H-{$diffJatuhTempo} Jatuh Tempo Sewa";
                $pesanRingkas = "Jatuh tempo sewa {$diffJatuhTempo} hari lagi (" . $dtJatuhTempo->format('d/m/Y') . ').';
            }

            if ($fase !== null) {
                $row['jenis_tunggakan']  = 'sewa';
                $row['status_akun']      = $statusAkun;
                $row['fase']             = $fase;
                $row['label_status']     = $labelStatus;
                $row['pesan_ringkas']    = $pesanRingkas;
                $row['diff_jatuh_tempo'] = $diffJatuhTempo;
                $row['sisa_hari_freeze'] = $sisaHariFreeze;
                $row['batas_freeze_fmt'] = $dtBatasFreeze->format('d/m/Y');
                $row['jatuh_tempo_fmt']  = $dtJatuhTempo->format('d/m/Y');
                $row['wa_pengingat_url'] = buatLinkWaPengingatSewa($row);
                $hasil[] = $row;
            }
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['cache_sewa_all']    = $hasil;
            $_SESSION['cache_sewa_all_ts'] = time();
        }

        $memo = $hasil;
        return $hasil;
    }
}

if (!function_exists('evaluasiStatusSewaPenghuni')) {
    function evaluasiStatusSewaPenghuni(PDO $pdo, int $penghuniId): ?array
    {
        if ($penghuniId <= 0) return null;

        $semuaSewa = evaluasiStatusSewaSemuaKamar($pdo);
        foreach ($semuaSewa as $item) {
            if ((int)$item['penghuni_id'] === $penghuniId) {
                return $item;
            }
        }

        return null;
    }
}

if (!function_exists('buatLinkWaPengingatSewa')) {
    function buatLinkWaPengingatSewa(array $data): string
    {
        $nomor = preg_replace('/\D/', '', (string)($data['no_wa'] ?? ''));
        if ($nomor === '') return '#';
        if (str_starts_with($nomor, '0')) {
            $nomor = '62' . substr($nomor, 1);
        }

        $nama       = $data['nama_penghuni'] ?? 'Penghuni';
        $kamar      = $data['no_kamar'] ?? '-';
        $jmlBulan   = (int)($data['jumlah_bulan_nunggak'] ?? 1);
        $nominalFmt = 'Rp ' . number_format((float)($data['total_tunggakan'] ?? 0), 0, ',', '.');
        $batasFmt   = $data['batas_freeze_fmt'] ?? '-';
        $fase       = $data['fase'] ?? 'pengingat';

        if ($fase === 'freeze') {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}),\n\n"
                   . "Akun SITEMAN - KOS Anda saat ini *dibekukan (Freeze)* karena sisa tunggakan sewa ({$jmlBulan} periode, Total: *{$nominalFmt}*) telah melewati batas masa tenggang.\n\n"
                   . "Silakan balas pesan ini untuk mengonfirmasi rencana pelunasan (tanggal bayar) agar akun dapat kami aktifkan kembali. Terima kasih. 🙏\n\n"
                   . "_Pemilik SITEMAN - KOS_";
        } else {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}),\n\n"
                   . "Mengingatkan sisa tagihan sewa kos Anda (*{$data['label_status']}*) sebesar *{$nominalFmt}*.\n"
                   . "Mohon segera melakukan pembayaran sebelum *{$batasFmt}* agar akun SITEMAN - KOS Anda tetap aktif. Terima kasih. 🙏\n\n"
                   . "_Pemilik SITEMAN - KOS_";
        }

        return "https://wa.me/{$nomor}?text=" . rawurlencode($pesan);
    }
}

if (!function_exists('buatLinkWaPermohonanUnfreeze')) {
    function buatLinkWaPermohonanUnfreeze(PDO $pdo, array $dataPenghuni, string $tanggalJanjiInput = ''): string
    {
        $noWaPemilik = '';
        try {
            $stmt = $pdo->query("SELECT no_wa FROM pemilik ORDER BY id ASC LIMIT 1");
            $noWaPemilik = preg_replace('/\D/', '', (string)$stmt->fetchColumn());
        } catch (Throwable $e) {
            $noWaPemilik = '';
        }

        if ($noWaPemilik === '') return '#';
        if (str_starts_with($noWaPemilik, '0')) {
            $noWaPemilik = '62' . substr($noWaPemilik, 1);
        }

        $nama       = $dataPenghuni['nama_penghuni'] ?? 'Penghuni';
        $kamar      = $dataPenghuni['no_kamar'] ?? '-';
        $jmlBulan   = (int)($dataPenghuni['jumlah_bulan_nunggak'] ?? 1);
        $nominalFmt = 'Rp ' . number_format((float)($dataPenghuni['total_tunggakan'] ?? 0), 0, ',', '.');
        $tglJanji   = $tanggalJanjiInput !== '' ? date('d/m/Y', strtotime($tanggalJanjiInput)) : '[Isi Tanggal Bayar]';

        $pesan = "Halo Bapak/Ibu Pemilik Kos,\n\n"
               . "Saya *{$nama}* penghuni *Kamar {$kamar}*. Akun SITEMAN - KOS saya saat ini ter-freeze karena sisa tunggakan sewa ({$jmlBulan} periode) dengan total *{$nominalFmt}*.\n\n"
               . "Saya memohon kebijaksanaannya untuk membuka akses akun saya sementara, dan saya berjanji akan melunasi tunggakan tersebut pada tanggal *{$tglJanji}*.\n\n"
               . "Terima kasih banyak atas pengertiannya. 🙏";

        return "https://wa.me/{$noWaPemilik}?text=" . rawurlencode($pesan);
    }
}