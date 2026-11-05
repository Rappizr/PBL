<?php
/** @var PDO $pdo */
$current_page = $current_page ?? ($_GET['page'] ?? 'manajemen_kamar');
$error_simpan = $error_simpan ?? '';

// Pastikan tabel skema_sewa dan kolom harga_default tersedia
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS skema_sewa (
            id SERIAL PRIMARY KEY,
            nama_skema VARCHAR(50) NOT NULL UNIQUE,
            tipe_periode VARCHAR(20) NOT NULL DEFAULT 'bulan',
            durasi_nilai INTEGER NOT NULL DEFAULT 1,
            harga_default NUMERIC(12, 2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ALTER TABLE skema_sewa ADD COLUMN IF NOT EXISTS harga_default NUMERIC(12, 2) DEFAULT 0;
    ");
} catch (Exception $e) {}

// 1. HANDLER: TAMBAH / UPDATE SKEMA SEWA DINAMIS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_skema_baru'])) {
    $id_skema      = (int) ($_POST['id_skema'] ?? 0);
    $nama_skema    = trim($_POST['nama_skema'] ?? '');
    $tipe_periode  = trim($_POST['tipe_periode'] ?? 'bulan');
    $durasi_nilai  = (int) ($_POST['durasi_nilai'] ?? 1);

    $harga_raw     = trim($_POST['harga_default'] ?? '0');
    $harga_bersih  = str_replace('.', '', $harga_raw);
    $harga_default = (float) $harga_bersih;

    if ($nama_skema !== '' && $durasi_nilai > 0) {
        try {
            if ($id_skema > 0) {
                $stmt = $pdo->prepare("
                    UPDATE skema_sewa 
                    SET nama_skema = :nama, 
                        tipe_periode = :tipe, 
                        durasi_nilai = :durasi, 
                        harga_default = :harga
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':nama'   => $nama_skema,
                    ':tipe'   => $tipe_periode,
                    ':durasi' => $durasi_nilai,
                    ':harga'  => $harga_default,
                    ':id'     => $id_skema
                ]);
                $_SESSION['pesan_sukses'] = "Skema sewa berhasil diperbarui!";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO skema_sewa (nama_skema, tipe_periode, durasi_nilai, harga_default)
                    VALUES (:nama, :tipe, :durasi, :harga)
                    ON CONFLICT (nama_skema) DO UPDATE 
                    SET tipe_periode = EXCLUDED.tipe_periode, 
                        durasi_nilai = EXCLUDED.durasi_nilai, 
                        harga_default = EXCLUDED.harga_default
                ");
                $stmt->execute([
                    ':nama'   => $nama_skema,
                    ':tipe'   => $tipe_periode,
                    ':durasi' => $durasi_nilai,
                    ':harga'  => $harga_default
                ]);
                $_SESSION['pesan_sukses'] = "Skema sewa baru berhasil disimpan!";
            }

            header("Location: /?page=" . urlencode($current_page) . "&modal=kelola_skema");
            exit;
        } catch (PDOException $e) {
            $error_simpan = "Gagal memproses skema sewa: " . $e->getMessage();
        }
    } else {
        $error_simpan = "Nama skema dan durasi wajib diisi.";
    }
}

// 2. HANDLER: HAPUS SKEMA SEWA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_skema_item'])) {
    $id_skema = (int) ($_POST['id_skema'] ?? 0);
    if ($id_skema > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM skema_sewa WHERE id = :id");
            $stmt->execute([':id' => $id_skema]);
            $_SESSION['pesan_sukses'] = "Skema sewa berhasil dihapus!";

            header("Location: /?page=" . urlencode($current_page) . "&modal=kelola_skema");
            exit;
        } catch (PDOException $e) {
            $error_simpan = "Gagal menghapus skema: " . $e->getMessage();
        }
    }
}

// 3. HANDLER: TAMBAH KAMAR (HANYA NO KAMAR, TANPA HARGA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_kamar'])) {
    $no_kamar = preg_replace('/[^0-9]/', '', trim($_POST['no_kamar'] ?? ''));

    if ($no_kamar !== '') {
        try {
            $stmtCek = $pdo->prepare("SELECT id FROM kamar WHERE LOWER(no_kamar) = LOWER(:no_kamar) LIMIT 1");
            $stmtCek->execute([':no_kamar' => $no_kamar]);

if ($stmtCek->fetch()) {
    $_SESSION['error_simpan'] = "Nomor kamar {$no_kamar} sudah terdaftar!";
    header("Location: /?page=" . urlencode($current_page));
    exit;
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO kamar (no_kamar, status_kamar)
                    VALUES (:no_kamar, 'kosong')
                ");
                $stmt->execute([':no_kamar' => $no_kamar]);
                header("Location: /?page=" . urlencode($current_page));
                exit;
            }
} catch (PDOException $e) {
    $_SESSION['error_simpan'] = "Gagal menambah kamar: " . $e->getMessage();
    header("Location: /?page=" . urlencode($current_page));
    exit;
}
    } else {
        $error_simpan = "Nomor kamar wajib diisi.";
    }
}

// 4. HANDLER: TAMBAH PENGHUNI (DENGAN EMAIL, WALI, PIN, & KETUA KOST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_penghuni'])) {
    $nama         = trim($_POST['nama'] ?? '');
    $kamar_nomor  = trim($_POST['kamar_nomor'] ?? '');
    $no_wa        = preg_replace('/[^0-9]/', '', trim($_POST['no_wa'] ?? ''));
    $email        = trim($_POST['email'] ?? null);
    $kontak_wali  = preg_replace('/[^0-9]/', '', trim($_POST['kontak_wali'] ?? ''));
    $pin          = preg_replace('/[^0-9]/', '', trim($_POST['pin'] ?? '123456'));
    $is_ketua_kos = (isset($_POST['is_ketua_kos']) && $_POST['is_ketua_kos'] === '1') ? 'true' : 'false';
    $skema_id     = (int) ($_POST['skema_id'] ?? 0);
    $tgl_mulai    = $_POST['tgl_mulai'] ?? date('Y-m-d');

    if ($pin === '') {
        $pin = '123456';
    }
    if ($email === '') {
        $email = null;
    }

    $stmtSkema = $pdo->prepare("SELECT * FROM skema_sewa WHERE id = :id LIMIT 1");
    $stmtSkema->execute([':id' => $skema_id]);
    $skema_pilih = $stmtSkema->fetch(PDO::FETCH_ASSOC);

    $skema_teks   = $skema_pilih['nama_skema'] ?? 'Bulanan';
    $tipe_periode = $skema_pilih['tipe_periode'] ?? 'bulan';
    $durasi_nilai = (int) ($skema_pilih['durasi_nilai'] ?? 1);

    if ($tipe_periode === 'hari') {
        $tgl_selesai = date('Y-m-d', strtotime("+{$durasi_nilai} days", strtotime($tgl_mulai)));
    } else {
        $tgl_selesai = date('Y-m-d', strtotime("+{$durasi_nilai} months", strtotime($tgl_mulai)));
    }

    $nama_foto = 'ktp_default.jpg';
    if (!empty($_FILES['foto_ktp']['name'])) {
        $upload_dir = __DIR__ . '/../../../public/uploads/ktp/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = pathinfo($_FILES['foto_ktp']['name'], PATHINFO_EXTENSION);
        $nama_foto = 'ktp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        move_uploaded_file($_FILES['foto_ktp']['tmp_name'], $upload_dir . $nama_foto);
    }

    if ($nama !== '' && $kamar_nomor !== '') {
        try {
            $pdo->beginTransaction();

            $stmtKamar = $pdo->prepare("SELECT id FROM kamar WHERE no_kamar = :no LIMIT 1 FOR UPDATE");
            $stmtKamar->execute([':no' => $kamar_nomor]);
            $kamar = $stmtKamar->fetch(PDO::FETCH_ASSOC);

            if (!$kamar) {
                throw new Exception("Kamar nomor {$kamar_nomor} tidak ditemukan.");
            }

            // Gunakan harga default skema
            $harga_hitung = (float) ($skema_pilih['harga_default'] ?? 0);

            $stmtP = $pdo->prepare("
                INSERT INTO penghuni (nama, no_wa, email, kontak_wali, foto_ktp, pin, status_akun, is_ketua_kos)
                VALUES (:nama, :no_wa, :email, :kontak_wali, :foto_ktp, :pin, 'aktif', {$is_ketua_kos})
                RETURNING id
            ");
            $stmtP->execute([
                ':nama'        => $nama,
                ':no_wa'       => $no_wa,
                ':email'       => $email,
                ':kontak_wali' => $kontak_wali,
                ':foto_ktp'    => $nama_foto,
                ':pin'         => $pin
            ]);
            $penghuni_id = $stmtP->fetchColumn();

// 1. Simpan Kontrak Penghuni Kamar
            $stmtPK = $pdo->prepare("
                INSERT INTO penghuni_kamar (kamar_id, penghuni_id, status_kamar, harga_sewa, tanggal_masuk, tanggal_selesai, skema_sewa)
                VALUES (:kamar_id, :penghuni_id, 'aktif', :harga, :tgl_masuk, :tgl_selesai, :skema)
                RETURNING id
            ");
            $stmtPK->execute([
                ':kamar_id'    => $kamar['id'],
                ':penghuni_id' => $penghuni_id,
                ':harga'       => $harga_hitung,
                ':tgl_masuk'   => $tgl_mulai,
                ':tgl_selesai' => $tgl_selesai,
                ':skema'       => $skema_teks
            ]);
            $penghuni_kamar_id = $stmtPK->fetchColumn();

            // Otomatis Buat Tagihan Pertama (Status Belum Bayar)
            $periode_tagihan = date('F Y', strtotime($tgl_mulai));
            $tgl_jatuh_tempo = date('Y-m-d', strtotime('+7 days', strtotime($tgl_mulai)));

            $stmtTagihanBaru = $pdo->prepare("
                INSERT INTO tagihan (
                    penghuni_kamar_id, 
                    total_tagihan, 
                    status_tagihan, 
                    tanggal_jatuh_tempo, 
                    periode_tagihan
                )
                VALUES (:pk_id, :total, 'belum_bayar', :tgl_tempo, :periode)
            ");
            $stmtTagihanBaru->execute([
                ':pk_id'     => $penghuni_kamar_id,
                ':total'     => $harga_hitung,
                ':tgl_tempo' => $tgl_jatuh_tempo,
                ':periode'   => $periode_tagihan
            ]);

            // Ubah Status Kamar Menjadi Terisi
            $stmtUK = $pdo->prepare("UPDATE kamar SET status_kamar = 'terisi' WHERE id = :kamar_id");
            $stmtUK->execute([':kamar_id' => $kamar['id']]);

            $pdo->commit();
            header("Location: /?page=" . urlencode($current_page));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_simpan = "Gagal menambah penghuni: " . $e->getMessage();
        }
    } else {
        $error_simpan = "Nama lengkap dan nomor kamar wajib diisi.";
    }
}

// 5. HANDLER: SIMPAN DETAIL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_detail'])) {
    $nomor_kamar  = trim($_POST['nomor_kamar'] ?? '');
    $nama         = trim($_POST['nama'] ?? '');
    $no_wa        = preg_replace('/[^0-9]/', '', trim($_POST['no_wa'] ?? ''));
    $email        = trim($_POST['email'] ?? null);
    $kontak_wali  = preg_replace('/[^0-9]/', '', trim($_POST['kontak_wali'] ?? ''));
    $pin_baru     = preg_replace('/[^0-9]/', '', trim($_POST['pin'] ?? ''));
    $is_ketua_kos = (isset($_POST['is_ketua_kos']) && $_POST['is_ketua_kos'] === '1') ? 'true' : 'false';
    $skema_teks   = trim($_POST['durasi_sewa'] ?? '');
    $tgl_mulai    = $_POST['tgl_mulai'] ?? date('Y-m-d');
    $status_bayar = trim($_POST['status_bayar'] ?? 'Lunas');

    if ($email === '') {
        $email = null;
    }

    try {
        $pdo->beginTransaction();
        $stmtCari = $pdo->prepare("
            SELECT k.id AS kamar_id, pk.id AS penghuni_kamar_id, pk.penghuni_id
            FROM kamar k
            JOIN penghuni_kamar pk ON k.id = pk.kamar_id AND pk.status_kamar = 'aktif'
            WHERE k.no_kamar = :no
            LIMIT 1
        ");
        $stmtCari->execute([':no' => $nomor_kamar]);
        $data = $stmtCari->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $sqlUP = "
                UPDATE penghuni 
                SET nama = :nama, 
                    no_wa = :no_wa, 
                    email = :email, 
                    kontak_wali = :kontak_wali,
                    is_ketua_kos = {$is_ketua_kos}
            ";
            $paramsUP = [
                ':nama'        => $nama,
                ':no_wa'       => $no_wa,
                ':email'       => $email,
                ':kontak_wali' => $kontak_wali,
                ':id'          => $data['penghuni_id']
            ];

            if ($pin_baru !== '') {
                $sqlUP .= ", pin = :pin";
                $paramsUP[':pin'] = $pin_baru;
            }
            $sqlUP .= " WHERE id = :id";

            $stmtUP = $pdo->prepare($sqlUP);
            $stmtUP->execute($paramsUP);

            $stmtUPK = $pdo->prepare("
                UPDATE penghuni_kamar 
                SET skema_sewa = :skema, tanggal_masuk = :tgl_masuk
                WHERE id = :pk_id
            ");
            $stmtUPK->execute([
                ':skema'     => $skema_teks,
                ':tgl_masuk' => $tgl_mulai,
                ':pk_id'     => $data['penghuni_kamar_id']
            ]);

        $status_bayar_input = strtolower(trim($_POST['status_bayar'] ?? 'belum_bayar'));
        $status_simpan = ($status_bayar_input === 'lunas') ? 'lunas' : 'belum_bayar';

        $stmtTagihan = $pdo->prepare("
            UPDATE tagihan 
            SET status_tagihan = :status_bayar 
            WHERE id = (
                SELECT id FROM tagihan 
                WHERE penghuni_kamar_id = :pk_id 
                ORDER BY tanggal_jatuh_tempo DESC LIMIT 1
            )
        ");
        $stmtTagihan->execute([
            ':status_bayar' => $status_simpan,
            ':pk_id'        => $data['penghuni_kamar_id']
        ]);
        }
        $pdo->commit();
        header("Location: /?page=" . urlencode($current_page));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_simpan = "Gagal memperbarui data: " . $e->getMessage();
    }
}

// 6. HANDLER: HAPUS PENGHUNI (HAPUS KONTRAK SEWA SEKALIGUS AKUN PENGHUNI DARI DATABASE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_penghuni'])) {
    $nomor_kamar = trim($_POST['nomor_kamar'] ?? '');
    
    if ($nomor_kamar !== '') {
        try {
            $pdo->beginTransaction();
            
            // 1. Cari ID kamar, ID kontrak sewa, dan ID penghuni yang aktif di kamar tersebut
            $stmtCari = $pdo->prepare("
                SELECT k.id AS kamar_id, pk.id AS pk_id, pk.penghuni_id 
                FROM kamar k
                JOIN penghuni_kamar pk ON k.id = pk.kamar_id AND pk.status_kamar = 'aktif'
                WHERE k.no_kamar = :no
                LIMIT 1
            ");
            $stmtCari->execute([':no' => $nomor_kamar]);
            $row = $stmtCari->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $penghuni_id = (int)$row['penghuni_id'];
                $kamar_id    = (int)$row['kamar_id'];
                $pk_id       = (int)$row['pk_id'];

                // 2. Hapus catatan pembayaran jika ada (relasi pembayaran -> tagihan)
                $pdo->prepare("
                    DELETE FROM pembayaran 
                    WHERE tagihan_id IN (
                        SELECT id FROM tagihan WHERE penghuni_kamar_id = :pk_id
                    )
                ")->execute([':pk_id' => $pk_id]);

                // 3. Hapus catatan tagihan terkait kontrak sewa ini
                $pdo->prepare("DELETE FROM tagihan WHERE penghuni_kamar_id = :pk_id")
                    ->execute([':pk_id' => $pk_id]);

                // 4. Hapus riwayat/kontrak sewa dari penghuni_kamar
                $pdo->prepare("DELETE FROM penghuni_kamar WHERE id = :pk_id")
                    ->execute([':pk_id' => $pk_id]);

                // 5. Hapus akun penghuni secara permanen dari tabel penghuni
                $pdo->prepare("DELETE FROM penghuni WHERE id = :id")
                    ->execute([':id' => $penghuni_id]);

                // 6. Kembalikan status kamar menjadi 'kosong'
                $pdo->prepare("UPDATE kamar SET status_kamar = 'kosong' WHERE id = :kamar_id")
                    ->execute([':kamar_id' => $kamar_id]);

                $_SESSION['pesan_sukses'] = "Penghuni dan seluruh data akunnya berhasil dihapus permanen.";
            }

            $pdo->commit();
            header("Location: /?page=" . urlencode($current_page));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_simpan = "Gagal menghapus akun penghuni: " . $e->getMessage();
        }
    }
}

// 7. HANDLER: PINDAH KAMAR PENGHUNI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_pindah_kamar'])) {
    $nomor_kamar_asal   = trim($_POST['nomor_kamar_asal'] ?? '');
    $nomor_kamar_tujuan = trim($_POST['nomor_kamar_tujuan'] ?? '');

    if ($nomor_kamar_asal !== '' && $nomor_kamar_tujuan !== '' && $nomor_kamar_asal !== $nomor_kamar_tujuan) {
        try {
            $pdo->beginTransaction();

            $stmtAsal = $pdo->prepare("
                SELECT k.id AS kamar_id, pk.id AS pk_id 
                FROM kamar k
                JOIN penghuni_kamar pk ON k.id = pk.kamar_id AND pk.status_kamar = 'aktif'
                WHERE k.no_kamar = :no
                LIMIT 1
            ");
            $stmtAsal->execute([':no' => $nomor_kamar_asal]);
            $asal = $stmtAsal->fetch(PDO::FETCH_ASSOC);

            $stmtTujuan = $pdo->prepare("
                SELECT id FROM kamar 
                WHERE no_kamar = :no AND status_kamar = 'kosong'
                LIMIT 1
            ");
            $stmtTujuan->execute([':no' => $nomor_kamar_tujuan]);
            $tujuan = $stmtTujuan->fetch(PDO::FETCH_ASSOC);

            if (!$asal) {
                throw new Exception("Kamar asal tidak ditemukan atau tidak berpenghuni.");
            }
            if (!$tujuan) {
                throw new Exception("Kamar tujuan tidak ditemukan atau sudah terisi.");
            }

            $stmtPindah = $pdo->prepare("UPDATE penghuni_kamar SET kamar_id = :kamar_baru WHERE id = :pk_id");
            $stmtPindah->execute([
                ':kamar_baru' => $tujuan['id'],
                ':pk_id'      => $asal['pk_id']
            ]);

            $stmtKosongkan = $pdo->prepare("UPDATE kamar SET status_kamar = 'kosong' WHERE id = :id");
            $stmtKosongkan->execute([':id' => $asal['kamar_id']]);

            $stmtIsi = $pdo->prepare("UPDATE kamar SET status_kamar = 'terisi' WHERE id = :id");
            $stmtIsi->execute([':id' => $tujuan['id']]);

            $pdo->commit();
            header("Location: /?page=" . urlencode($current_page) . "&modal=detail_kamar&nomor=" . urlencode($nomor_kamar_tujuan));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_simpan = "Gagal memindahkan kamar: " . $e->getMessage();
        }
    } else {
        $error_simpan = "Pilih kamar tujuan yang valid.";
    }
}

// 8. HANDLER: HAPUS KAMAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_kamar'])) {
    $nomor_kamar = trim($_POST['nomor_kamar'] ?? '');
    try {
        $pdo->beginTransaction();
        $stmtCari = $pdo->prepare("SELECT id FROM kamar WHERE no_kamar = :no LIMIT 1");
        $stmtCari->execute([':no' => $nomor_kamar]);
        $kamar_id = $stmtCari->fetchColumn();

        if ($kamar_id) {
            $stmtDelPK = $pdo->prepare("DELETE FROM penghuni_kamar WHERE kamar_id = :id");
            $stmtDelPK->execute([':id' => $kamar_id]);

            $stmtDelK = $pdo->prepare("DELETE FROM kamar WHERE id = :id");
            $stmtDelK->execute([':id' => $kamar_id]);

            $pdo->commit();
            header("Location: /?page=" . urlencode($current_page));
            exit;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_simpan = "Gagal menghapus kamar: " . $e->getMessage();
    }
}