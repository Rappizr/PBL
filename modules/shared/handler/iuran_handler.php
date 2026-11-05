<?php
/**
 * HANDLER KELOLA / MONITORING IURAN
 * Mengolah data saldo kas, riwayat transaksi, dan status tagihan iuran penghuni dari PostgreSQL
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';

// ==========================================================================
// FUNGSI BANTU
// ==========================================================================
if (!function_exists('formatRupiah')) {
    function formatRupiah(float $angka): string
    {
        return 'Rp. ' . ($angka < 0 ? '-' : '') . number_format(abs($angka), 0, ',', '.');
    }
}

if (!function_exists('linkWhatsApp')) {
    function linkWhatsApp(?string $noWa, string $pesan = ''): ?string
    {
        $nomor = preg_replace('/\D/', '', (string) $noWa);
        if ($nomor === '') {
            return null;
        }
        if (str_starts_with($nomor, '0')) {
            $nomor = '62' . substr($nomor, 1);
        }
        return 'https://wa.me/' . $nomor . ($pesan !== '' ? '?text=' . rawurlencode($pesan) : '');
    }
}

if (!function_exists('urlIuran')) {
    function urlIuran(string $page, array $params = []): string
    {
        $query = ['page' => $page] + $params;
        $query = array_filter($query, fn($v) => $v !== null && $v !== '');
        return '/?' . http_build_query($query);
    }
}

if (!function_exists('catatError')) {
    function catatError(string $konteks, PDOException $e): void
    {
        error_log('[iuran] ' . $konteks . ': ' . $e->getMessage());
    }
}

// ==========================================================================
// 1. Role dan Hak Akses
// ==========================================================================
$role_user    = $_SESSION['user']['role'] ?? 'penghuni';
$is_pemilik   = ($role_user === 'pemilik');
$is_pj        = !$is_pemilik && (!empty($_SESSION['user']['is_pj']) || !empty($_SESSION['user']['is_ketua_kos']));
$can_manage   = $is_pemilik || $is_pj;

$link_kembali = $is_pemilik ? '/?page=dashboard_pemilik' : '/?page=dashboard_penghuni';
$current_page = $_GET['page'] ?? ($can_manage ? 'kelola_iuran' : 'monitoring_iuran');
$show_modal   = isset($_GET['modal']) && $_GET['modal'] === 'tambah_pengeluaran';

if ($is_pemilik) {
    $judul_halaman    = 'Monitoring & Tagihan Iuran';
    $subjudul_halaman = 'Monitoring pengeluaran & pemasukan uang iuran';
} elseif ($is_pj) {
    $judul_halaman    = 'Kelola Iuran';
    $subjudul_halaman = 'Kelola dan monitor kas iuran bersama penghuni kos';
} else {
    $judul_halaman    = 'Monitoring Iuran';
    $subjudul_halaman = 'Pantau saldo dan riwayat pengeluaran uang iuran bersama';
}

// ==========================================================================
// 2. HANDLER: SIMPAN PENGELUARAN BARU (POST)
// ==========================================================================

$error_modal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pengeluaran'])) {

    $keterangan = trim($_POST['keterangan'] ?? '');

    // Hapus titik pemisah ribuan
    $nominal = str_replace('.', '', $_POST['nominal'] ?? '');

    $user_id = $_SESSION['user']['id'] ?? null;

    if (!$can_manage) {
        $error_modal = 'Anda tidak memiliki akses untuk menambah pengeluaran.';

    } elseif ($keterangan === '') {
        $error_modal = 'Keterangan wajib diisi.';

    } elseif (!ctype_digit($nominal) || (int) $nominal < 1) {
        $error_modal = 'Nominal harus berupa angka lebih dari 0.';

    } else {

        // Ubah nominal menjadi integer
        $nominal = (int) $nominal;

        try {
            $stmtInsert = $pdo->prepare("
                INSERT INTO buku_kas (
                    keterangan,
                    tipe_transaksi,
                    nominal,
                    tanggal_transaksi,
                    " . ($is_pemilik ? "pemilik_id" : "penghuni_id") . "
                )
                VALUES (:ket, 'pengeluaran', :nom, NOW(), :uid)
            ");

            $stmtInsert->execute([
                ':ket' => $keterangan,
                ':nom' => $nominal,
                ':uid' => $user_id
            ]);

            header('Location: ' . urlIuran($current_page));
            exit;

        } catch (PDOException $e) {
            catatError('simpan pengeluaran', $e);
            $error_modal = 'Gagal menyimpan pengeluaran.';
        }
    }

    if ($error_modal !== '') {
        $show_modal = true;
    }
}

// ==========================================================================
// 3. Filter Rentang Tanggal
// ==========================================================================
$date_range  = trim($_GET['date_range'] ?? '');
$tgl_mulai   = '';
$tgl_selesai = '';

if ($date_range !== '' && preg_match('/^\d{4}-\d{2}-\d{2}( to \d{4}-\d{2}-\d{2})?$/', $date_range)) {
    $parts       = explode(' to ', $date_range);
    $tgl_mulai   = trim($parts[0]);
    $tgl_selesai = trim($parts[1] ?? $tgl_mulai);
}

$is_filtered = ($date_range !== '');

// ==========================================================================
// 4. Total Penghuni dan Sudah Bayar
// ==========================================================================
$total_penghuni = 0;
$sudah_bayar    = 0;

if ($can_manage) {
    try {
        $stmtPenghuniCount = $pdo->query("
            SELECT COUNT(DISTINCT pen.id)
            FROM penghuni pen
            LEFT JOIN penghuni_kamar pk ON pen.id = pk.penghuni_id
            WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) = 'aktif'
        ");
        $total_penghuni = (int) $stmtPenghuniCount->fetchColumn();

        $stmtBayarCount = $pdo->query("
            SELECT COUNT(DISTINCT pen.id)
            FROM penghuni pen
            LEFT JOIN penghuni_kamar pk ON pen.id = pk.penghuni_id
            LEFT JOIN tagihan t ON pk.id = t.penghuni_kamar_id
            LEFT JOIN pembayaran pem ON t.id = pem.tagihan_id
            LEFT JOIN buku_kas bk ON pen.id = bk.penghuni_id OR pem.id = bk.pembayaran_id
            WHERE LOWER(COALESCE(t.status_tagihan, '')) IN ('lunas', 'terverifikasi', 'diterima', 'sebagian')
               OR LOWER(COALESCE(pem.status_verifikasi, '')) IN ('diterima', 'terverifikasi', 'lunas')
               OR LOWER(COALESCE(bk.tipe_transaksi, '')) = 'pemasukan'
        ");
        $sudah_bayar = (int) $stmtBayarCount->fetchColumn();
    } catch (PDOException $e) {
        catatError('hitung penghuni', $e);
    }
}

// ==========================================================================
// 5. Saldo Kas Iuran
// ==========================================================================
$saldo_kas_iuran = 0;
try {
    $stmtSaldo = $pdo->query("
        SELECT
            COALESCE(SUM(CASE WHEN LOWER(tipe_transaksi) = 'pemasukan' THEN nominal ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN LOWER(tipe_transaksi) = 'pengeluaran' THEN nominal ELSE 0 END), 0) AS saldo
        FROM buku_kas
    ");
    $saldo_kas_iuran = (float) $stmtSaldo->fetchColumn();
} catch (PDOException $e) {
    catatError('hitung saldo', $e);
}
$saldo_tampil = formatRupiah($saldo_kas_iuran);

// ==========================================================================
// 6. Riwayat Transaksi
// ==========================================================================
$daftar_transaksi = [];
try {
    $sqlKas = "
        SELECT
            id,
            keterangan AS judul,
            tipe_transaksi AS tipe,
            nominal,
            tanggal_transaksi AS tanggal
        FROM buku_kas
        WHERE 1=1
    ";

    $paramsKas = [];
    if ($tgl_mulai !== '' && $tgl_selesai !== '') {
        $sqlKas .= " AND DATE(tanggal_transaksi) BETWEEN :tgl_mulai AND :tgl_selesai";
        $paramsKas[':tgl_mulai']   = $tgl_mulai;
        $paramsKas[':tgl_selesai'] = $tgl_selesai;
    }

    $sqlKas .= " ORDER BY tanggal_transaksi DESC";
    $stmtKas = $pdo->prepare($sqlKas);
    $stmtKas->execute($paramsKas);
    $rawKas = $stmtKas->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rawKas as $row) {
        $is_keluar = strtolower($row['tipe']) === 'pengeluaran';
        $daftar_transaksi[] = [
            'tipe'    => $is_keluar ? 'keluar' : 'masuk',
            'icon'    => $is_keluar ? 'fa-solid fa-arrow-up-right-from-square' : 'fa-solid fa-check',
            'judul'   => $row['judul'],
            'tanggal' => date('Y-m-d H:i:s', strtotime($row['tanggal'])),
            'nominal' => ($is_keluar ? 'Rp. -' : 'Rp. +') . number_format((float) $row['nominal'], 0, ',', '.')
        ];
    }

    if (empty($daftar_transaksi) && !$is_filtered) {
        $sqlFallback = "
            SELECT
                p.id,
                CONCAT('Pembayaran ', pen.nama) AS judul,
                'pemasukan' AS tipe,
                p.jumlah_bayar AS nominal,
                COALESCE(p.tanggal_bayar, CURRENT_TIMESTAMP) AS tanggal
            FROM pembayaran p
            JOIN tagihan t ON p.tagihan_id = t.id
            JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
            JOIN penghuni pen ON pk.penghuni_id = pen.id
            WHERE LOWER(COALESCE(p.status_verifikasi, '')) IN ('diterima', 'terverifikasi', 'lunas')
            ORDER BY p.id DESC
        ";
        $rawFallback = $pdo->query($sqlFallback)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rawFallback as $fb) {
            $daftar_transaksi[] = [
                'tipe'    => 'masuk',
                'icon'    => 'fa-solid fa-check',
                'judul'   => $fb['judul'],
                'tanggal' => date('Y-m-d H:i:s', strtotime($fb['tanggal'])),
                'nominal' => 'Rp. +' . number_format((float) $fb['nominal'], 0, ',', '.')
            ];
        }
    }
} catch (PDOException $e) {
    catatError('riwayat transaksi', $e);
}

// ==========================================================================
// 7. Tab Filter Status Tagihan
// ==========================================================================
$filter_status = strtolower(trim($_GET['filter'] ?? 'semua'));
if (!in_array($filter_status, ['semua', 'belum_bayar', 'verifikasi'], true)) {
    $filter_status = 'semua';
}

$count_semua      = 0;
$count_belum      = 0;
$count_verifikasi = 0;

if ($can_manage) {
    try {
        $sqlCount = "
            SELECT
                COUNT(DISTINCT pen.id) AS total_semua,
                COUNT(DISTINCT CASE WHEN LOWER(COALESCE(t.status_tagihan, 'belum_bayar')) IN ('belum_bayar', 'menunggu') THEN pen.id END) AS total_belum,
                COUNT(DISTINCT CASE WHEN LOWER(COALESCE(t.status_tagihan, '')) IN ('proses_verifikasi', 'menunggu_verifikasi', 'verifikasi', 'pending') OR LOWER(COALESCE(pem.status_verifikasi, '')) = 'pending' THEN pen.id END) AS total_verifikasi
            FROM penghuni pen
            LEFT JOIN penghuni_kamar pk ON pen.id = pk.penghuni_id
            LEFT JOIN tagihan t ON pk.id = t.penghuni_kamar_id
            LEFT JOIN pembayaran pem ON t.id = pem.tagihan_id
            WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) = 'aktif'
        ";
        $counts = $pdo->query($sqlCount)->fetch(PDO::FETCH_ASSOC);

        if ($counts) {
            $count_semua      = (int) $counts['total_semua'];
            $count_belum      = (int) $counts['total_belum'];
            $count_verifikasi = (int) $counts['total_verifikasi'];
        }
    } catch (PDOException $e) {
        catatError('hitung tab filter', $e);
    }
}

$filter_tabs = [
    'semua'       => ['label' => 'Semua',       'jumlah' => $count_semua],
    'belum_bayar' => ['label' => 'Belum Bayar', 'jumlah' => $count_belum],
    'verifikasi'  => ['label' => 'Verifikasi',  'jumlah' => $count_verifikasi],
];

// ==========================================================================
// 8. Daftar Penghuni Per Tab
// ==========================================================================
$daftar_penghuni = [];

if ($can_manage) {
    try {
        $sqlPenghuni = "
            SELECT DISTINCT ON (pen.id)
                pen.nama,
                pen.no_wa,
                COALESCE(k.no_kamar, '-') AS kamar,
                COALESCE(t.total_tagihan, pk.harga_sewa, 0) AS nominal,
                COALESCE(t.status_tagihan, 'belum_bayar') AS status_tagihan
            FROM penghuni pen
            LEFT JOIN penghuni_kamar pk ON pen.id = pk.penghuni_id
            LEFT JOIN kamar k ON pk.kamar_id = k.id
            LEFT JOIN tagihan t ON pk.id = t.penghuni_kamar_id
            LEFT JOIN pembayaran pem ON t.id = pem.tagihan_id
            WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) = 'aktif'
        ";

        if ($filter_status === 'belum_bayar') {
            $sqlPenghuni .= " AND (t.status_tagihan IS NULL OR LOWER(t.status_tagihan) IN ('belum_bayar', 'menunggu'))";
        } elseif ($filter_status === 'verifikasi') {
            $sqlPenghuni .= " AND (LOWER(COALESCE(t.status_tagihan, '')) IN ('proses_verifikasi', 'menunggu_verifikasi', 'verifikasi') OR LOWER(COALESCE(pem.status_verifikasi, '')) = 'pending')";
        }

        $sqlPenghuni .= " ORDER BY pen.id, t.id DESC";
        $daftar_penghuni = $pdo->query($sqlPenghuni)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($daftar_penghuni as &$p) {
            $p['nominal_tampil'] = formatRupiah((float) $p['nominal']);
            $p['kamar_tampil']   = $p['kamar'] === '-' ? '-' : 'Kamar ' . $p['kamar'];
            $p['wa_link']        = linkWhatsApp($p['no_wa'] ?? null, 'Halo ' . $p['nama'] . ', ini mengenai iuran kos.');
        }
        unset($p);
    } catch (PDOException $e) {
        catatError('daftar penghuni', $e);
    }
}