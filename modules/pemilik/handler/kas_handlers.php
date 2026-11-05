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

// 2. Hitung Total Pemasukan
$total_pemasukan = 0;
try {
    $stmtTotal = $pdo->query("
        SELECT COALESCE(SUM(jumlah_bayar), 0) 
        FROM pembayaran 
        WHERE LOWER(status_verifikasi) IN ('terverifikasi', 'diterima', 'lunas')
    ");
    $total_pemasukan = (float) $stmtTotal->fetchColumn();
    
    if ($total_pemasukan == 0) {
        $stmtTotal2 = $pdo->query("
            SELECT COALESCE(SUM(total_tagihan), 0) 
            FROM tagihan 
            WHERE LOWER(status_tagihan) IN ('lunas', 'terverifikasi')
        ");
        $total_pemasukan = (float) $stmtTotal2->fetchColumn();
    }
} catch (PDOException $e) {
    $total_pemasukan = 0;
}

// 3. Ambil Riwayat Arus Kas (Terverifikasi / Lunas)
$daftar_transaksi = [];
try {
    $sqlMutasi = "
        SELECT 
            p.id,
            p.tanggal_bayar AS tanggal,
            p.jumlah_bayar AS nominal,
            pen.nama AS nama_penghuni,
            CONCAT('Pembayaran Sewa ', pen.nama) AS judul
        FROM pembayaran p
        JOIN tagihan t ON p.tagihan_id = t.id
        JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
        JOIN penghuni pen ON pk.penghuni_id = pen.id
        WHERE LOWER(p.status_verifikasi) IN ('terverifikasi', 'diterima', 'lunas')
    ";

    $paramsMutasi = [];
    if ($tgl_mulai !== '' && $tgl_selesai !== '') {
        $sqlMutasi .= " AND DATE(p.tanggal_bayar) BETWEEN :tgl_mulai AND :tgl_selesai";
        $paramsMutasi[':tgl_mulai']   = $tgl_mulai;
        $paramsMutasi[':tgl_selesai'] = $tgl_selesai;
    }

    $sqlMutasi .= " ORDER BY p.tanggal_bayar DESC";
    $stmtMutasi = $pdo->prepare($sqlMutasi);
    $stmtMutasi->execute($paramsMutasi);
    $daftar_transaksi = $stmtMutasi->fetchAll(PDO::FETCH_ASSOC);

    if (empty($daftar_transaksi)) {
        $sqlTagihanLunas = "
            SELECT 
                t.id,
                t.tanggal_jatuh_tempo AS tanggal,
                t.total_tagihan AS nominal,
                pen.nama AS nama_penghuni,
                CONCAT('Pembayaran Sewa ', pen.nama) AS judul
            FROM tagihan t
            JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
            JOIN penghuni pen ON pk.penghuni_id = pen.id
            WHERE LOWER(t.status_tagihan) IN ('lunas', 'terverifikasi')
        ";
        if ($tgl_mulai !== '' && $tgl_selesai !== '') {
            $sqlTagihanLunas .= " AND DATE(t.tanggal_jatuh_tempo) BETWEEN " . $pdo->quote($tgl_mulai) . " AND " . $pdo->quote($tgl_selesai);
        }
        $sqlTagihanLunas .= " ORDER BY t.id DESC";
        $daftar_transaksi = $pdo->query($sqlTagihanLunas)->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $daftar_transaksi = [];
}

// 4. Tangkap Filter Tab Status
$filter_status = strtolower(trim($_GET['filter'] ?? 'semua'));

// 5. Hitung Counter Badge Tab Secara Akurat Berdasarkan Penghuni Aktif
$count_semua      = 0;
$count_belum      = 0;
$count_verifikasi = 0;

try {
    $sqlCount = "
        SELECT 
            COUNT(DISTINCT pen.id) AS total_semua,
            COUNT(DISTINCT CASE WHEN LOWER(t.status_tagihan) IN ('belum_bayar', 'menunggu') OR t.status_tagihan IS NULL THEN pen.id END) AS total_belum,
            COUNT(DISTINCT CASE WHEN LOWER(t.status_tagihan) IN ('proses_verifikasi', 'menunggu_verifikasi', 'verifikasi') THEN pen.id END) AS total_verifikasi
        FROM penghuni pen
        JOIN penghuni_kamar pk ON pen.id = pk.penghuni_id AND pk.status_kamar = 'aktif'
        LEFT JOIN tagihan t ON pk.id = t.penghuni_kamar_id
    ";
    $counts = $pdo->query($sqlCount)->fetch(PDO::FETCH_ASSOC);

    if ($counts) {
        $count_semua      = (int) $counts['total_semua'];
        $count_belum      = (int) $counts['total_belum'];
        $count_verifikasi = (int) $counts['total_verifikasi'];
    }
} catch (PDOException $e) {}

// 6. Ambil Data Penghuni Aktif
$daftar_penghuni = [];
try {
    $sqlPenghuni = "
        SELECT DISTINCT ON (pen.id)
            pen.nama,
            pen.no_wa,
            k.no_kamar AS kamar,
            COALESCE(t.total_tagihan, pk.harga_sewa, 0) AS nominal,
            COALESCE(t.status_tagihan, 'belum_bayar') AS status_tagihan
        FROM penghuni pen
        JOIN penghuni_kamar pk ON pen.id = pk.penghuni_id AND pk.status_kamar = 'aktif'
        JOIN kamar k ON pk.kamar_id = k.id
        LEFT JOIN tagihan t ON pk.id = t.penghuni_kamar_id
        WHERE 1=1
    ";

    if ($filter_status === 'belum_bayar') {
        $sqlPenghuni .= " AND (t.status_tagihan IS NULL OR LOWER(t.status_tagihan) IN ('belum_bayar', 'menunggu'))";
    } elseif ($filter_status === 'verifikasi') {
        $sqlPenghuni .= " AND LOWER(t.status_tagihan) IN ('proses_verifikasi', 'menunggu_verifikasi', 'verifikasi')";
    }

    $sqlPenghuni .= " ORDER BY pen.id, t.id DESC";
    $daftar_penghuni = $pdo->query($sqlPenghuni)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $daftar_penghuni = [];
}