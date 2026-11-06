<?php
// modules/penghuni/handler/tagihan_riwayat_handlers.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Koneksi Database PDO secara Fleksibel
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $possible_paths = [
        __DIR__ . '/../../../config/database.php',
        __DIR__ . '/../../config/database.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/database.php',
        dirname(__DIR__, 2) . '/config/database.php',
        dirname(__DIR__, 3) . '/config/database.php'
    ];
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

// Ambil ID penghuni yang sedang login dari session (sesuaikan dengan struktur session login Anda)
$penghuni_id = $_SESSION['user']['id'] ?? 1; 
$nama_penghuni = $_SESSION['user']['nama'] ?? "Penghuni";

$daftar_tagihan_saya = [];
$semua_riwayat = [];
$sukses_pesan = '';
$error_pesan = '';

if (isset($pdo) && $pdo instanceof PDO) {

    // A. PROSES POST: KELOLA UPLOAD BUKTI PEMBAYARAN / KIRIM BUKTI
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tagihan_id'])) {
        $tagihan_id = (int)$_POST['tagihan_id'];
        
        if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['bukti_transfer']['tmp_name'];
            $file_name = $_FILES['bukti_transfer']['name'];
            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            
            // Buat nama file unik untuk bukti pembayaran
            $new_filename = 'bukti_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $upload_dir = __DIR__ . '/../../../public/uploads/bukti/';
            
            // Pastikan folder ada
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                try {
                    $pdo->beginTransaction();

                    // Ambil nominal tagihan untuk dimasukkan ke tabel pembayaran
                    $stmtTagihan = $pdo->prepare("SELECT total_tagihan FROM tagihan WHERE id = ?");
                    $stmtTagihan->execute([$tagihan_id]);
                    $tagihanData = $stmtTagihan->fetch(PDO::FETCH_ASSOC);
                    $jumlah_bayar = $tagihanData['total_tagihan'] ?? 0;

                    // Insert ke tabel pembayaran (status_verifikasi default 'pending')
                    $stmtBayar = $pdo->prepare("
                        INSERT INTO pembayaran (tagihan_id, bukti_pembayaran, jumlah_bayar, metode_bayar, status_verifikasi, no_kwitansi)
                        VALUES (?, ?, ?, 'transfer', 'pending', ?)
                    ");
                    $no_kwitansi = 'KW/' . date('Ym') . '/' . rand(1000, 9999);
                    $stmtBayar->execute([$tagihan_id, $new_filename, $jumlah_bayar, $no_kwitansi]);

                    // Update status tagihan menjadi 'sebagian' atau 'menunggu verifikasi' (jika ada)
                    $stmtUpTagihan = $pdo->prepare("UPDATE tagihan SET status_tagihan = 'sebagian' WHERE id = ?");
                    $stmtUpTagihan->execute([$tagihan_id]);

                    $pdo->commit();
                    $sukses_pesan = "Bukti pembayaran berhasil dikirim dan sedang menunggu verifikasi pemilik.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error_pesan = "Gagal memproses pembayaran: " . $e->getMessage();
                }
            } else {
                $error_pesan = "Gagal mengunggah file foto bukti transfer.";
            }
        } else {
            $error_pesan = "Harap lampirkan file foto bukti pembayaran.";
        }
    }

    // B. AMBIL DATA TAGIHAN SAYA (STATUS BELUM BAYAR / SEBAGIAN)
    try {
        $sqlTagihan = "
            SELECT 
                t.id,
                COALESCE(ki.nama_iuran, 'Sewa Kos') AS jenis,
                t.total_tagihan AS nominal,
                TO_CHAR(t.tanggal_jatuh_tempo, 'DD-MM-YY') AS jatuh_tempo
            FROM tagihan t
            JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
            LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
            WHERE pk.penghuni_id = ? AND t.status_tagihan IN ('belum_bayar', 'sebagian', 'terlambat')
            ORDER BY t.tanggal_jatuh_tempo ASC
        ";
        $stmtT = $pdo->prepare($sqlTagihan);
        $stmtT->execute([$penghuni_id]);
        $daftar_tagihan_saya = $stmtT->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $daftar_tagihan_saya = [];
    }

    // C. AMBIL DATA RIWAYAT TRANSAKSI (YANG SUDAH LUNAS / DITERIMA)
    try {
        $sqlRiwayat = "
            SELECT 
                p.id,
                COALESCE(ki.nama_iuran, 'Sewa Bulanan') AS judul,
                TO_CHAR(p.tanggal_bayar, 'DD Month YYYY') AS tanggal,
                TO_CHAR(p.tanggal_bayar, 'YYYY-MM-DD') AS tanggal_filter,
                p.jumlah_bayar AS nominal,
                p.no_kwitansi AS nomor_invoice,
                TO_CHAR(t.tanggal_jatuh_tempo - INTERVAL '10 day', 'DD Month YYYY') AS tanggal_terbit,
                TO_CHAR(t.tanggal_jatuh_tempo, 'DD Month YYYY') AS jatuh_tempo,
                TO_CHAR(p.tanggal_bayar, 'DD Month YYYY, HH24:MI') || ' WIB' AS tanggal_bayar,
                COALESCE(t.periode_tagihan, 'Bulanan') AS periode_sewa,
                COALESCE(k.no_kamar, '-') AS no_kamar
            FROM pembayaran p
            JOIN tagihan t ON p.tagihan_id = t.id
            JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
            LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
            LEFT JOIN kamar k ON pk.kamar_id = k.id
            WHERE pk.penghuni_id = ? AND p.status_verifikasi = 'diterima'
            ORDER BY p.tanggal_bayar DESC
        ";
        $stmtR = $pdo->prepare($sqlRiwayat);
        $stmtR->execute([$penghuni_id]);
        $semua_riwayat = $stmtR->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $semua_riwayat = [];
    }
}

// 3. LOGIKA FILTER RENTANG TANGGAL UNTUK RIWAYAT
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

$daftar_riwayat = array_filter($semua_riwayat, function ($r) use ($tgl_mulai, $tgl_selesai) {
    if ($tgl_mulai === '') {
        return true;
    }
    return ($r['tanggal_filter'] >= $tgl_mulai && $r['tanggal_filter'] <= $tgl_selesai);
});

$is_filtered = ($date_range !== '');

// 4. DETEKSI MODAL BAYAR & INVOICE
$modal_bayar_id = isset($_GET['bayar_id']) ? (int)$_GET['bayar_id'] : null;
$tagihan_dipilih = null;
if ($modal_bayar_id) {
    foreach ($daftar_tagihan_saya as $item) {
        if ($item['id'] === $modal_bayar_id) {
            $tagihan_dipilih = $item;
            break;
        }
    }
}

$modal_invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : null;
$invoice_dipilih = null;
if ($modal_invoice_id) {
    foreach ($semua_riwayat as $item) {
        if ($item['id'] === $modal_invoice_id) {
            $invoice_dipilih = $item;
            break;
        }
    }
}