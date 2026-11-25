<?php
// modules/penghuni/handler/tagihan_handler.php
require_once __DIR__ . '/../../../config/supabase_storage.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function resolvePenghuniKamarId(PDO $pdo, array $sessionUser): int {
    $pk_id = (int)($sessionUser['penghuni_kamar_id'] ?? 0);
    if ($pk_id > 0) {
        return $pk_id;
    }

    $session_user_id = (int)($sessionUser['id'] ?? ($sessionUser['penghuni_id'] ?? 0));
    $nama_penghuni   = trim($sessionUser['nama'] ?? '');
    $username        = trim($sessionUser['username'] ?? '');

    if ($session_user_id > 0) {
        try {
            $stmtPK = $pdo->prepare("SELECT id FROM penghuni_kamar WHERE penghuni_id = :p_id ORDER BY id DESC LIMIT 1");
            $stmtPK->execute([':p_id' => $session_user_id]);
            $found = (int)$stmtPK->fetchColumn();
            if ($found > 0) return $found;
        } catch (Exception $e) {}
    }

    if (!empty($nama_penghuni)) {
        try {
            $stmtNama = $pdo->prepare("
                SELECT pk.id 
                FROM penghuni_kamar pk 
                JOIN penghuni p ON pk.penghuni_id = p.id 
                WHERE LOWER(TRIM(p.nama)) = LOWER(:nama) 
                ORDER BY pk.id DESC LIMIT 1
            ");
            $stmtNama->execute([':nama' => $nama_penghuni]);
            $found = (int)$stmtNama->fetchColumn();
            if ($found > 0) return $found;
        } catch (Exception $e) {}
    }

    if (!empty($username)) {
        try {
            $stmtUser = $pdo->prepare("
                SELECT pk.id 
                FROM penghuni_kamar pk 
                JOIN penghuni p ON pk.penghuni_id = p.id 
                WHERE LOWER(TRIM(p.username)) = LOWER(:username) 
                ORDER BY pk.id DESC LIMIT 1
            ");
            $stmtUser->execute([':username' => $username]);
            $found = (int)$stmtUser->fetchColumn();
            if ($found > 0) return $found;
        } catch (Exception $e) {}
    }

    return 0;
}

function formatTanggalIndonesia(string $ymd): string {
    if (empty($ymd)) return '-';
    $ts = strtotime($ymd);
    if ($ts === false) return $ymd;

    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $hari  = date('d', $ts);
    $bln   = $bulan[(int)date('n', $ts)] ?? date('F', $ts);
    $tahun = date('Y', $ts);

    return "{$hari} {$bln} {$tahun}";
}

function hitungBerlakuSampai(string $tglMulaiYmd, string $tglJatuhTempoYmd, string $namaSkema, bool $isIuran): string {
    $baseStr = !empty($tglMulaiYmd) ? $tglMulaiYmd : $tglJatuhTempoYmd;
    if (empty($baseStr)) return '-';

    try {
        $dt = new DateTime($baseStr);
        $skemaLower = strtolower(trim($namaSkema));

        if ($isIuran) {
            if (strpos($skemaLower, 'tahun') !== false) {
                $dt->modify('+1 year')->modify('-1 day');
            } else {
                $dt->modify('last day of this month');
            }
            return formatTanggalIndonesia($dt->format('Y-m-d'));
        }

        if (preg_match('/(\d+)\s*tahun/', $skemaLower, $m)) {
            $dt->modify('+' . (int)$m[1] . ' year')->modify('-1 day');
        } elseif (strpos($skemaLower, 'tahunan') !== false) {
            $dt->modify('+1 year')->modify('-1 day');
        } elseif (preg_match('/(\d+)\s*bulan/', $skemaLower, $m)) {
            $dt->modify('+' . (int)$m[1] . ' month')->modify('-1 day');
        } elseif (strpos($skemaLower, 'semester') !== false) {
            $dt->modify('+6 month')->modify('-1 day');
        } elseif (strpos($skemaLower, 'triwulan') !== false) {
            $dt->modify('+3 month')->modify('-1 day');
        } elseif (preg_match('/(\d+)\s*minggu/', $skemaLower, $m)) {
            $dt->modify('+' . (int)$m[1] . ' week')->modify('-1 day');
        } elseif (strpos($skemaLower, 'mingguan') !== false) {
            $dt->modify('+7 day')->modify('-1 day');
        } elseif (preg_match('/(\d+)\s*hari/', $skemaLower, $m)) {
            $dt->modify('+' . (int)$m[1] . ' day')->modify('-1 day');
        } elseif (strpos($skemaLower, 'harian') !== false) {
            // 1 hari penuh
        } else {
            // Default bulanan
            $dt->modify('+1 month')->modify('-1 day');
        }

        return formatTanggalIndonesia($dt->format('Y-m-d'));
    } catch (Exception $e) {
        return !empty($tglJatuhTempoYmd) ? formatTanggalIndonesia($tglJatuhTempoYmd) : '-';
    }
}

function fetchDaftarTagihan(PDO $pdo, int $pk_id): array {
    if ($pk_id <= 0) return [];

    try {
        $sqlFetch = "
            SELECT 
                t.id,
                CASE 
                    WHEN ki.nama_iuran IS NOT NULL THEN ki.nama_iuran || ' (' || TO_CHAR(t.tanggal_jatuh_tempo, 'DD/MM/YY') || ')'
                    ELSE 'Sewa (' || TO_CHAR(t.tanggal_jatuh_tempo, 'DD/MM/YY') || ')'
                END AS jenis,
                COALESCE(ki.nominal, s.nominal, 5500000) AS total_tagihan,
                COALESCE(bayar_ok.total_diterima, 0) AS sudah_dibayar,
                GREATEST(0, COALESCE(ki.nominal, s.nominal, 5500000) - COALESCE(bayar_ok.total_diterima, 0)) AS nominal,
                TO_CHAR(t.tanggal_jatuh_tempo, 'DD-MM-YY') AS jatuh_tempo,
                TO_CHAR(t.tanggal_jatuh_tempo, 'YYYY-MM-DD') AS jatuh_tempo_ymd,
                (t.tanggal_jatuh_tempo::date - CURRENT_DATE) AS sisa_hari,
                ((t.tanggal_jatuh_tempo::date - CURRENT_DATE) > 7) AS is_mendatang,
                t.status_tagihan,
                CASE
                    WHEN COALESCE(pend.total_pending, 0) > 0 THEN 'pending'
                    ELSE LOWER(TRIM(COALESCE(pem.status_verifikasi, '')))
                END AS status_verif,
                COALESCE(pend.total_pending, pem.jumlah_bayar, 0) AS nominal_pending,
                pem.alasan_penolakan
            FROM tagihan t
            JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
            LEFT JOIN skema_sewa s ON COALESCE(t.skema_id, pk.skema_id) = s.id
            LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
            LEFT JOIN (
                SELECT tagihan_id, SUM(jumlah_bayar) AS total_diterima
                FROM pembayaran
                WHERE LOWER(TRIM(status_verifikasi)) IN ('diterima', 'terverifikasi', 'lunas')
                GROUP BY tagihan_id
            ) bayar_ok ON bayar_ok.tagihan_id = t.id
            LEFT JOIN (
                SELECT tagihan_id, SUM(jumlah_bayar) AS total_pending
                FROM pembayaran
                WHERE LOWER(TRIM(status_verifikasi)) = 'pending'
                GROUP BY tagihan_id
            ) pend ON pend.tagihan_id = t.id
            LEFT JOIN (
                SELECT tagihan_id, MAX(id) AS max_pem_id
                FROM pembayaran
                GROUP BY tagihan_id
            ) lp ON lp.tagihan_id = t.id
            LEFT JOIN pembayaran pem ON pem.id = lp.max_pem_id
            WHERE t.penghuni_kamar_id = :pk_id
              AND LOWER(TRIM(t.status_tagihan)) != 'lunas'
            ORDER BY t.tanggal_jatuh_tempo ASC, t.id ASC
        ";
        $stmt = $pdo->prepare($sqlFetch);
        $stmt->execute([':pk_id' => $pk_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function fetchRiwayatTransaksi(PDO $pdo, int $pk_id): array {
    if ($pk_id <= 0) return [];

    try {
        $stmtPid = $pdo->prepare("SELECT penghuni_id FROM penghuni_kamar WHERE id = :pk_id LIMIT 1");
        $stmtPid->execute([':pk_id' => $pk_id]);
        $penghuniId = (int)$stmtPid->fetchColumn();

        $whereUser = ($penghuniId > 0) ? "pk.penghuni_id = :uid" : "pk.id = :uid";
        $uidParam  = ($penghuniId > 0 ? $penghuniId : $pk_id);

        // Ambil rincian tiap cicilan per tagihan_id
        $sqlCicilan = "
            SELECT 
                p.id,
                p.tagihan_id,
                p.jumlah_bayar,
                COALESCE(p.metode_bayar, 'transfer') AS metode_bayar,
                COALESCE(p.no_kwitansi, 'KW-' || p.id) AS no_kwitansi,
                TO_CHAR(COALESCE(p.tanggal_bayar, NOW()), 'YYYY-MM-DD') AS tgl_ymd,
                TO_CHAR(COALESCE(p.tanggal_bayar, NOW()), 'HH24:MI') AS jam_bayar
            FROM pembayaran p
            JOIN tagihan t ON p.tagihan_id = t.id
            JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
            WHERE {$whereUser}
              AND LOWER(TRIM(p.status_verifikasi)) IN ('diterima', 'terverifikasi', 'lunas')
            ORDER BY COALESCE(p.tanggal_bayar, NOW()) ASC, p.id ASC
        ";
        $stmtCicil = $pdo->prepare($sqlCicilan);
        $stmtCicil->execute([':uid' => $uidParam]);
        $cicilanByTagihan = [];
        foreach ($stmtCicil->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $tid = (int)$c['tagihan_id'];
            $cicilanByTagihan[$tid][] = [
                'id'          => (int)$c['id'],
                'nominal'     => (float)$c['jumlah_bayar'],
                'nominal_fmt' => 'Rp ' . number_format((float)$c['jumlah_bayar'], 0, ',', '.'),
                'metode'      => ucfirst(strtolower((string)$c['metode_bayar'])),
                'no_kwitansi' => $c['no_kwitansi'],
                'tanggal'     => formatTanggalIndonesia((string)$c['tgl_ymd']),
                'jam'         => trim((string)$c['jam_bayar'])
            ];
        }

        // Gabungkan riwayat menjadi 1 kartu & 1 invoice per tagihan_id
        $sqlRiwayat = "
            SELECT * FROM (
                -- 1. Tagihan dengan pembayaran terverifikasi (digabung per tagihan_id)
                SELECT 
                    t.id AS id,
                    t.id AS tagihan_id,
                    (ki.id IS NOT NULL) AS is_iuran,
                    CASE 
                        WHEN ki.nama_iuran IS NOT NULL THEN ki.nama_iuran
                        ELSE 'Sewa ' || COALESCE(s.nama_skema, 'Bulanan')
                    END AS judul,
                    TO_CHAR(MAX(COALESCE(p.tanggal_bayar, NOW())), 'YYYY-MM-DD') AS tanggal_ymd,
                    TO_CHAR(MAX(COALESCE(p.tanggal_bayar, NOW())), 'YYYY-MM-DD') AS tanggal_filter,
                    SUM(p.jumlah_bayar) AS nominal,
                    COALESCE(ki.nominal, s.nominal, SUM(p.jumlah_bayar)) AS total_tagihan,
                    LOWER(TRIM(COALESCE(t.status_tagihan, 'lunas'))) AS status_tagihan,
                    COUNT(p.id) AS jumlah_cicilan,
                    'INV-' || TO_CHAR(MAX(COALESCE(p.tanggal_bayar, NOW())), 'YYYYMM') || '-' || LPAD(t.id::text, 4, '0') AS nomor_invoice,
                    TO_CHAR(pk.tanggal_masuk, 'YYYY-MM-DD') AS tanggal_masuk_ymd,
                    TO_CHAR(COALESCE(t.tanggal_awal_tagihan, pk.tanggal_masuk, MIN(p.tanggal_bayar), NOW()), 'YYYY-MM-DD') AS tanggal_terbit_ymd,
                    TO_CHAR(COALESCE(t.tanggal_jatuh_tempo, MAX(p.tanggal_bayar), NOW()), 'YYYY-MM-DD') AS jatuh_tempo_ymd,
                    TO_CHAR(MAX(COALESCE(p.tanggal_bayar, NOW())), 'YYYY-MM-DD') AS tanggal_bayar_ymd,
                    TO_CHAR(MAX(COALESCE(p.tanggal_bayar, NOW())), 'HH24:MI') AS jam_bayar,
                    COALESCE(s.nama_skema, 'Bulanan') AS periode_sewa,
                    COALESCE(k.no_kamar, '-') AS no_kamar,
                    MAX(COALESCE(p.tanggal_bayar, NOW())) AS sort_date,
                    FALSE AS is_lunas_langsung
                FROM pembayaran p
                JOIN tagihan t ON p.tagihan_id = t.id
                JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
                LEFT JOIN skema_sewa s ON COALESCE(t.skema_id, pk.skema_id) = s.id
                LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
                LEFT JOIN kamar k ON pk.kamar_id = k.id
                WHERE {$whereUser}
                  AND LOWER(TRIM(p.status_verifikasi)) IN ('diterima', 'terverifikasi', 'lunas')
                GROUP BY 
                    t.id, ki.id, ki.nama_iuran, ki.nominal, s.nama_skema, s.nominal,
                    t.status_tagihan, pk.tanggal_masuk, t.tanggal_awal_tagihan,
                    t.tanggal_jatuh_tempo, k.no_kamar

                UNION ALL

                -- 2. Tagihan yang langsung dilunasi pemilik kos tanpa record pembayaran
                SELECT 
                    t.id AS id,
                    t.id AS tagihan_id,
                    (ki.id IS NOT NULL) AS is_iuran,
                    CASE 
                        WHEN ki.nama_iuran IS NOT NULL THEN ki.nama_iuran
                        ELSE 'Sewa ' || COALESCE(s.nama_skema, 'Bulanan')
                    END AS judul,
                    TO_CHAR(COALESCE(t.tanggal_awal_tagihan, pk.tanggal_masuk, CURRENT_DATE), 'YYYY-MM-DD') AS tanggal_ymd,
                    TO_CHAR(COALESCE(t.tanggal_awal_tagihan, pk.tanggal_masuk, CURRENT_DATE), 'YYYY-MM-DD') AS tanggal_filter,
                    COALESCE(ki.nominal, s.nominal, 0) AS nominal,
                    COALESCE(ki.nominal, s.nominal, 0) AS total_tagihan,
                    'lunas' AS status_tagihan,
                    1 AS jumlah_cicilan,
                    'INV-SYS-' || LPAD(t.id::text, 4, '0') AS nomor_invoice,
                    TO_CHAR(pk.tanggal_masuk, 'YYYY-MM-DD') AS tanggal_masuk_ymd,
                    TO_CHAR(COALESCE(t.tanggal_awal_tagihan, pk.tanggal_masuk, CURRENT_DATE), 'YYYY-MM-DD') AS tanggal_terbit_ymd,
                    TO_CHAR(COALESCE(t.tanggal_jatuh_tempo, CURRENT_DATE), 'YYYY-MM-DD') AS jatuh_tempo_ymd,
                    TO_CHAR(COALESCE(t.tanggal_awal_tagihan, pk.tanggal_masuk, CURRENT_DATE), 'YYYY-MM-DD') AS tanggal_bayar_ymd,
                    '' AS jam_bayar,
                    COALESCE(s.nama_skema, 'Bulanan') AS periode_sewa,
                    COALESCE(k.no_kamar, '-') AS no_kamar,
                    COALESCE(t.tanggal_awal_tagihan, pk.tanggal_masuk, CURRENT_DATE)::timestamp AS sort_date,
                    TRUE AS is_lunas_langsung
                FROM tagihan t
                JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
                LEFT JOIN skema_sewa s ON COALESCE(t.skema_id, pk.skema_id) = s.id
                LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
                LEFT JOIN kamar k ON pk.kamar_id = k.id
                WHERE {$whereUser}
                  AND LOWER(TRIM(t.status_tagihan)) = 'lunas'
                  AND NOT EXISTS (
                      SELECT 1 FROM pembayaran pem
                      WHERE pem.tagihan_id = t.id
                        AND LOWER(TRIM(pem.status_verifikasi)) IN ('diterima', 'terverifikasi', 'lunas')
                  )
            ) AS gabungan
            ORDER BY sort_date DESC, id DESC
        ";

        $stmt = $pdo->prepare($sqlRiwayat);
        $stmt->execute([':uid' => $uidParam]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $tid           = (int)$row['tagihan_id'];
            $isIuran       = (bool)($row['is_iuran'] ?? false);
            $tglMasukYmd   = $row['tanggal_masuk_ymd'] ?? '';
            $tglTerbitYmd  = $row['tanggal_terbit_ymd'] ?? '';
            $jatuhTempoYmd = $row['jatuh_tempo_ymd'] ?? '';
            $tglBayarYmd   = $row['tanggal_bayar_ymd'] ?? '';
            $tglYmd        = $row['tanggal_ymd'] ?? '';
            $jamBayar      = trim((string)($row['jam_bayar'] ?? ''));
            $lunasLangsung = (bool)($row['is_lunas_langsung'] ?? false);
            $rincianCicil  = $cicilanByTagihan[$tid] ?? [];
            $jmlCicil      = count($rincianCicil);
            $totalTagihan  = (float)($row['total_tagihan'] ?? $row['nominal']);
            $nominalMasuk  = (float)($row['nominal'] ?? 0);
            $isSudahLunas  = (($row['status_tagihan'] ?? '') === 'lunas' || $nominalMasuk >= $totalTagihan);

            $row['rincian_cicilan'] = $rincianCicil;
            $row['jumlah_cicilan']  = max(1, $jmlCicil);
            $row['is_lunas']        = $isSudahLunas;
            $row['status_label']    = $isSudahLunas ? 'Lunas' : 'Cicilan (' . $jmlCicil . 'x)';
            $row['tanggal']         = formatTanggalIndonesia($tglYmd);
            $row['tanggal_masuk']   = !empty($tglMasukYmd) ? formatTanggalIndonesia($tglMasukYmd) : '-';
            $row['tanggal_terbit']  = formatTanggalIndonesia($tglTerbitYmd);
            $row['jatuh_tempo']     = formatTanggalIndonesia($jatuhTempoYmd);
            $row['berlaku_sampai']  = hitungBerlakuSampai($tglTerbitYmd, $jatuhTempoYmd, (string)($row['periode_sewa'] ?? 'Bulanan'), $isIuran);

            if ($lunasLangsung) {
                $row['tanggal_bayar'] = formatTanggalIndonesia($tglBayarYmd) . ' (Lunas Langsung)';
            } elseif ($jmlCicil > 1) {
                $row['tanggal_bayar'] = 'Terakhir ' . formatTanggalIndonesia($tglBayarYmd);
            } else {
                $row['tanggal_bayar'] = formatTanggalIndonesia($tglBayarYmd) . ($jamBayar !== '' ? ', ' . $jamBayar . ' WIB' : '');
            }
        }
        unset($row);

        return $rows;
    } catch (Exception $e) {
        return [];
    }
}

function redirectTagihanRiwayat(string $pesanSukses = '', string $pesanError = ''): never {
    if ($pesanSukses !== '') {
        $_SESSION['pesan_sukses'] = $pesanSukses;
    }
    if ($pesanError !== '') {
        $_SESSION['pesan_error'] = $pesanError;
    }
    header('Location: /?page=tagihan_riwayat');
    exit;
}

function handleUploadBuktiBayar(PDO $pdo): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['tagihan_id'])) {
        return ['sukses' => '', 'error' => ''];
    }

    $tagihan_id   = (int)$_POST['tagihan_id'];
    $metode_bayar = strtolower(trim($_POST['metode_bayar'] ?? 'transfer'));
    if (!in_array($metode_bayar, ['transfer', 'tunai'], true)) {
        $metode_bayar = 'transfer';
    }

    $nominal_raw   = preg_replace('/[^0-9]/', '', (string)($_POST['nominal_bayar'] ?? ''));
    $nominal_input = (float)$nominal_raw;

    $new_filename     = null;
    $oldBuktiToDelete = '';

    try {
        // Cegah pengiriman baru jika masih ada transaksi yang sedang menunggu verifikasi pada tagihan ini
        $stmtCekPending = $pdo->prepare("
            SELECT COUNT(id)
            FROM pembayaran
            WHERE tagihan_id = :tid
              AND LOWER(TRIM(status_verifikasi)) = 'pending'
        ");
        $stmtCekPending->execute([':tid' => $tagihan_id]);
        if ((int)$stmtCekPending->fetchColumn() > 0) {
            redirectTagihanRiwayat('', 'Pembayaran sebelumnya masih menunggu verifikasi.');
        }

        $stmtTag = $pdo->prepare("
            SELECT 
                t.id, 
                COALESCE(ki.nominal, s.nominal, 5500000) AS total_tagihan,
                COALESCE((
                    SELECT SUM(jumlah_bayar) FROM pembayaran 
                    WHERE tagihan_id = t.id AND LOWER(TRIM(status_verifikasi)) = 'diterima'
                ), 0) AS sudah_dibayar
            FROM tagihan t
            JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
            LEFT JOIN skema_sewa s ON COALESCE(t.skema_id, pk.skema_id) = s.id
            LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
            WHERE t.id = ? LIMIT 1
        ");
        $stmtTag->execute([$tagihan_id]);
        $tagData = $stmtTag->fetch(PDO::FETCH_ASSOC);

        if (!$tagData) {
            redirectTagihanRiwayat('', 'Data tagihan tidak ditemukan.');
        }

        $sisa_tagihan = max(0, (float)$tagData['total_tagihan'] - (float)$tagData['sudah_dibayar']);
        if ($sisa_tagihan <= 0) {
            redirectTagihanRiwayat('', 'Tagihan ini sudah lunas.');
        }

        $jumlah_bayar = ($nominal_input > 0) ? $nominal_input : $sisa_tagihan;
        if ($jumlah_bayar < 1000) {
            redirectTagihanRiwayat('', 'Nominal pembayaran/cicilan minimal Rp 1.000.');
        }
        if ($jumlah_bayar > $sisa_tagihan) {
            redirectTagihanRiwayat('', 'Nominal bayar tidak boleh melebihi sisa tagihan (Rp ' . number_format($sisa_tagihan, 0, ',', '.') . ').');
        }

        if ($metode_bayar === 'transfer') {
            if (!isset($_FILES['bukti_transfer']) || ($_FILES['bukti_transfer']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                redirectTagihanRiwayat('', 'Harap lampirkan file foto bukti transfer.');
            }

            $ext = strtolower(pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                redirectTagihanRiwayat('', 'Format foto bukti transfer harus JPG, PNG, atau WEBP.');
            }

            // Unggah langsung ke Supabase Storage bucket 'bukti_tf'
            $uploadedBukti = uploadKeSupabase($_FILES['bukti_transfer'], 'bukti_tf');
            if ($uploadedBukti === false) {
                redirectTagihanRiwayat('', 'Gagal mengunggah file foto bukti transfer ke penyimpanan cloud.');
            }
            $new_filename = $uploadedBukti;
        }

        $pdo->beginTransaction();

        $stmtCek = $pdo->prepare("
            SELECT id, bukti_pembayaran
            FROM pembayaran 
            WHERE tagihan_id = :tid 
              AND LOWER(TRIM(status_verifikasi)) = 'ditolak'
            ORDER BY id DESC LIMIT 1
        ");
        $stmtCek->execute([':tid' => $tagihan_id]);
        $existingRejected = $stmtCek->fetch(PDO::FETCH_ASSOC);

        if ($existingRejected) {
            $oldFoto = basename(trim((string)($existingRejected['bukti_pembayaran'] ?? '')));
            if ($oldFoto !== '' && strtolower($oldFoto) !== 'null') {
                $oldBuktiToDelete = $oldFoto;
            }

            $stmtUpd = $pdo->prepare("
                UPDATE pembayaran 
                SET bukti_pembayaran = :foto,
                    jumlah_bayar = :nominal,
                    metode_bayar = :metode,
                    status_verifikasi = 'pending',
                    alasan_penolakan = NULL,
                    tanggal_bayar = NOW()
                WHERE id = :id
            ");
            $stmtUpd->execute([
                ':foto'    => ($metode_bayar === 'tunai') ? null : $new_filename,
                ':nominal' => $jumlah_bayar,
                ':metode'  => $metode_bayar,
                ':id'      => (int)$existingRejected['id']
            ]);
        } else {
            $no_kwitansi = 'KW/' . date('Ym') . '/' . strtoupper(bin2hex(random_bytes(2)));
            $stmtBayar = $pdo->prepare("
                INSERT INTO pembayaran (tagihan_id, bukti_pembayaran, jumlah_bayar, metode_bayar, status_verifikasi, no_kwitansi, tanggal_bayar)
                VALUES (?, ?, ?, ?, 'pending', ?, NOW())
            ");
            $stmtBayar->execute([
                $tagihan_id,
                ($metode_bayar === 'tunai') ? null : $new_filename,
                $jumlah_bayar,
                $metode_bayar,
                $no_kwitansi
            ]);
        }

        $pdo->commit();

        // Hapus bukti transfer lama dari bucket 'bukti_tf' setelah upload ulang sukses
        if ($oldBuktiToDelete !== '') {
            hapusDariSupabase('bukti_tf', $oldBuktiToDelete);
        }

        if (function_exists('bersihkanCacheNotifSiteman')) {
            bersihkanCacheNotifSiteman();
        }

        $is_cicilan = ($jumlah_bayar < $sisa_tagihan);
        $labelJenis = $is_cicilan ? 'cicilan Rp ' . number_format($jumlah_bayar, 0, ',', '.') : 'pelunasan';
        $pesan = ($metode_bayar === 'tunai')
            ? "Konfirmasi bayar tunai ({$labelJenis}) berhasil dikirim! Silakan serahkan uang ke pemilik/pengurus kos."
            : "Bukti pembayaran transfer ({$labelJenis}) berhasil dikirim! Menunggu konfirmasi verifikasi.";

        redirectTagihanRiwayat($pesan, '');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($new_filename !== null) {
            hapusDariSupabase('bukti_tf', $new_filename);
        }
        redirectTagihanRiwayat('', 'Gagal memproses pembayaran: ' . $e->getMessage());
    }

    return ['sukses' => '', 'error' => ''];
}