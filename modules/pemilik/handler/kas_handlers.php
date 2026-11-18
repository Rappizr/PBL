<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../../../config/database.php';

if (!function_exists('buatLinkWaSewa')) {
    function buatLinkWaSewa(array $p): string
    {
        $no_hp = preg_replace('/\D/', '', (string)($p['no_wa'] ?? ''));
        if (str_starts_with($no_hp, '0')) {
            $no_hp = '62' . substr($no_hp, 1);
        }

        $nama    = $p['nama'] ?? 'Penghuni';
        $kamar   = $p['kamar'] ?? '-';
        $nominal = number_format((float)($p['nominal'] ?? 0), 0, ',', '.');
        $batas   = !empty($p['tanggal_jatuh_tempo']) ? date('d/m/Y', strtotime($p['tanggal_jatuh_tempo'])) : '-';
        $selisih = (int)($p['selisih_hari'] ?? 99);

        $is_lunas     = !empty($p['is_lunas']);
        $status_verif = strtolower(trim($p['status_verifikasi'] ?? ''));

        if ($is_lunas) {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}), terima kasih pembayaran *Sewa Kamar* Anda sudah terverifikasi *LUNAS* (Sisa Rp 0). 🙏\n\n_Pengelola SITEMAN - KOS_";
        } elseif ($status_verif === 'pending') {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}), bukti pembayaran *Sewa Kamar* sudah masuk dan sedang kami proses verifikasi ya (sisa tagihan sebelum verifikasi: *Rp {$nominal}*). Terima kasih. 🙏\n\n_Pengelola SITEMAN - KOS_";
        } else {
            if ($selisih === 2) {
                $waktu_info = "akan jatuh tempo dalam *2 hari lagi* (tanggal *{$batas}*)";
            } elseif ($selisih === 1) {
                $waktu_info = "akan jatuh tempo *BESOK* (tanggal *{$batas}*)";
            } elseif ($selisih === 0) {
                $waktu_info = "jatuh tempo *HARI INI* (tanggal *{$batas}*)";
            } elseif ($selisih < 0 && $batas !== '-') {
                $waktu_info = "telah *melewati batas waktu* (tanggal *{$batas}*)";
            } else {
                $waktu_info = ($batas !== '-') ? "memiliki batas pembayaran hingga tanggal *{$batas}*" : "saat ini berstatus *Belum Bayar*";
            }

            $pesan = "Halo *{$nama}* (Kamar {$kamar}),\n\n"
                   . "Kami dari Pengelola *SITEMAN - KOS* ingin mengingatkan bahwa sisa tagihan *Sewa Kamar* sebesar *Rp {$nominal}* {$waktu_info}.\n\n"
                   . "Mohon untuk segera melakukan pembayaran (lunas maupun cicilan) dan mengunggah bukti bayar melalui aplikasi SITEMAN - KOS ya. Terima kasih atas perhatiannya. 🙏";
        }

        return $no_hp !== '' ? "https://wa.me/{$no_hp}?text=" . rawurlencode($pesan) : '#';
    }
}

// 1. Filter Rentang Tanggal
$date_range  = trim($_GET['date_range'] ?? '');
$tgl_mulai   = '';
$tgl_selesai = '';

if ($date_range !== '' && preg_match('/^\d{4}-\d{2}-\d{2}( to \d{4}-\d{2}-\d{2})?$/', $date_range)) {
    $parts       = explode(' to ', $date_range);
    $tgl_mulai   = trim($parts[0] ?? '');
    $tgl_selesai = trim($parts[1] ?? $tgl_mulai);
}

$is_filtered = ($tgl_mulai !== '' && $tgl_selesai !== '');

// 2. Hitung Total Pemasukan Sewa Pemilik (HANYA TAGIHAN SEWA: t.kategori_iuran_id IS NULL)
$total_pemasukan = 0;
try {
    $sqlTotal = "
        SELECT COALESCE(SUM(p.jumlah_bayar), 0) 
        FROM pembayaran p
        JOIN tagihan t ON p.tagihan_id = t.id
        WHERE LOWER(TRIM(p.status_verifikasi)) IN ('terverifikasi', 'diterima', 'lunas')
          AND t.kategori_iuran_id IS NULL
    ";

    if ($is_filtered) {
        $sqlTotal .= " AND DATE(p.tanggal_bayar) BETWEEN :tgl_mulai AND :tgl_selesai";
        $stmtTotal = $pdo->prepare($sqlTotal);
        $stmtTotal->execute([
            ':tgl_mulai'   => $tgl_mulai,
            ':tgl_selesai' => $tgl_selesai
        ]);
    } else {
        $stmtTotal = $pdo->query($sqlTotal);
    }

    $total_pemasukan = (float) $stmtTotal->fetchColumn();
} catch (PDOException $e) {
    $total_pemasukan = 0;
}

// 3. Ambil Riwayat Arus Kas Sewa (HANYA TAGIHAN SEWA: t.kategori_iuran_id IS NULL)
$daftar_transaksi = [];
try {
    $sqlMutasi = "
        SELECT 
            p.id,
            COALESCE(p.tanggal_bayar, NOW()) AS tanggal,
            p.jumlah_bayar AS nominal,
            COALESCE(pen.nama, 'Penghuni') AS nama_penghuni,
            CONCAT('Pembayaran Sewa ', COALESCE(pen.nama, 'Penghuni')) AS judul
        FROM pembayaran p
        JOIN tagihan t ON p.tagihan_id = t.id
        LEFT JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
        LEFT JOIN penghuni pen ON pk.penghuni_id = pen.id
        WHERE LOWER(TRIM(p.status_verifikasi)) IN ('terverifikasi', 'diterima', 'lunas')
          AND t.kategori_iuran_id IS NULL
    ";

    $paramsMutasi = [];
    if ($is_filtered) {
        $sqlMutasi .= " AND DATE(p.tanggal_bayar) BETWEEN :tgl_mulai AND :tgl_selesai";
        $paramsMutasi[':tgl_mulai']   = $tgl_mulai;
        $paramsMutasi[':tgl_selesai'] = $tgl_selesai;
    }

    $sqlMutasi .= " ORDER BY p.id DESC";
    $stmtMutasi = $pdo->prepare($sqlMutasi);
    $stmtMutasi->execute($paramsMutasi);
    $daftar_transaksi = $stmtMutasi->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftar_transaksi = [];
}

// 4. Tangkap Filter Tab Status Tagihan Sewa
$filter_status = strtolower(trim($_GET['filter'] ?? 'semua'));
if (!in_array($filter_status, ['semua', 'belum_bayar', 'verifikasi'], true)) {
    $filter_status = 'semua';
}

// 5. Ambil Semua Daftar Penghuni untuk Tagihan Sewa (Akumulasi Sisa Tunggakan & Cicilan)
$daftar_penghuni  = [];
$count_semua      = 0;
$count_belum      = 0;
$count_verifikasi = 0;

try {
    $sqlPenghuni = "
        SELECT 
            pen.id AS penghuni_id,
            pen.nama,
            pen.no_wa,
            COALESCE(k.no_kamar, '-') AS kamar,
            COALESCE(SUM(
                CASE 
                    WHEN t.id IS NOT NULL AND LOWER(TRIM(COALESCE(t.status_tagihan, 'belum_bayar'))) <> 'lunas'
                    THEN GREATEST(0, COALESCE(s.nominal, 0) - COALESCE(bayar_ok.total_diterima, 0))
                    ELSE 0
                END
            ), 0) AS nominal,
            COALESCE(SUM(COALESCE(bayar_pend.jml_pending, 0)), 0) AS jml_pending,
            MIN(
                CASE 
                    WHEN t.id IS NOT NULL AND LOWER(TRIM(COALESCE(t.status_tagihan, 'belum_bayar'))) <> 'lunas'
                    THEN t.tanggal_jatuh_tempo
                    ELSE NULL
                END
            ) AS tanggal_jatuh_tempo
        FROM penghuni_kamar pk
        JOIN penghuni pen ON pk.penghuni_id = pen.id
        JOIN kamar k ON pk.kamar_id = k.id AND LOWER(TRIM(k.status_kamar)) = 'terisi'
        LEFT JOIN skema_sewa s ON pk.skema_id = s.id
        LEFT JOIN tagihan t ON t.penghuni_kamar_id = pk.id AND t.kategori_iuran_id IS NULL
        LEFT JOIN (
            SELECT tagihan_id, SUM(jumlah_bayar) AS total_diterima
            FROM pembayaran
            WHERE LOWER(TRIM(status_verifikasi)) IN ('terverifikasi', 'diterima', 'lunas')
            GROUP BY tagihan_id
        ) bayar_ok ON bayar_ok.tagihan_id = t.id
        LEFT JOIN (
            SELECT tagihan_id, COUNT(*) AS jml_pending
            FROM pembayaran
            WHERE LOWER(TRIM(status_verifikasi)) = 'pending'
            GROUP BY tagihan_id
        ) bayar_pend ON bayar_pend.tagihan_id = t.id
        WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) IN ('aktif', 'freeze')
        GROUP BY pen.id, pen.nama, pen.no_wa, k.no_kamar
        ORDER BY NULLIF(regexp_replace(k.no_kamar, '[^0-9]', '', 'g'), '')::INTEGER ASC NULLS LAST, k.no_kamar ASC
    ";

    $rows = $pdo->query($sqlPenghuni)->fetchAll(PDO::FETCH_ASSOC);
    $hari_ini_dt = new DateTime(date('Y-m-d'));

    foreach ($rows as $r) {
        $sisaNominal = (float)($r['nominal'] ?? 0);
        $hasPending  = ((int)($r['jml_pending'] ?? 0) > 0);
        $is_lunas    = ($sisaNominal <= 0);

        $selisih_hari = 99;
        if (!empty($r['tanggal_jatuh_tempo'])) {
            $jt_dt = new DateTime(date('Y-m-d', strtotime($r['tanggal_jatuh_tempo'])));
            $selisih_hari = (int) $hari_ini_dt->diff($jt_dt)->format('%r%a');
        }

        $r['nominal']           = $sisaNominal;
        $r['is_lunas']          = $is_lunas;
        $r['is_verifikasi']     = $hasPending;
        $r['status_tagihan']    = $is_lunas ? 'lunas' : 'belum_bayar';
        $r['status_verifikasi'] = $hasPending ? 'pending' : ($is_lunas ? 'diterima' : '');
        $r['selisih_hari']      = $selisih_hari;

        $count_semua++;
        if (!$is_lunas) {
            $count_belum++;
        }
        if ($hasPending) {
            $count_verifikasi++;
        }

        $daftar_penghuni[] = $r;
    }
} catch (PDOException $e) {
    $daftar_penghuni = [];
}