<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';

if (!function_exists('buatLinkWaSewa')) {
    function buatLinkWaSewa(array $p): string
    {
        $no_hp   = preg_replace('/\D/', '', (string)($p['no_wa'] ?? ''));
        if (str_starts_with($no_hp, '0')) {
            $no_hp = '62' . substr($no_hp, 1);
        }

        $nama    = $p['nama'] ?? 'Penghuni';
        $kamar   = $p['kamar'] ?? '-';
        $nominal = number_format((float)($p['nominal'] ?? 0), 0, ',', '.');
        $batas   = !empty($p['tanggal_jatuh_tempo']) ? date('d/m/Y', strtotime($p['tanggal_jatuh_tempo'])) : '-';
        $selisih = (int)($p['selisih_hari'] ?? 99);

        $status_tagihan = strtolower(trim($p['status_tagihan'] ?? 'belum_bayar'));
        $status_verif   = strtolower(trim($p['status_verifikasi'] ?? ''));

        if ($status_tagihan === 'lunas' || in_array($status_verif, ['terverifikasi', 'diterima', 'lunas'], true)) {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}), terima kasih pembayaran *Sewa Kamar* sebesar *Rp {$nominal}* sudah kami terima & terverifikasi LUNAS. 🙏\n\n_Pengelola SITEMAN - KOS_";
        } elseif ($status_verif === 'pending') {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}), bukti pembayaran *Sewa Kamar* sebesar *Rp {$nominal}* sudah masuk dan sedang kami proses verifikasi ya. Terima kasih. 🙏\n\n_Pengelola SITEMAN - KOS_";
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
                   . "Kami dari Pengelola *SITEMAN - KOS* ingin mengingatkan bahwa tagihan *Sewa Kamar* sebesar *Rp {$nominal}* {$waktu_info}.\n\n"
                   . "Mohon untuk segera melakukan pembayaran dan mengunggah bukti bayar melalui aplikasi SITEMAN - KOS ya. Terima kasih atas perhatiannya. 🙏";
        }

        return $no_hp !== '' ? "https://wa.me/{$no_hp}?text=" . rawurlencode($pesan) : '#';
    }
}

// 1. Filter Rentang Tanggal
$date_range  = trim($_GET['date_range'] ?? '');
$tgl_mulai   = '';
$tgl_selesai = '';

if ($date_range !== '') {
    $parts       = explode(' to ', $date_range);
    $tgl_mulai   = trim($parts[0] ?? '');
    $tgl_selesai = trim($parts[1] ?? $tgl_mulai);
}

$is_filtered = ($date_range !== '');

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
    
    if ($tgl_mulai !== '' && $tgl_selesai !== '') {
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
    if ($tgl_mulai !== '' && $tgl_selesai !== '') {
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

// 5. Ambil Semua Daftar Penghuni untuk Tagihan Sewa & Hitung Counter Tab Sekaligus
$daftar_penghuni  = [];
$count_semua      = 0;
$count_belum      = 0;
$count_verifikasi = 0;
$jumlah_urgent_sewa = 0;
$ada_sewa_telat     = false;

try {
    $sqlPenghuni = "
        SELECT 
            pen.id AS penghuni_id,
            pen.nama,
            pen.no_wa,
            COALESCE(k.no_kamar, '-') AS kamar,
            COALESCE(s.nominal, 0) AS nominal,
            COALESCE(t.status_tagihan, 'belum_bayar') AS status_tagihan,
            t.tanggal_jatuh_tempo,
            pem.status_verifikasi
        FROM penghuni_kamar pk
        JOIN penghuni pen ON pk.penghuni_id = pen.id
        JOIN kamar k ON pk.kamar_id = k.id AND k.status_kamar = 'terisi'
        LEFT JOIN skema_sewa s ON pk.skema_id = s.id
        LEFT JOIN LATERAL (
            SELECT id, status_tagihan, tanggal_jatuh_tempo
            FROM tagihan
            WHERE penghuni_kamar_id = pk.id AND kategori_iuran_id IS NULL
            ORDER BY tanggal_jatuh_tempo DESC
            LIMIT 1
        ) t ON true
        LEFT JOIN (
            SELECT tagihan_id, MAX(id) AS max_pem_id
            FROM pembayaran
            GROUP BY tagihan_id
        ) lp ON lp.tagihan_id = t.id
        LEFT JOIN pembayaran pem ON pem.id = lp.max_pem_id
        WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) = 'aktif'
        ORDER BY NULLIF(regexp_replace(k.no_kamar, '[^0-9]', '', 'g'), '')::INTEGER ASC NULLS LAST, k.no_kamar ASC
    ";

    $rows = $pdo->query($sqlPenghuni)->fetchAll(PDO::FETCH_ASSOC);
    $hari_ini_dt = new DateTime(date('Y-m-d'));

    foreach ($rows as $r) {
        $st_tagihan = strtolower(trim($r['status_tagihan'] ?? ''));
        $st_verif   = strtolower(trim($r['status_verifikasi'] ?? ''));

        $is_lunas = ($st_tagihan === 'lunas' || in_array($st_verif, ['terverifikasi', 'diterima', 'lunas'], true));
        $is_verif = ($st_verif === 'pending');

        $selisih_hari = 99;
        if (!empty($r['tanggal_jatuh_tempo'])) {
            $jt_dt = new DateTime(date('Y-m-d', strtotime($r['tanggal_jatuh_tempo'])));
            $selisih_hari = (int) $hari_ini_dt->diff($jt_dt)->format('%r%a');
        }

        $r['is_lunas']      = $is_lunas;
        $r['is_verifikasi'] = $is_verif;
        $r['selisih_hari']  = $selisih_hari;

        $count_semua++;
        if (!$is_lunas) {
            $count_belum++;
            if ($selisih_hari <= 2) {
                $jumlah_urgent_sewa++;
                if ($selisih_hari <= 0) {
                    $ada_sewa_telat = true;
                }
            }
        }
        if ($is_verif) {
            $count_verifikasi++;
        }

        $daftar_penghuni[] = $r;
    }
} catch (PDOException $e) {
    $daftar_penghuni = [];
}

// 6. Banner Pengingat Jika Ada Tagihan Sewa H-2, H-1, Hari H, atau Lewat Jatuh Tempo
$banner_notif_sewa = null;
if ($jumlah_urgent_sewa > 0) {
    $banner_notif_sewa = [
        'tipe'  => $ada_sewa_telat ? 'danger' : 'warning',
        'judul' => $ada_sewa_telat ? 'Tagihan Sewa Jatuh Tempo / Terlambat!' : 'Pengingat Jatuh Tempo Sewa (H-2 / H-1)!',
        'pesan' => "Terdapat {$jumlah_urgent_sewa} penghuni dengan tagihan sewa yang mendekati atau melewati batas bayar. Klik tombol Chat WA di bawah untuk mengirim pengingat otomatis."
    ];
}