<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';

// 1. Filter Rentang Tanggal
$date_range  = trim($_GET['date_range'] ?? '');
$tgl_mulai   = '';
$tgl_selesai = '';

if ($date_range !== '') {
    $parts = explode(' to ', $date_range);
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

// 5. Hitung Counter Badge Tab Khusus Tagihan Sewa
$count_semua      = 0;
$count_belum      = 0;
$count_verifikasi = 0;

try {
    $sqlCount = "
        SELECT 
            COUNT(DISTINCT pk.id) AS total_semua,
            COUNT(DISTINCT CASE 
                WHEN t.status_tagihan IS NULL OR LOWER(TRIM(t.status_tagihan)) != 'lunas' THEN pk.id 
            END) AS total_belum,
            COUNT(DISTINCT CASE 
                WHEN LOWER(TRIM(pem.status_verifikasi)) = 'pending' THEN pk.id 
            END) AS total_verifikasi
        FROM penghuni_kamar pk
        JOIN penghuni pen ON pk.penghuni_id = pen.id
        JOIN kamar k ON pk.kamar_id = k.id AND k.status_kamar = 'terisi'
        LEFT JOIN tagihan t ON pk.id = t.penghuni_kamar_id AND t.kategori_iuran_id IS NULL
        LEFT JOIN (
            SELECT tagihan_id, MAX(id) AS max_pem_id
            FROM pembayaran
            GROUP BY tagihan_id
        ) lp ON lp.tagihan_id = t.id
        LEFT JOIN pembayaran pem ON pem.id = lp.max_pem_id
        WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) = 'aktif'
    ";
    $counts = $pdo->query($sqlCount)->fetch(PDO::FETCH_ASSOC);

    if ($counts) {
        $count_semua      = (int) $counts['total_semua'];
        $count_belum      = (int) $counts['total_belum'];
        $count_verifikasi = (int) $counts['total_verifikasi'];
    }
} catch (PDOException $e) {}

// 6. Ambil Daftar Penghuni untuk Tagihan Sewa
$daftar_penghuni = [];
try {
    $sqlPenghuni = "
        SELECT 
            pen.id AS penghuni_id,
            pen.nama,
            pen.no_wa,
            COALESCE(k.no_kamar, '-') AS kamar,
            COALESCE(s.nominal, 0) AS nominal,
            COALESCE(t.status_tagihan, 'belum_bayar') AS status_tagihan,
            pem.status_verifikasi
        FROM penghuni_kamar pk
        JOIN penghuni pen ON pk.penghuni_id = pen.id
        JOIN kamar k ON pk.kamar_id = k.id AND k.status_kamar = 'terisi'
        LEFT JOIN skema_sewa s ON pk.skema_id = s.id
        LEFT JOIN tagihan t ON pk.id = t.penghuni_kamar_id AND t.kategori_iuran_id IS NULL
        LEFT JOIN (
            SELECT tagihan_id, MAX(id) AS max_pem_id
            FROM pembayaran
            GROUP BY tagihan_id
        ) lp ON lp.tagihan_id = t.id
        LEFT JOIN pembayaran pem ON pem.id = lp.max_pem_id
        WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) = 'aktif'
    ";

    if ($filter_status === 'belum_bayar') {
        $sqlPenghuni .= " AND (t.status_tagihan IS NULL OR LOWER(TRIM(t.status_tagihan)) != 'lunas')";
    } elseif ($filter_status === 'verifikasi') {
        $sqlPenghuni .= " AND LOWER(TRIM(pem.status_verifikasi)) = 'pending'";
    }

    $sqlPenghuni .= " ORDER BY NULLIF(regexp_replace(k.no_kamar, '[^0-9]', '', 'g'), '')::INTEGER ASC NULLS LAST, k.no_kamar ASC";
    $stmtPenghuni = $pdo->query($sqlPenghuni);
    $daftar_penghuni = $stmtPenghuni->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftar_penghuni = [];
}