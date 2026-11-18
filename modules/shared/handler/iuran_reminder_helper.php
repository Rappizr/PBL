<?php
/**
 * HELPER PENGINGAT JATUH TEMPO IURAN, MASA TENGGANG (+7 HARI), & AUTO-FREEZE IURAN
 * Lokasi: modules/shared/handler/iuran_reminder_helper.php
 */

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

            $dtSelesai      = new DateTimeImmutable($ki['tanggal_selesai']);
            $dtBatasDefault = $dtSelesai->modify('+7 days');

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
                    t.id AS tagihan_id,
                    t.tanggal_janji_bayar,
                    t.catatan_tunggakan,
                    LOWER(TRIM(COALESCE(t.status_tagihan, 'belum_bayar'))) AS status_tagihan,
                    LOWER(TRIM(COALESCE(pem.status_verifikasi, ''))) AS status_verifikasi
                FROM kamar k
                INNER JOIN penghuni_kamar pk ON pk.kamar_id = k.id
                INNER JOIN penghuni p ON p.id = pk.penghuni_id
                LEFT JOIN tagihan t ON t.penghuni_kamar_id = pk.id AND t.kategori_iuran_id = :ki_id
                LEFT JOIN (
                    SELECT tagihan_id, MAX(id) AS max_id FROM pembayaran GROUP BY tagihan_id
                ) lp ON lp.tagihan_id = t.id
                LEFT JOIN pembayaran pem ON pem.id = lp.max_id
                WHERE LOWER(TRIM(k.status_kamar)) = 'terisi'
                  AND LOWER(TRIM(COALESCE(p.status_akun, 'aktif'))) IN ('aktif', 'freeze')
                ORDER BY NULLIF(regexp_replace(k.no_kamar, '\D', '', 'g'), '')::INTEGER ASC NULLS LAST
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':ki_id' => $ki['id']]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtFreezeIuran = $pdo->prepare("
                UPDATE penghuni 
                SET status_akun = 'freeze', freeze_reason = 'iuran' 
                WHERE id = :pid
            ");
            $hasil = [];

            foreach ($rows as $row) {
                $isLunas   = ($row['status_tagihan'] === 'lunas' && $row['status_verifikasi'] === 'diterima');
                $isPending = ($row['status_verifikasi'] === 'pending');

                if ($isLunas || $isPending) {
                    continue;
                }

                $dtBatasFreeze = $dtBatasDefault;
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
                    $pesanRingkas = "Tunggakan {$ki['nama_iuran']} melewati batas tenggang +7 hari ({$dtBatasFreeze->format('d/m/Y')}).";
                } elseif ($diffJatuhTempo < 0) {
                    $hariTelat    = abs($diffJatuhTempo);
                    $fase         = 'tenggang';
                    $labelStatus  = "Iuran Telat {$hariTelat} Hari (Sisa {$sisaHariFreeze} hari)";
                    $pesanRingkas = "Masa tenggang {$ki['nama_iuran']} berjalan hingga {$dtBatasFreeze->format('d/m/Y')}.";
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
                    $row['total_tunggakan']      = (float)$ki['nominal'];
                    $row['jumlah_bulan_nunggak'] = 1;
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
                   . "Akun SITEMAN - KOS Anda saat ini *dibekukan (Freeze)* karena tagihan *{$namaIuran}* sebesar *{$nominalFmt}* telah melewati batas masa tenggang (+7 hari).\n\n"
                   . "Silakan balas pesan ini untuk konfirmasi rencana pelunasan agar akun dapat kami aktifkan kembali. Terima kasih. 🙏\n\n"
                   . "_Pengurus / Pemilik SITEMAN - KOS_";
        } else {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}),\n\n"
                   . "Mengingatkan pembayaran *{$namaIuran}* (*{$data['label_status']}*) sebesar *{$nominalFmt}*.\n"
                   . "Mohon segera melakukan pembayaran sebelum *{$batasFmt}* agar akun SITEMAN - KOS Anda tetap aktif. Terima kasih. 🙏\n\n"
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
               . "Saya *{$nama}* penghuni *Kamar {$kamar}*. Akun SITEMAN - KOS saya saat ini ter-freeze karena tunggakan *{$namaIuran}* sebesar *{$nominalFmt}*.\n\n"
               . "Saya memohon kebijaksanaannya untuk membuka akses akun saya sementara, dan saya berjanji akan melunasi iuran tersebut pada tanggal *{$tglJanji}*.\n\n"
               . "Terima kasih banyak atas pengertiannya. 🙏";

        return "https://wa.me/{$noWaTujuan}?text=" . rawurlencode($pesan);
    }
}