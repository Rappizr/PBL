<?php
/**
 * HANDLER LOGIK & DATABASE KELOLA / MONITORING IURAN
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

$modal_type             = $_GET['modal'] ?? '';
$show_modal_pengeluaran = ($modal_type === 'tambah_pengeluaran');
$show_modal_set_iuran   = ($modal_type === 'set_nominal_iuran');

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
// 2. AMBIL NAMA, NOMINAL & PERIODE IURAN DARI TABEL kategori_iuran
// ==========================================================================
$nama_iuran_saat_ini    = 'Iuran Kebersihan & Keamanan';
$nominal_iuran_saat_ini = 50000;
$tgl_mulai_iuran        = date('Y-m-01');
$tgl_selesai_iuran      = date('Y-m-t');
$id_kategori_iuran      = null;

try {
    $pdo->exec("ALTER TABLE kategori_iuran ADD COLUMN IF NOT EXISTS tanggal_mulai DATE NULL");
    $pdo->exec("ALTER TABLE kategori_iuran ADD COLUMN IF NOT EXISTS tanggal_selesai DATE NULL");

    $stmtGetSetting = $pdo->query("SELECT id, nama_iuran, nominal, tanggal_mulai, tanggal_selesai FROM kategori_iuran ORDER BY id ASC LIMIT 1");
    $rowIuran = $stmtGetSetting->fetch(PDO::FETCH_ASSOC);

    if ($rowIuran) {
        $id_kategori_iuran      = $rowIuran['id'];
        $nama_iuran_saat_ini    = !empty($rowIuran['nama_iuran']) ? $rowIuran['nama_iuran'] : 'Iuran Kebersihan & Keamanan';
        $nominal_iuran_saat_ini = (float) $rowIuran['nominal'];
        
        if (!empty($rowIuran['tanggal_mulai'])) {
            $tgl_mulai_iuran = $rowIuran['tanggal_mulai'];
        }
        if (!empty($rowIuran['tanggal_selesai'])) {
            $tgl_selesai_iuran = $rowIuran['tanggal_selesai'];
        }
    } else {
        $stmtInit = $pdo->prepare("
            INSERT INTO kategori_iuran (nama_iuran, nominal, tanggal_mulai, tanggal_selesai) 
            VALUES ('Iuran Kebersihan & Keamanan', 50000, :tgl_m, :tgl_s)
        ");
        $stmtInit->execute([':tgl_m' => $tgl_mulai_iuran, ':tgl_s' => $tgl_selesai_iuran]);
        $id_kategori_iuran = (int) $pdo->lastInsertId();
    }
} catch (PDOException $e) {
    catatError('ambil nominal iuran', $e);
}

// ==========================================================================
// 3. HANDLER POST: SIMPAN PENGELUARAN & SIMPAN NAMA, NOMINAL, TANGGAL IURAN
// ==========================================================================
$error_modal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'] ?? null;

    // SIMPAN PENGELUARAN KAS
    if (isset($_POST['simpan_pengeluaran'])) {
        $keterangan = trim($_POST['keterangan'] ?? '');
        $nominal    = str_replace('.', '', $_POST['nominal'] ?? '');

        if (!$can_manage) {
            $error_modal = 'Anda tidak memiliki akses untuk menambah pengeluaran.';
        } elseif ($keterangan === '') {
            $error_modal = 'Keterangan wajib diisi.';
        } elseif (!ctype_digit($nominal) || (int) $nominal < 1) {
            $error_modal = 'Nominal harus berupa angka lebih dari 0.';
        } else {
            try {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO buku_kas (
                        keterangan, tipe_transaksi, nominal, tanggal_transaksi, " . ($is_pemilik ? "pemilik_id" : "penghuni_id") . "
                    ) VALUES (:ket, 'pengeluaran', :nom, NOW(), :uid)
                ");
                $stmtInsert->execute([':ket' => $keterangan, ':nom' => (int)$nominal, ':uid' => $user_id]);

                header('Location: ' . urlIuran($current_page));
                exit;
            } catch (PDOException $e) {
                catatError('simpan pengeluaran', $e);
                $error_modal = 'Gagal menyimpan pengeluaran.';
            }
        }
        if ($error_modal !== '') $show_modal_pengeluaran = true;
    }

    // SIMPAN NAMA, NOMINAL & TANGGAL IURAN BERLAKU
    if (isset($_POST['simpan_nominal_iuran'])) {
        $nama_iuran_baru = trim($_POST['nama_iuran'] ?? 'Iuran Kebersihan & Keamanan');
        $nominal_baru    = str_replace('.', '', $_POST['nominal_iuran'] ?? '');
        $rentang_tgl     = trim($_POST['periode_iuran_range'] ?? '');
        $tgl_m_baru      = null;
        $tgl_s_baru      = null;

        if ($rentang_tgl !== '' && str_contains($rentang_tgl, ' to ')) {
            $p = explode(' to ', $rentang_tgl);
            $tgl_m_baru = trim($p[0]);
            $tgl_s_baru = trim($p[1] ?? $p[0]);
        } elseif ($rentang_tgl !== '') {
            $tgl_m_baru = $rentang_tgl;
            $tgl_s_baru = $rentang_tgl;
        }

        if (!$can_manage) {
            $error_modal = 'Anda tidak memiliki akses untuk mengubah nominal iuran.';
        } elseif ($nama_iuran_baru === '') {
            $error_modal = 'Nama / Keterangan iuran wajib diisi.';
        } elseif (!ctype_digit($nominal_baru) || (int) $nominal_baru < 0) {
            $error_modal = 'Nominal iuran harus berupa angka valid.';
        } elseif (empty($tgl_m_baru) || empty($tgl_s_baru)) {
            $error_modal = 'Pilih rentang tanggal iuran yang valid (Tanggal Mulai s.d Selesai).';
        } else {
            try {
                if ($id_kategori_iuran) {
                    $stmtSet = $pdo->prepare("
                        UPDATE kategori_iuran 
                        SET nama_iuran = :nama, nominal = :val, tanggal_mulai = :tgl_m, tanggal_selesai = :tgl_s 
                        WHERE id = :id
                    ");
                    $stmtSet->execute([
                        ':nama'  => $nama_iuran_baru,
                        ':val'   => (int)$nominal_baru,
                        ':tgl_m' => $tgl_m_baru,
                        ':tgl_s' => $tgl_s_baru,
                        ':id'    => $id_kategori_iuran
                    ]);
                } else {
                    $stmtSet = $pdo->prepare("
                        INSERT INTO kategori_iuran (nama_iuran, nominal, tanggal_mulai, tanggal_selesai) 
                        VALUES (:nama, :val, :tgl_m, :tgl_s)
                    ");
                    $stmtSet->execute([
                        ':nama'  => $nama_iuran_baru,
                        ':val'   => (int)$nominal_baru,
                        ':tgl_m' => $tgl_m_baru,
                        ':tgl_s' => $tgl_s_baru
                    ]);
                }

                header('Location: ' . urlIuran($current_page));
                exit;
            } catch (PDOException $e) {
                catatError('simpan nominal iuran', $e);
                $error_modal = 'Gagal memperbarui nominal & rentang tanggal iuran.';
            }
        }
        if ($error_modal !== '') $show_modal_set_iuran = true;
    }
}

// ==========================================================================
// 4. FILTER TANGGAL, HITUNG STATISTIK (DITERIMA = SUDAH BAYAR)
// ==========================================================================
$date_range  = trim($_GET['date_range'] ?? '');
$tgl_mulai   = '';
$tgl_selesai = '';

if ($date_range !== '' && preg_match('/^\d{4}-\d{2}-\d{2}( to \d{4}-\d{2}-\d{2})?$/', $date_range)) {
    $parts       = explode(' to ', $date_range);
    $tgl_mulai   = trim($parts[0]);
    $tgl_selesai = trim($parts[1] ?? $tgl_mulai);
}

$is_filtered         = ($date_range !== '');
$total_penghuni      = 0;
$sudah_bayar         = 0;
$count_belum         = 0;
$count_verifikasi    = 0;

if ($can_manage) {
    try {
        // 1. Total Penghuni Kamar Aktif
        $stmtTotal = $pdo->query("
            SELECT COUNT(DISTINCT pk.id) 
            FROM penghuni_kamar pk
            JOIN penghuni pen ON pk.penghuni_id = pen.id
            JOIN kamar k ON pk.kamar_id = k.id AND k.status_kamar = 'terisi'
            WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) = 'aktif'
        ");
        $total_penghuni = (int) $stmtTotal->fetchColumn();

        // 2. Penghuni yang SUDAH BAYAR (status_verifikasi = 'diterima' DAN tagihan = 'lunas')
        $stmtSudahBayar = $pdo->query("
            SELECT COUNT(DISTINCT pk.id)
            FROM penghuni_kamar pk
            JOIN penghuni pen ON pk.penghuni_id = pen.id
            JOIN kamar k ON pk.kamar_id = k.id AND k.status_kamar = 'terisi'
            JOIN tagihan t ON pk.id = t.penghuni_kamar_id AND t.kategori_iuran_id IS NOT NULL
            JOIN pembayaran pem ON t.id = pem.tagihan_id
            WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) = 'aktif'
              AND LOWER(TRIM(pem.status_verifikasi)) = 'diterima'
              AND LOWER(TRIM(t.status_tagihan)) = 'lunas'
        ");
        $sudah_bayar = (int) $stmtSudahBayar->fetchColumn();

        // 3. Penghuni yang MENUNGGU VERIFIKASI (status_verifikasi = 'pending')
        $stmtVerifikasi = $pdo->query("
            SELECT COUNT(DISTINCT pk.id)
            FROM penghuni_kamar pk
            JOIN penghuni pen ON pk.penghuni_id = pen.id
            JOIN kamar k ON pk.kamar_id = k.id AND k.status_kamar = 'terisi'
            JOIN tagihan t ON pk.id = t.penghuni_kamar_id AND t.kategori_iuran_id IS NOT NULL
            JOIN pembayaran pem ON t.id = pem.tagihan_id
            WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) = 'aktif'
              AND LOWER(TRIM(pem.status_verifikasi)) = 'pending'
        ");
        $count_verifikasi = (int) $stmtVerifikasi->fetchColumn();

        // 4. Penghuni yang BELUM BAYAR (Total dikurangi yang sudah lunas)
        $count_belum = max(0, $total_penghuni - $sudah_bayar);

    } catch (PDOException $e) {
        catatError('hitung statistik iuran', $e);
    }
}

// Saldo Kas Iuran
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

// Riwayat Mutasi Buku Kas
$daftar_transaksi = [];
try {
    $sqlKas = "SELECT id, keterangan AS judul, tipe_transaksi AS tipe, nominal, tanggal_transaksi AS tanggal FROM buku_kas WHERE 1=1";
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
} catch (PDOException $e) {
    catatError('riwayat transaksi', $e);
}

// Filter Tab Penghuni
$filter_status = strtolower(trim($_GET['filter'] ?? 'semua'));
if (!in_array($filter_status, ['semua', 'belum_bayar', 'verifikasi'], true)) {
    $filter_status = 'semua';
}

$filter_tabs = [
    'semua'       => ['label' => 'Semua',       'jumlah' => $total_penghuni],
    'belum_bayar' => ['label' => 'Belum Bayar', 'jumlah' => $count_belum],
    'verifikasi'  => ['label' => 'Verifikasi',  'jumlah' => $count_verifikasi],
];

// DAFTAR PENGHUNI BERDASARKAN TAB
$daftar_penghuni = [];
if ($can_manage) {
    try {
        $sqlPenghuni = "
            SELECT 
                pen.id AS penghuni_id,
                pen.nama, 
                pen.no_wa, 
                COALESCE(k.no_kamar, '-') AS kamar,
                COALESCE(ki.nominal, :nom_def) AS nominal,
                COALESCE(t.status_tagihan, 'belum_bayar') AS status_tagihan,
                pem.status_verifikasi
            FROM penghuni_kamar pk
            JOIN penghuni pen ON pk.penghuni_id = pen.id
            JOIN kamar k ON pk.kamar_id = k.id AND k.status_kamar = 'terisi'
            LEFT JOIN tagihan t ON pk.id = t.penghuni_kamar_id AND t.kategori_iuran_id IS NOT NULL
            LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
            LEFT JOIN (
                SELECT tagihan_id, MAX(id) AS max_pem_id
                FROM pembayaran
                GROUP BY tagihan_id
            ) lp ON lp.tagihan_id = t.id
            LEFT JOIN pembayaran pem ON pem.id = lp.max_pem_id
            WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) = 'aktif'
        ";

        if ($filter_status === 'belum_bayar') {
            $sqlPenghuni .= " AND (pem.status_verifikasi IS NULL OR LOWER(TRIM(pem.status_verifikasi)) != 'diterima' OR LOWER(TRIM(t.status_tagihan)) != 'lunas')";
        } elseif ($filter_status === 'verifikasi') {
            $sqlPenghuni .= " AND LOWER(TRIM(pem.status_verifikasi)) = 'pending'";
        }

        $sqlPenghuni .= " ORDER BY k.no_kamar ASC";
        $stmtP = $pdo->prepare($sqlPenghuni);
        $stmtP->execute([':nom_def' => $nominal_iuran_saat_ini]);
        $daftar_penghuni = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        catatError('daftar penghuni', $e);
    }
}