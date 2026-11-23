<?php
/**
 * HANDLER LOGIK & DATABASE VERIFIKASI PEMBAYARAN
 * Mendukung verifikasi pelunasan penuh maupun cicilan bertahap.
 * Lokasi: modules/shared/handler/verifikasi_handler.php
 */

require_once __DIR__ . '/../../../config/init.php';
/** @var PDO $pdo */

require_once __DIR__ . '/sewa_reminder_helper.php';

$currentUser = $_SESSION['user'] ?? [];
$is_pemilik  = ($currentUser['role'] ?? '') === 'pemilik';
$is_ketua    = !empty($currentUser['is_pj']) || !empty($currentUser['is_ketua_kos']) || ($currentUser['role'] ?? '') === 'pj';

$error_verif  = '';
$pesan_sukses = $_SESSION['pesan_sukses'] ?? '';
unset($_SESSION['pesan_sukses']);

// Eksekusi Aksi Terima / Tolak Pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_verifikasi'])) {
    $pembayaran_id = (int) ($_POST['pembayaran_id'] ?? 0);
    $aksi          = trim($_POST['aksi_verifikasi'] ?? '');

    if ($pembayaran_id > 0 && isset($pdo) && ($pdo instanceof PDO)) {
        try {
            $pdo->beginTransaction();

            $stmtCek = $pdo->prepare("
                SELECT 
                    p.id,
                    p.jumlah_bayar,
                    p.metode_bayar,
                    p.status_verifikasi,
                    t.id AS tagihan_id,
                    t.penghuni_kamar_id,
                    t.kategori_iuran_id,
                    COALESCE(ki.nominal, s.nominal, 0) AS total_tagihan,
                    pk.penghuni_id,
                    pen.nama AS nama_penghuni,
                    pen.no_wa AS no_wa_penghuni,
                    LOWER(TRIM(COALESCE(pen.status_akun, 'aktif'))) AS status_akun,
                    k.no_kamar
                FROM pembayaran p
                JOIN tagihan t ON p.tagihan_id = t.id
                LEFT JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
                LEFT JOIN skema_sewa s ON pk.skema_id = s.id
                LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
                LEFT JOIN penghuni pen ON pk.penghuni_id = pen.id
                LEFT JOIN kamar k ON pk.kamar_id = k.id
                WHERE p.id = :id AND LOWER(TRIM(p.status_verifikasi)) = 'pending'
            ");
            $stmtCek->execute([':id' => $pembayaran_id]);
            $bayar = $stmtCek->fetch(PDO::FETCH_ASSOC);

            if (!$bayar) {
                throw new Exception("Data pembayaran tidak ditemukan atau sudah diproses.");
            }

            $is_sewa       = empty($bayar['kategori_iuran_id']);
            $tagihan_id    = (int)$bayar['tagihan_id'];
            $jumlah_bayar  = (float)$bayar['jumlah_bayar'];
            $total_tagihan = (float)$bayar['total_tagihan'];
            $penghuni_id   = (int)($bayar['penghuni_id'] ?? 0);
            $nama_pen      = $bayar['nama_penghuni'] ?? 'Penghuni';
            $no_wa_pen     = preg_replace('/[^0-9]/', '', (string)($bayar['no_wa_penghuni'] ?? ''));
            $kamar_pen     = $bayar['no_kamar'] ?? '-';
            $status_akun   = $bayar['status_akun'] ?? 'aktif';
            $tipe_label    = $is_sewa ? 'Sewa Kamar' : 'Iuran Kos';

            // Proteksi Hak Akses: Ketua kos dilarang memverifikasi sewa kamar
            if (!$is_pemilik && $is_sewa) {
                throw new Exception("Akses ditolak: Hanya Pemilik Kos yang berhak memverifikasi pembayaran sewa kamar.");
            }

            if ($aksi === 'terima') {
                // 1. Update status pembayaran menjadi 'diterima'
                $stmtUpdBayar = $pdo->prepare("
                    UPDATE pembayaran 
                    SET status_verifikasi = 'diterima', 
                        tanggal_bayar = NOW() 
                    WHERE id = :id
                ");
                $stmtUpdBayar->execute([':id' => $pembayaran_id]);

                // 2. Hitung akumulasi pembayaran 'diterima' pada tagihan ini (untuk menentukan lunas vs sebagian)
                $stmtTotalMasuk = $pdo->prepare("
                    SELECT COALESCE(SUM(jumlah_bayar), 0)
                    FROM pembayaran
                    WHERE tagihan_id = :tid
                      AND LOWER(TRIM(status_verifikasi)) = 'diterima'
                ");
                $stmtTotalMasuk->execute([':tid' => $tagihan_id]);
                $totalDiterima    = (float)$stmtTotalMasuk->fetchColumn();
                $sisaSetelahBayar = max(0, $total_tagihan - $totalDiterima);

                // Jika sisa <= 0 atau pembayaran tunggal sudah >= target tagihan, pastikan status langsung 'lunas'
                $statusBaru = ($sisaSetelahBayar <= 0 || $jumlah_bayar >= $total_tagihan) ? 'lunas' : 'sebagian';

                $stmtUpdTagihan = $pdo->prepare("
                    UPDATE tagihan 
                    SET status_tagihan = :status_baru,
                        reminder_sent_by = NULL,
                        reminder_sent_at = NULL,
                        reminder_fase = NULL
                    WHERE id = :tagihan_id
                ");
                $stmtUpdTagihan->execute([
                    ':status_baru' => $statusBaru,
                    ':tagihan_id'  => $tagihan_id
                ]);

                // 3. Catat ke buku_kas HANYA JIKA IURAN BERSAMA
                if (!$is_sewa) {
                    $stmtCekKas = $pdo->prepare("SELECT id FROM buku_kas WHERE pembayaran_id = :pid LIMIT 1");
                    $stmtCekKas->execute([':pid' => $pembayaran_id]);
                    $sudahAdaKas = $stmtCekKas->fetchColumn();

                    if (!$sudahAdaKas) {
                        $ketKas = ($statusBaru === 'lunas')
                            ? 'Pembayaran Iuran Terverifikasi (' . $nama_pen . ')'
                            : 'Cicilan Iuran Terverifikasi (' . $nama_pen . ')';

                        $stmtKas = $pdo->prepare("
                            INSERT INTO buku_kas (keterangan, tipe_transaksi, nominal, tanggal_transaksi, penghuni_id, pembayaran_id)
                            VALUES (:ket, 'pemasukan', :nominal, NOW(), :uid, :pembayaran_id)
                        ");
                        $stmtKas->execute([
                            ':ket'           => $ketKas,
                            ':nominal'       => $jumlah_bayar,
                            ':uid'           => $penghuni_id,
                            ':pembayaran_id' => $pembayaran_id
                        ]);
                    }
                }

                // 4. Auto-Unfreeze jika akun penghuni berstatus 'freeze' dan tidak ada tunggakan melewati tenggang
                $infoUnfreeze = '';
                if ($penghuni_id > 0 && $status_akun === 'freeze') {
                    $stmtCekSewa = $pdo->prepare("
                        SELECT COUNT(t.id)
                        FROM tagihan t
                        JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
                        WHERE pk.penghuni_id = :pid
                          AND t.kategori_iuran_id IS NULL
                          AND LOWER(TRIM(t.status_tagihan)) != 'lunas'
                          AND CURRENT_DATE > GREATEST(
                              (t.tanggal_jatuh_tempo + INTERVAL '7 days')::date,
                              COALESCE(t.tanggal_janji_bayar, '1970-01-01'::date)
                          )
                    ");
                    $stmtCekSewa->execute([':pid' => $penghuni_id]);
                    $masihNunggakSewa = ((int)$stmtCekSewa->fetchColumn() > 0);

                    $stmtCekIuran = $pdo->prepare("
                        SELECT COUNT(t.id)
                        FROM tagihan t
                        JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
                        JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
                        WHERE pk.penghuni_id = :pid
                          AND LOWER(TRIM(t.status_tagihan)) != 'lunas'
                          AND CURRENT_DATE > GREATEST(
                              (t.tanggal_jatuh_tempo + INTERVAL '7 days')::date,
                              COALESCE(t.tanggal_janji_bayar, '1970-01-01'::date)
                          )
                    ");
                    $stmtCekIuran->execute([':pid' => $penghuni_id]);
                    $masihNunggakIuran = ((int)$stmtCekIuran->fetchColumn() > 0);

                    if (!$masihNunggakSewa && !$masihNunggakIuran) {
                        $stmtAktifkan = $pdo->prepare("
                            UPDATE penghuni 
                            SET status_akun = 'aktif', 
                                freeze_reason = NULL
                            WHERE id = :pid
                        ");
                        $stmtAktifkan->execute([':pid' => $penghuni_id]);
                        $infoUnfreeze = " Akun {$nama_pen} otomatis diaktifkan kembali (Unfreeze).";
                    }
                }

                $pdo->commit();
                if (function_exists('bersihkanCacheNotifSiteman')) {
                    bersihkanCacheNotifSiteman();
                }

                $infoSisa = ($statusBaru === 'sebagian')
                    ? " (Cicilan diterima, sisa tagihan: Rp " . number_format($sisaSetelahBayar, 0, ',', '.') . ")."
                    : " (Tagihan LUNAS).";

                $_SESSION['pesan_sukses'] = ($is_sewa
                    ? "Pembayaran sewa berhasil diterima{$infoSisa}"
                    : "Pembayaran iuran berhasil diterima dan dicatat ke kas bersama{$infoSisa}") . $infoUnfreeze;
                header("Location: /?page=verifikasi_bayar");
                exit;

            } elseif ($aksi === 'tolak') {
                $alasan_pilihan = trim($_POST['alasan_penolakan'] ?? 'Foto / Bukti transfer buram & tidak terbaca');
                $alasan_custom  = trim($_POST['alasan_lainnya'] ?? '');
                $alasan_final   = ($alasan_pilihan === 'Lainnya' && $alasan_custom !== '') ? $alasan_custom : $alasan_pilihan;

                // 1. Update status pembayaran menjadi 'ditolak' dan simpan alasannya
                $stmtUpdBayar = $pdo->prepare("
                    UPDATE pembayaran 
                    SET status_verifikasi = 'ditolak',
                        alasan_penolakan = :alasan
                    WHERE id = :id
                ");
                $stmtUpdBayar->execute([
                    ':alasan' => $alasan_final,
                    ':id'     => $pembayaran_id
                ]);

                // 2. Kembalikan status tagihan ke 'sebagian' (jika sudah pernah cicil diterima) atau 'belum_bayar'
                $stmtCekCicil = $pdo->prepare("
                    SELECT COALESCE(SUM(jumlah_bayar), 0)
                    FROM pembayaran
                    WHERE tagihan_id = :tid AND LOWER(TRIM(status_verifikasi)) = 'diterima'
                ");
                $stmtCekCicil->execute([':tid' => $tagihan_id]);
                $sudahAdaCicilan = ((float)$stmtCekCicil->fetchColumn() > 0);

                $stmtUpdTagihan = $pdo->prepare("
                    UPDATE tagihan 
                    SET status_tagihan = :st
                    WHERE id = :tagihan_id
                ");
                $stmtUpdTagihan->execute([
                    ':st'         => $sudahAdaCicilan ? 'sebagian' : 'belum_bayar',
                    ':tagihan_id' => $tagihan_id
                ]);

                $pdo->commit();
                if (function_exists('bersihkanCacheNotifSiteman')) {
                    bersihkanCacheNotifSiteman();
                }

                // 3. Langsung redirect ke WhatsApp dengan format alasan penolakan
                if ($no_wa_pen !== '') {
                    if (str_starts_with($no_wa_pen, '0')) {
                        $no_wa_pen = '62' . substr($no_wa_pen, 1);
                    }
                    $nominal_fmt   = number_format($jumlah_bayar, 0, ',', '.');
                    $pengirim_role = $is_pemilik ? 'Pemilik SITEMAN - KOS' : 'Ketua Kos SITEMAN - KOS';

                    $pesan_wa = "Halo *{$nama_pen}* (Kamar {$kamar_pen}),\n\n"
                              . "Mohon maaf, konfirmasi pembayaran *{$tipe_label}* sebesar *Rp {$nominal_fmt}* belum dapat kami verifikasi (Ditolak).\n\n"
                              . "*Alasan:* {$alasan_final}\n\n"
                              . "Silakan lakukan pengecekan dan ajukan ulang pembayaran melalui aplikasi SITEMAN - KOS ya. Terima kasih. 🙏\n\n"
                              . "_{$pengirim_role}_";

                    header("Location: https://wa.me/{$no_wa_pen}?text=" . rawurlencode($pesan_wa));
                    exit;
                }

                header("Location: /?page=verifikasi_bayar");
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_verif = $e->getMessage();
        }
    }
}

// Filter Rentang Tanggal
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

// Query Ambil Data Pending Berdasarkan Role
$daftar_verifikasi = [];
if (isset($pdo) && ($pdo instanceof PDO)) {
    try {
        $sql = "
            SELECT DISTINCT ON (p.tagihan_id)
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

        if (!$is_pemilik) {
            $sql .= " AND t.kategori_iuran_id IS NOT NULL ";
        }

        $params = [];
        if ($tgl_mulai !== '' && $tgl_selesai !== '') {
            $sql .= " AND DATE(p.tanggal_bayar) >= :tgl_mulai AND DATE(p.tanggal_bayar) <= :tgl_selesai ";
            $params[':tgl_mulai']   = $tgl_mulai;
            $params[':tgl_selesai'] = $tgl_selesai;
        }

        $sql .= " ORDER BY p.tagihan_id, p.id DESC";

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