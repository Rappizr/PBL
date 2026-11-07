<?php
// modules/penghuni/handler/tagihan_handler.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Mengambil ID penghuni_kamar berdasarkan session atau fallback nama
 */
function resolvePenghuniKamarId(PDO $pdo, array $sessionUser): int {
    $session_user_id = (int)($sessionUser['id'] ?? ($sessionUser['penghuni_id'] ?? 0));
    $nama_penghuni   = trim($sessionUser['nama'] ?? '');
    $pk_id           = (int)($sessionUser['penghuni_kamar_id'] ?? 0);

    if ($pk_id > 0) {
        return $pk_id;
    }

    // A. Cari via ID Penghuni
    if ($session_user_id > 0) {
        try {
            $stmtPK = $pdo->prepare("SELECT id FROM penghuni_kamar WHERE penghuni_id = :p_id ORDER BY id DESC LIMIT 1");
            $stmtPK->execute([':p_id' => $session_user_id]);
            $pk_id = (int)$stmtPK->fetchColumn();
            if ($pk_id > 0) return $pk_id;
        } catch (Exception $e) {}
    }

    // B. Cari via Nama Penghuni
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
            $pk_id = (int)$stmtNama->fetchColumn();
            if ($pk_id > 0) return $pk_id;
        } catch (Exception $e) {}
    }

    // C. Fallback khusus berdasarkan nama (Mukhammad = 10, Sesil = 12)
    return (strtolower($nama_penghuni) === 'sesil') ? 12 : 10;
}

/**
 * Mengambil daftar tagihan aktif penghuni
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
                t.status_tagihan
            FROM tagihan t
            JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
            LEFT JOIN skema_sewa s ON pk.skema_id = s.id
            LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
            WHERE t.penghuni_kamar_id = :pk_id
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
            WHERE pk.id = :pk_id AND LOWER(p.status_verifikasi) = 'diterima'
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
 * Menangani proses upload foto bukti transfer (POST)
 */
function handleUploadBuktiBayar(PDO $pdo): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['tagihan_id'])) {
        return ['sukses' => '', 'error' => ''];
    }

    $tagihan_id = (int)$_POST['tagihan_id'];
    
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === UPLOAD_ERR_OK) {
        $file_tmp  = $_FILES['bukti_transfer']['tmp_name'];
        $file_name = $_FILES['bukti_transfer']['name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $new_filename = 'bukti_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $upload_dir   = __DIR__ . '/../../../public/uploads/bukti/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
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

                $no_kwitansi = 'KW/' . date('Ym') . '/' . strtoupper(bin2hex(random_bytes(2)));
                $stmtBayar = $pdo->prepare("
                    INSERT INTO pembayaran (tagihan_id, bukti_pembayaran, jumlah_bayar, metode_bayar, status_verifikasi, no_kwitansi, tanggal_bayar)
                    VALUES (?, ?, ?, 'transfer', 'pending', ?, NOW())
                ");
                $stmtBayar->execute([$tagihan_id, $new_filename, $jumlah_bayar, $no_kwitansi]);

                $stmtUpTagihan = $pdo->prepare("UPDATE tagihan SET status_tagihan = 'sebagian' WHERE id = ?");
                $stmtUpTagihan->execute([$tagihan_id]);

                $pdo->commit();
                return ['sukses' => "Bukti pembayaran berhasil dikirim! Menunggu konfirmasi pemilik kos.", 'error' => ''];
            } catch (Exception $e) {
                $pdo->rollBack();
                return ['sukses' => '', 'error' => "Gagal memproses pembayaran: " . $e->getMessage()];
            }
        } else {
            return ['sukses' => '', 'error' => "Gagal mengunggah file foto bukti transfer."];
        }
    }
    return ['sukses' => '', 'error' => "Harap lampirkan file foto bukti pembayaran."];
}