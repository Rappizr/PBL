<?php
// modules/penghuni/handler/tagihan_handler.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Mengambil ID penghuni_kamar berdasarkan session secara akurat
 */
function resolvePenghuniKamarId(PDO $pdo, array $sessionUser): int {
    $pk_id = (int)($sessionUser['penghuni_kamar_id'] ?? 0);
    if ($pk_id > 0) {
        return $pk_id;
    }

    $session_user_id = (int)($sessionUser['id'] ?? ($sessionUser['penghuni_id'] ?? 0));
    $nama_penghuni   = trim($sessionUser['nama'] ?? '');
    $username        = trim($sessionUser['username'] ?? '');

    // 1. Cari via ID Penghuni
    if ($session_user_id > 0) {
        try {
            $stmtPK = $pdo->prepare("SELECT id FROM penghuni_kamar WHERE penghuni_id = :p_id ORDER BY id DESC LIMIT 1");
            $stmtPK->execute([':p_id' => $session_user_id]);
            $found = (int)$stmtPK->fetchColumn();
            if ($found > 0) return $found;
        } catch (Exception $e) {}
    }

    // 2. Cari via Nama Lengkap
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

    // 3. Cari via Username
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

/**
 * Mengambil daftar tagihan aktif penghuni (termasuk status verifikasi & alasan penolakan)
 */
function fetchDaftarTagihan(PDO $pdo, int $pk_id): array {
    if ($pk_id <= 0) return [];

    try {
        $sqlFetch = "
            SELECT 
                t.id,
                CASE 
                    WHEN ki.nama_iuran IS NOT NULL THEN ki.nama_iuran
                    ELSE 'Sewa'
                END AS jenis,
                COALESCE(ki.nominal, s.nominal, 5500000) AS nominal,
                TO_CHAR(t.tanggal_jatuh_tempo, 'DD-MM-YY') AS jatuh_tempo,
                t.status_tagihan,
                LOWER(TRIM(pem.status_verifikasi)) AS status_verif,
                pem.alasan_penolakan
            FROM tagihan t
            JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
            LEFT JOIN skema_sewa s ON pk.skema_id = s.id
            LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
            LEFT JOIN (
                SELECT tagihan_id, MAX(id) AS max_pem_id
                FROM pembayaran
                GROUP BY tagihan_id
            ) lp ON lp.tagihan_id = t.id
            LEFT JOIN pembayaran pem ON pem.id = lp.max_pem_id
            WHERE t.penghuni_kamar_id = :pk_id
              AND LOWER(TRIM(t.status_tagihan)) != 'lunas'
            ORDER BY t.id ASC
        ";
        $stmt = $pdo->prepare($sqlFetch);
        $stmt->execute([':pk_id' => $pk_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Mengambil riwayat pembayaran yang sudah disetujui (diterima)
 */
function fetchRiwayatTransaksi(PDO $pdo, int $pk_id): array {
    if ($pk_id <= 0) return [];

    try {
        $sqlRiwayat = "
            SELECT 
                p.id,
                CASE 
                    WHEN ki.nama_iuran IS NOT NULL THEN ki.nama_iuran
                    ELSE 'Sewa ' || COALESCE(s.nama_skema, 'Bulanan')
                END AS judul,
                TO_CHAR(p.tanggal_bayar, 'DD Month YYYY') AS tanggal,
                TO_CHAR(p.tanggal_bayar, 'YYYY-MM-DD') AS tanggal_filter,
                p.jumlah_bayar AS nominal,
                p.no_kwitansi AS nomor_invoice,
                TO_CHAR(COALESCE(t.tanggal_awal_tagihan, p.tanggal_bayar), 'DD Month YYYY') AS tanggal_terbit,
                TO_CHAR(t.tanggal_jatuh_tempo, 'DD Month YYYY') AS jatuh_tempo,
                TO_CHAR(p.tanggal_bayar, 'DD Month YYYY, HH24:MI') || ' WIB' AS tanggal_bayar,
                COALESCE(s.nama_skema, 'Bulanan') AS periode_sewa,
                COALESCE(k.no_kamar, '-') AS no_kamar
            FROM pembayaran p
            JOIN tagihan t ON p.tagihan_id = t.id
            JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
            LEFT JOIN skema_sewa s ON pk.skema_id = s.id
            LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
            LEFT JOIN kamar k ON pk.kamar_id = k.id
            WHERE pk.id = :pk_id AND LOWER(TRIM(p.status_verifikasi)) = 'diterima'
            ORDER BY p.tanggal_bayar DESC
        ";
        $stmt = $pdo->prepare($sqlRiwayat);
        $stmt->execute([':pk_id' => $pk_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Menangani proses upload bukti bayar (Transfer & Tunai)
 */
function handleUploadBuktiBayar(PDO $pdo): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['tagihan_id'])) {
        return ['sukses' => '', 'error' => ''];
    }

    $tagihan_id   = (int)$_POST['tagihan_id'];
    $metode_bayar = strtolower(trim($_POST['metode_bayar'] ?? 'transfer'));
    if (!in_array($metode_bayar, ['transfer', 'tunai'], true)) {
        $metode_bayar = 'transfer';
    }

    $new_filename = null;

    if ($metode_bayar === 'transfer') {
        if (!isset($_FILES['bukti_transfer']) || $_FILES['bukti_transfer']['error'] !== UPLOAD_ERR_OK) {
            return ['sukses' => '', 'error' => "Harap lampirkan file foto bukti transfer."];
        }

        $file_tmp  = $_FILES['bukti_transfer']['tmp_name'];
        $file_name = $_FILES['bukti_transfer']['name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $new_filename = 'bukti_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $upload_dir   = __DIR__ . '/../../../public/uploads/bukti/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (!move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
            return ['sukses' => '', 'error' => "Gagal mengunggah file foto bukti transfer."];
        }
    }

    try {
        $pdo->beginTransaction();

        $stmtTag = $pdo->prepare("
            SELECT t.id, COALESCE(ki.nominal, s.nominal, 5500000) AS jumlah_bayar
            FROM tagihan t
            JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
            LEFT JOIN skema_sewa s ON pk.skema_id = s.id
            LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
            WHERE t.id = ? LIMIT 1
        ");
        $stmtTag->execute([$tagihan_id]);
        $tagData = $stmtTag->fetch(PDO::FETCH_ASSOC);
        $jumlah_bayar = (float)($tagData['jumlah_bayar'] ?? 5500000);

        // Cek baris pembayaran pending / ditolak yang sudah ada
        $stmtCek = $pdo->prepare("
            SELECT id 
            FROM pembayaran 
            WHERE tagihan_id = :tid 
              AND LOWER(TRIM(status_verifikasi)) IN ('pending', 'ditolak')
            ORDER BY id DESC LIMIT 1
        ");
        $stmtCek->execute([':tid' => $tagihan_id]);
        $existingId = $stmtCek->fetchColumn();

        if ($existingId) {
            // Jika tunai, kolom bukti_pembayaran dikosongkan (NULL)
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
                ':id'      => $existingId
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

        $stmtUpTagihan = $pdo->prepare("UPDATE tagihan SET status_tagihan = 'sebagian' WHERE id = ?");
        $stmtUpTagihan->execute([$tagihan_id]);

        $pdo->commit();

        $pesan = ($metode_bayar === 'tunai') 
            ? "Konfirmasi bayar tunai berhasil dikirim! Silakan serahkan uang ke pemilik/pengurus kos."
            : "Bukti pembayaran transfer berhasil dikirim! Menunggu konfirmasi verifikasi.";

        return ['sukses' => $pesan, 'error' => ''];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['sukses' => '', 'error' => "Gagal memproses pembayaran: " . $e->getMessage()];
    }
}