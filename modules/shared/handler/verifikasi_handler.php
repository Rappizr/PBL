<?php
/**
 * HANDLER LOGIK & DATABASE VERIFIKASI PEMBAYARAN
 * Lokasi: modules/shared/handler/verifikasi_handler.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Inisialisasi Koneksi Database PDO
global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $possible_paths = [
        __DIR__ . '/../../../config/database.php',
        __DIR__ . '/../../config/database.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/database.php'
    ];
    foreach ($possible_paths as $p) {
        if (file_exists($p)) {
            require_once $p;
            if (isset($pdo) && ($pdo instanceof PDO)) break;
        }
    }
}

// 2. Deteksi Hak Akses Role
$currentUser = $_SESSION['user'] ?? [];
$is_pemilik  = ($currentUser['role'] ?? '') === 'pemilik';
$is_ketua    = !empty($currentUser['is_pj']) || !empty($currentUser['is_ketua_kos']) || ($currentUser['role'] ?? '') === 'pj';

$error_verif  = '';
$pesan_sukses = $_SESSION['pesan_sukses'] ?? '';
unset($_SESSION['pesan_sukses']);

// 3. Eksekusi Aksi Terima / Tolak Pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_verifikasi'])) {
    $pembayaran_id = (int) ($_POST['pembayaran_id'] ?? 0);
    $aksi          = trim($_POST['aksi_verifikasi'] ?? '');

    if ($pembayaran_id > 0 && isset($pdo)) {
        try {
            $pdo->beginTransaction();

            $stmtCek = $pdo->prepare("
                SELECT 
                    p.id,
                    p.jumlah_bayar,
                    p.status_verifikasi,
                    t.id AS tagihan_id,
                    t.penghuni_kamar_id,
                    t.kategori_iuran_id,
                    pk.penghuni_id
                FROM pembayaran p
                JOIN tagihan t ON p.tagihan_id = t.id
                LEFT JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
                WHERE p.id = :id AND LOWER(TRIM(p.status_verifikasi)) = 'pending'
            ");
            $stmtCek->execute([':id' => $pembayaran_id]);
            $bayar = $stmtCek->fetch(PDO::FETCH_ASSOC);

            if (!$bayar) {
                throw new Exception("Data pembayaran tidak ditemukan atau sudah diproses.");
            }

            $is_sewa      = empty($bayar['kategori_iuran_id']);
            $tagihan_id   = (int)$bayar['tagihan_id'];
            $jumlah_bayar = (float)$bayar['jumlah_bayar'];
            $penghuni_id  = (int)($bayar['penghuni_id'] ?? 0);
            $user_auth_id = (int)($_SESSION['user']['id'] ?? 1);

            // Proteksi: Ketua kos dilarang memverifikasi sewa kamar
            if (!$is_pemilik && $is_sewa) {
                throw new Exception("Akses ditolak: Hanya Pemilik Kos yang berhak memverifikasi pembayaran sewa kamar.");
            }

            if ($aksi === 'terima') {
                // 1. Update status pembayaran menjadi 'diterima' (TANPA pemilik_id)
                $stmtUpdBayar = $pdo->prepare("
                    UPDATE pembayaran 
                    SET status_verifikasi = 'diterima', 
                        tanggal_bayar = NOW() 
                    WHERE id = :id
                ");
                $stmtUpdBayar->execute([':id' => $pembayaran_id]);

                // 2. Update status tagihan menjadi 'lunas'
                $stmtUpdTagihan = $pdo->prepare("
                    UPDATE tagihan 
                    SET status_tagihan = 'lunas' 
                    WHERE id = :tagihan_id
                ");
                $stmtUpdTagihan->execute([':tagihan_id' => $tagihan_id]);

                // 3. Catat transaksi ke buku_kas jika belum tercatat
                $stmtCekKas = $pdo->prepare("SELECT id FROM buku_kas WHERE pembayaran_id = :pid LIMIT 1");
                $stmtCekKas->execute([':pid' => $pembayaran_id]);
                $sudahAdaKas = $stmtCekKas->fetchColumn();

                if (!$sudahAdaKas) {
                    $ket_kas = $is_sewa ? 'Pembayaran Sewa Kamar Terverifikasi' : 'Pembayaran Iuran Terverifikasi';
                    $stmtKas = $pdo->prepare("
                        INSERT INTO buku_kas (
                            keterangan, 
                            tipe_transaksi, 
                            nominal, 
                            tanggal_transaksi, 
                            " . ($is_pemilik ? "pemilik_id" : "penghuni_id") . ", 
                            pembayaran_id
                        )
                        VALUES (:ket, 'pemasukan', :nominal, NOW(), :uid, :pembayaran_id)
                    ");
                    $stmtKas->execute([
                        ':ket'           => $ket_kas,
                        ':nominal'       => $jumlah_bayar,
                        ':uid'           => $is_pemilik ? $user_auth_id : $penghuni_id,
                        ':pembayaran_id' => $pembayaran_id
                    ]);
                }

                $_SESSION['pesan_sukses'] = "Pembayaran berhasil diterima dan dicatat ke kas.";
            } elseif ($aksi === 'tolak') {
                // 1. Update status pembayaran menjadi 'ditolak' (TANPA pemilik_id)
                $stmtUpdBayar = $pdo->prepare("
                    UPDATE pembayaran 
                    SET status_verifikasi = 'ditolak' 
                    WHERE id = :id
                ");
                $stmtUpdBayar->execute([':id' => $pembayaran_id]);

                // 2. Kembalikan status tagihan ke 'belum_bayar'
                $stmtUpdTagihan = $pdo->prepare("
                    UPDATE tagihan 
                    SET status_tagihan = 'belum_bayar' 
                    WHERE id = :tagihan_id
                ");
                $stmtUpdTagihan->execute([':tagihan_id' => $tagihan_id]);

                $_SESSION['pesan_sukses'] = "Bukti pembayaran berhasil ditolak.";
            }

            $pdo->commit();
            header("Location: /?page=verifikasi_bayar");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_verif = $e->getMessage();
        }
    }
}

// 4. Filter Rentang Tanggal
$date_range = trim($_GET['date_range'] ?? '');
if (!preg_match('/^\d{4}-\d{2}-\d{2}( to \d{4}-\d{2}-\d{2})?$/', $date_range)) {
    $date_range = '';
}

$tgl_mulai   = '';
$tgl_selesai = '';
if ($date_range !== '') {
    $parts       = explode(' to ', $date_range);
    $tgl_mulai   = trim($parts[0]);
    $tgl_selesai = trim($parts[1] ?? $tgl_mulai);
}

// 5. Query Ambil Data Pending Berdasarkan Role
$daftar_verifikasi = [];
if (isset($pdo) && ($pdo instanceof PDO)) {
    try {
        $sql = "
            SELECT 
                p.id AS pembayaran_id,
                p.jumlah_bayar,
                COALESCE(p.tanggal_bayar, NOW()) AS tanggal_bayar,
                p.bukti_pembayaran AS foto,
                p.metode_bayar,
                t.kategori_iuran_id,
                CASE 
                    WHEN t.kategori_iuran_id IS NOT NULL THEN 'Iuran'
                    ELSE 'Sewa'
                END AS tipe_tagihan,
                COALESCE(pg.nama, 'Penghuni') AS nama,
                COALESCE(pg.no_wa, '') AS no_wa,
                COALESCE(k.no_kamar, '-') AS kamar
            FROM pembayaran p
            JOIN tagihan t ON p.tagihan_id = t.id
            LEFT JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
            LEFT JOIN penghuni pg ON pk.penghuni_id = pg.id
            LEFT JOIN kamar k ON pk.kamar_id = k.id
            WHERE LOWER(TRIM(p.status_verifikasi)) = 'pending'
        ";

        // PEMILIK: Melihat Sewa dan Iuran
        // KETUA KOS (PJ): HANYA Melihat Iuran
        if (!$is_pemilik) {
            $sql .= " AND t.kategori_iuran_id IS NOT NULL ";
        }

        $params = [];
        if ($tgl_mulai !== '' && $tgl_selesai !== '') {
            $sql .= " AND DATE(p.tanggal_bayar) >= :tgl_mulai AND DATE(p.tanggal_bayar) <= :tgl_selesai ";
            $params[':tgl_mulai']   = $tgl_mulai;
            $params[':tgl_selesai'] = $tgl_selesai;
        }

        $sql .= " ORDER BY p.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $upload_dir = realpath(__DIR__ . '/../../../public/uploads/bukti');

        foreach ($raw_data as $row) {
            $foto_nama = basename(trim((string)($row['foto'] ?? '')));
            $src_final = '';

            if ($foto_nama !== '' && $upload_dir && file_exists($upload_dir . DIRECTORY_SEPARATOR . $foto_nama)) {
                $path_fisik = $upload_dir . DIRECTORY_SEPARATOR . $foto_nama;
                $ext = strtolower(pathinfo($path_fisik, PATHINFO_EXTENSION));
                $mime = ($ext === 'png') ? 'image/png' : (($ext === 'webp') ? 'image/webp' : 'image/jpeg');
                $src_final = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path_fisik));
            } else {
                $src_final = !empty($row['foto']) ? '/uploads/bukti/' . $foto_nama : '/uploads/bukti/bukti_001.jpg';
            }

            $row['src_foto']     = $src_final;
            $daftar_verifikasi[] = $row;
        }
    } catch (Exception $e) {
        $daftar_verifikasi = [];
    }
}

$is_filtered  = ($date_range !== '');
$current_page = $_GET['page'] ?? 'verifikasi_bayar';
$link_kembali = $is_pemilik ? '/?page=dashboard_pemilik' : '/?page=dashboard_penghuni';