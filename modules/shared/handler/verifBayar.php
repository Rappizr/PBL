<?php
/** @var PDO $pdo */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error_verif = $error_verif ?? '';

// HANDLE AKSI TERIMA / TOLAK PEMBAYARAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_verifikasi'])) {
    $pembayaran_id = (int) ($_POST['pembayaran_id'] ?? 0);
    $aksi          = trim($_POST['aksi_verifikasi'] ?? ''); // 'terima' atau 'tolak'

    if ($pembayaran_id > 0) {
        try {
            $pdo->beginTransaction();

            // 1. Ambil data pembayaran dan tagihan terkait
            $stmtCek = $pdo->prepare("
                SELECT p.*, t.id AS tagihan_id, t.penghuni_kamar_id 
                FROM pembayaran p
                JOIN tagihan t ON p.tagihan_id = t.id
                WHERE p.id = :id AND p.status_verifikasi = 'pending'
                FOR UPDATE
            ");
            $stmtCek->execute([':id' => $pembayaran_id]);
            $bayar = $stmtCek->fetch(PDO::FETCH_ASSOC);

            if (!$bayar) {
                throw new Exception("Data pembayaran tidak ditemukan atau sudah diverifikasi.");
            }

            $tagihan_id   = $bayar['tagihan_id'];
            $jumlah_bayar = $bayar['jumlah_bayar'];
            $pemilik_id   = $_SESSION['user']['id'] ?? 1;

            if ($aksi === 'terima') {
                // A. Update status pembayaran jadi 'diterima'
                $stmtUpdBayar = $pdo->prepare("
                    UPDATE pembayaran 
                    SET status_verifikasi = 'diterima', pemilik_id = :pemilik_id 
                    WHERE id = :id
                ");
                $stmtUpdBayar->execute([
                    ':pemilik_id' => $pemilik_id,
                    ':id'         => $pembayaran_id
                ]);

                // B. Update status tagihan jadi 'lunas'
                $stmtUpdTagihan = $pdo->prepare("
                    UPDATE tagihan 
                    SET status_tagihan = 'lunas' 
                    WHERE id = :tagihan_id
                ");
                $stmtUpdTagihan->execute([':tagihan_id' => $tagihan_id]);

                // C. Catat otomatis ke buku_kas sebagai pemasukan
                $stmtKas = $pdo->prepare("
                    INSERT INTO buku_kas (keterangan, tipe_transaksi, nominal, pemilik_id, pembayaran_id)
                    VALUES (:ket, 'pemasukan', :nominal, :pemilik_id, :pembayaran_id)
                    ON CONFLICT (pembayaran_id) DO NOTHING
                ");
                $stmtKas->execute([
                    ':ket'           => 'Pembayaran terverifikasi dari tagihan #' . $tagihan_id,
                    ':nominal'       => $jumlah_bayar,
                    ':pemilik_id'    => $pemilik_id,
                    ':pembayaran_id' => $pembayaran_id
                ]);

                $_SESSION['pesan_sukses'] = "Pembayaran berhasil diterima dan dicatat ke buku kas!";
            } elseif ($aksi === 'tolak') {
                // Update status pembayaran jadi 'ditolak'
                $stmtUpdBayar = $pdo->prepare("
                    UPDATE pembayaran 
                    SET status_verifikasi = 'ditolak', pemilik_id = :pemilik_id 
                    WHERE id = :id
                ");
                $stmtUpdBayar->execute([
                    ':pemilik_id' => $pemilik_id,
                    ':id'         => $pembayaran_id
                ]);

                $_SESSION['pesan_sukses'] = "Pembayaran berhasil ditolak.";
            }

            $pdo->commit();
            header("Location: /?page=verifikasi_pembayaran");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_verif = "Gagal memproses verifikasi: " . $e->getMessage();
        }
    }
}