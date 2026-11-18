<?php
/**
 * HANDLER LOGIK & DATABASE KELOLA / MONITORING IURAN
 * Mendukung akumulasi tunggakan lintas periode (+) & pembayaran cicilan.
 * Lokasi: modules/shared/handler/iuran_handler.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/sewa_reminder_helper.php';
require_once __DIR__ . '/iuran_reminder_helper.php';

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

if (!function_exists('buatLinkWaIuran')) {
    function buatLinkWaIuran(
        array $p,
        string $nama_iuran,
        string $tgl_selesai,
        int $selisih_hari,
        string $tgl_batas_freeze = '',
        string $pengirim_label = 'Pengelola'
    ): string {
        $nama    = $p['nama'] ?? 'Penghuni';
        $kamar   = $p['kamar'] ?? '-';
        $sisaVal = (float)($p['nominal'] ?? 0);
        $nominal = number_format($sisaVal, 0, ',', '.');
        $batas   = date('d/m/Y', strtotime($tgl_selesai));
        $batasFz = $tgl_batas_freeze !== ''
            ? date('d/m/Y', strtotime($tgl_batas_freeze))
            : date('d/m/Y', strtotime($tgl_selesai . ' +7 days'));

        $is_lunas     = !empty($p['is_lunas']);
        $status_verif = strtolower(trim($p['status_verifikasi'] ?? ''));

        if ($is_lunas) {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}), terima kasih seluruh tagihan *{$nama_iuran}* Anda sudah terverifikasi *LUNAS* (Sisa Rp 0). 🙏\n\n_{$pengirim_label} SITEMAN - KOS_";
        } elseif ($status_verif === 'pending') {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}), bukti pembayaran *{$nama_iuran}* sudah masuk dan sedang kami proses verifikasi ya (sisa tagihan sebelum verifikasi: *Rp {$nominal}*). Terima kasih. 🙏\n\n_{$pengirim_label} SITEMAN - KOS_";
        } elseif ($selisih_hari < 0) {
            $telat = abs($selisih_hari);
            $sisaTenggang = max(0, 7 - $telat);
            if ($telat <= 7) {
                $pesan = "Halo *{$nama}* (Kamar {$kamar}),\n\n"
                       . "Kami dari *{$pengirim_label} SITEMAN - KOS* mengingatkan bahwa total sisa tagihan *{$nama_iuran}* sebesar *Rp {$nominal}* telah melewati batas bayar (*{$batas}*) selama *{$telat} hari*.\n\n"
                       . "Saat ini berlaku masa tenggang (+7 hari) hingga *{$batasFz}* (sisa *{$sisaTenggang} hari*). Mohon segera melunasi (penuh/cicilan) dan mengunggah bukti pembayaran agar akun SITEMAN - KOS Anda tidak dibekukan (freeze) otomatis. Terima kasih. 🙏";
            } else {
                $pesan = "Halo *{$nama}* (Kamar {$kamar}),\n\n"
                       . "Sisa tagihan *{$nama_iuran}* sebesar *Rp {$nominal}* telah melewati batas masa tenggang (*{$batasFz}*), sehingga akun SITEMAN - KOS Anda dibekukan sementara.\n\n"
                       . "Silakan segera melakukan pembayaran dan unggah bukti bayar di aplikasi agar akun Anda dapat diaktifkan kembali. Terima kasih. 🙏\n\n"
                       . "_{$pengirim_label} SITEMAN - KOS_";
            }
        } else {
            if ($selisih_hari === 2) {
                $waktu_info = "akan jatuh tempo dalam *2 hari lagi* (tanggal *{$batas}*)";
            } elseif ($selisih_hari === 1) {
                $waktu_info = "akan jatuh tempo *BESOK* (tanggal *{$batas}*)";
            } elseif ($selisih_hari === 0) {
                $waktu_info = "jatuh tempo *HARI INI* (tanggal *{$batas}*)";
            } else {
                $waktu_info = "memiliki batas pembayaran hingga tanggal *{$batas}*";
            }

            $pesan = "Halo *{$nama}* (Kamar {$kamar}),\n\n"
                   . "Kami dari *{$pengirim_label} SITEMAN - KOS* ingin mengingatkan bahwa total sisa tagihan *{$nama_iuran}* sebesar *Rp {$nominal}* {$waktu_info}.\n\n"
                   . "Mohon untuk segera melakukan pembayaran (lunas maupun cicilan) dan mengunggah bukti bayar melalui aplikasi SITEMAN - KOS ya (batas toleransi +7 hari s/d *{$batasFz}* sebelum akun di-freeze). Terima kasih atas perhatiannya. 🙏";
        }

        return linkWhatsApp($p['no_wa'] ?? '', $pesan) ?? '#';
    }
}

// ==========================================================================
// 1. Role dan Hak Akses
// ==========================================================================
$role_user     = $_SESSION['user']['role'] ?? 'penghuni';
$user_login_id = (int)($_SESSION['user']['id'] ?? 0);
$is_pemilik    = ($role_user === 'pemilik');
$is_pj         = !$is_pemilik && (!empty($_SESSION['user']['is_pj']) || !empty($_SESSION['user']['is_ketua_kos']));
$can_manage    = $is_pemilik || $is_pj;
$nama_login    = trim((string)($_SESSION['user']['nama'] ?? ($is_pemilik ? 'Pak Kos' : 'PJ Kos')));
$pengirim_tag  = $is_pemilik ? "Pemilik ({$nama_login})" : "PJ ({$nama_login})";

$link_kembali = $is_pemilik ? '/?page=dashboard_pemilik' : '/?page=dashboard_penghuni';
$current_page = $_GET['page'] ?? ($can_manage ? 'iuran' : 'iuran');

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
    $stmtGetSetting = $pdo->query("SELECT id, nama_iuran, nominal, tanggal_mulai, tanggal_selesai FROM kategori_iuran ORDER BY id ASC LIMIT 1");
    $rowIuran = $stmtGetSetting->fetch(PDO::FETCH_ASSOC);

    if ($rowIuran) {
        $id_kategori_iuran      = (int)$rowIuran['id'];
        $nama_iuran_saat_ini    = !empty($rowIuran['nama_iuran']) ? $rowIuran['nama_iuran'] : 'Iuran Kebersihan & Keamanan';
        $nominal_iuran_saat_ini = (float) $rowIuran['nominal'];
        if (!empty($rowIuran['tanggal_mulai'])) $tgl_mulai_iuran = $rowIuran['tanggal_mulai'];
        if (!empty($rowIuran['tanggal_selesai'])) $tgl_selesai_iuran = $rowIuran['tanggal_selesai'];
    } else {
        $stmtInit = $pdo->prepare("
            INSERT INTO kategori_iuran (nama_iuran, nominal, tanggal_mulai, tanggal_selesai) 
            VALUES ('Iuran Kebersihan & Keamanan', 50000, :tgl_m, :tgl_s)
        ");
        $stmtInit->execute([':tgl_m' => $tgl_mulai_iuran, ':tgl_s' => $tgl_selesai_iuran]);
        $id_kategori_iuran = (int) $pdo->lastInsertId();
    }

    // Pastikan seluruh penghuni aktif sudah memiliki tagihan untuk periode berjalan
    sinkronTagihanIuranPeriodeAktif($pdo, [
        'id'              => $id_kategori_iuran,
        'tanggal_mulai'   => $tgl_mulai_iuran,
        'tanggal_selesai' => $tgl_selesai_iuran,
    ]);
} catch (PDOException $e) {
    catatError('ambil nominal iuran', $e);
}

// Hitung Fase Reminder & Batas +7 Hari
$tgl_batas_freeze_iuran = date('Y-m-d', strtotime($tgl_selesai_iuran . ' +7 days'));
$hari_ini_dt            = new DateTime(date('Y-m-d'));
$jatuh_tempo_dt         = new DateTime(date('Y-m-d', strtotime($tgl_selesai_iuran)));
$selisih_hari           = (int) $hari_ini_dt->diff($jatuh_tempo_dt)->format('%r%a');

if ($selisih_hari === 2) {
    $fase_reminder_sekarang = 'H-2';
} elseif ($selisih_hari === 1) {
    $fase_reminder_sekarang = 'H-1';
} elseif ($selisih_hari === 0) {
    $fase_reminder_sekarang = 'HARI_H';
} elseif ($selisih_hari < 0 && abs($selisih_hari) <= 7) {
    $fase_reminder_sekarang = 'TENGGANG_7';
} elseif ($selisih_hari < -7) {
    $fase_reminder_sekarang = 'FREEZE';
} else {
    $fase_reminder_sekarang = 'NORMAL';
}

// Evaluasi status seluruh kamar khusus Iuran
$daftar_notif_iuran      = $can_manage ? evaluasiStatusIuranSemuaKamar($pdo) : [];
$jumlah_freeze_iuran     = 0;
$jumlah_peringatan_iuran = 0;

foreach ($daftar_notif_iuran as $ni) {
    if (($ni['fase'] ?? '') === 'freeze') {
        $jumlah_freeze_iuran++;
    } else {
        $jumlah_peringatan_iuran++;
    }
}

// ==========================================================================
// 2B. ENDPOINT AJAX ANTI-DOUBLE CHAT (CATAT PENGIRIM REMINDER WA)
// ==========================================================================
if (isset($_GET['action']) && $_GET['action'] === 'catat_reminder') {
    header('Content-Type: application/json');
    if (!$can_manage) {
        echo json_encode(['ok' => false, 'msg' => 'Akses ditolak']);
        exit;
    }

    $pkId = (int)($_GET['pk_id'] ?? 0);
    if ($pkId <= 0 || !$id_kategori_iuran) {
        echo json_encode(['ok' => false, 'msg' => 'Parameter tidak valid']);
        exit;
    }

    try {
        $stmtUpd = $pdo->prepare("
            UPDATE tagihan 
            SET reminder_sent_by = :by, reminder_sent_at = NOW(), reminder_fase = :fase 
            WHERE penghuni_kamar_id = :pk 
              AND kategori_iuran_id IS NOT NULL 
              AND LOWER(TRIM(COALESCE(status_tagihan, 'belum_bayar'))) <> 'lunas'
        ");
        $stmtUpd->execute([
            ':by'   => $pengirim_tag,
            ':fase' => $fase_reminder_sekarang,
            ':pk'   => $pkId
        ]);

        echo json_encode([
            'ok'   => true,
            'by'   => $pengirim_tag,
            'jam'  => date('H:i'),
            'fase' => $fase_reminder_sekarang
        ]);
    } catch (PDOException $e) {
        catatError('catat reminder wa', $e);
        echo json_encode(['ok' => false, 'msg' => 'Gagal mencatat reminder']);
    }
    exit;
}

// ==========================================================================
// 3. HANDLER POST: PENGELUARAN, NOMINAL IURAN, & UNFREEZE IURAN
// ==========================================================================
$error_modal  = '';
$pesan_sukses = $_SESSION['pesan_sukses_iuran'] ?? '';
unset($_SESSION['pesan_sukses_iuran']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'] ?? null;

    // 3A. SETUJUI & UNFREEZE AKUN KHUSUS TUNGGAKAN IURAN (PEMILIK / KETUA KOS)
    if (isset($_POST['setujui_unfreeze_iuran'])) {
        $targetPid     = (int)($_POST['penghuni_id'] ?? 0);
        $nomorKamar    = trim($_POST['nomor_kamar'] ?? '');
        $tanggalJanji  = trim($_POST['tanggal_janji_bayar'] ?? '');
        $catatanInput  = trim($_POST['catatan_tunggakan'] ?? '');

        if (!$can_manage) {
            $error_modal = 'Anda tidak memiliki akses untuk memproses pembukaan freeze.';
        } elseif (!$is_pemilik && $targetPid === $user_login_id) {
            $error_modal = 'Ketua Kos tidak dapat menyetujui pembukaan freeze untuk akun sendiri. Hubungi Pemilik Kos.';
        } elseif ($targetPid <= 0 || $tanggalJanji === '') {
            $error_modal = 'Tanggal janji bayar wajib diisi.';
        } else {
            try {
                $stmtCekReason = $pdo->prepare("SELECT LOWER(TRIM(COALESCE(freeze_reason, ''))) FROM penghuni WHERE id = :pid");
                $stmtCekReason->execute([':pid' => $targetPid]);
                $reason = (string)$stmtCekReason->fetchColumn();

                if ($reason !== 'iuran') {
                    $error_modal = 'Akun penghuni ini ter-freeze karena tunggakan Sewa. Hanya Pemilik Kos yang dapat membukanya.';
                } else {
                    $pdo->beginTransaction();

                    $catatanSimpan = ($catatanInput !== '')
                        ? $catatanInput
                        : "Disetujui {$pengirim_tag} s/d " . date('d/m/Y', strtotime($tanggalJanji));

                    $stmtUpdTagihan = $pdo->prepare("
                        UPDATE tagihan
                        SET tanggal_janji_bayar = :tgl_janji,
                            catatan_tunggakan = :catatan
                        WHERE penghuni_kamar_id IN (
                            SELECT id FROM penghuni_kamar WHERE penghuni_id = :pid
                        )
                        AND LOWER(TRIM(status_tagihan)) != 'lunas'
                        AND kategori_iuran_id IS NOT NULL
                    ");
                    $stmtUpdTagihan->execute([
                        ':tgl_janji' => $tanggalJanji,
                        ':catatan'   => $catatanSimpan,
                        ':pid'       => $targetPid
                    ]);

                    $stmtUnfreeze = $pdo->prepare("
                        UPDATE penghuni
                        SET status_akun = 'aktif',
                            freeze_reason = NULL
                        WHERE id = :pid
                    ");
                    $stmtUnfreeze->execute([':pid' => $targetPid]);

                    $pdo->commit();
                    $_SESSION['pesan_sukses_iuran'] = "Akun penghuni Kamar {$nomorKamar} berhasil diaktifkan kembali hingga janji bayar " . date('d/m/Y', strtotime($tanggalJanji)) . ".";
                    header('Location: ' . urlIuran($current_page));
                    exit;
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                catatError('setujui unfreeze iuran', $e);
                $error_modal = 'Gagal mengaktifkan kembali akun penghuni.';
            }
        }
    }

    // 3B. TOLAK PENGAJUAN JANJI BAYAR IURAN (AKUN TETAP FREEZE)
    if (isset($_POST['tolak_unfreeze_iuran'])) {
        $targetPid    = (int)($_POST['penghuni_id'] ?? 0);
        $nomorKamar   = trim($_POST['nomor_kamar'] ?? '');
        $alasanTolak  = trim($_POST['alasan_tolak_janji'] ?? '');

        if (!$can_manage) {
            $error_modal = 'Anda tidak memiliki akses.';
        } elseif (!$is_pemilik && $targetPid === $user_login_id) {
            $error_modal = 'Tidak dapat memproses akun sendiri.';
        } elseif ($targetPid > 0) {
            try {
                $catatanTolak = '[Ditolak] ' . ($alasanTolak !== '' ? $alasanTolak : "Pengajuan tanggal ditolak oleh {$pengirim_tag}. Silakan ajukan tanggal lebih awal.");

                $stmtTolak = $pdo->prepare("
                    UPDATE tagihan
                    SET tanggal_janji_bayar = NULL,
                        catatan_tunggakan = :catatan
                    WHERE penghuni_kamar_id IN (
                        SELECT id FROM penghuni_kamar WHERE penghuni_id = :pid
                    )
                    AND LOWER(TRIM(status_tagihan)) != 'lunas'
                    AND kategori_iuran_id IS NOT NULL
                ");
                $stmtTolak->execute([
                    ':catatan' => $catatanTolak,
                    ':pid'     => $targetPid
                ]);

                $_SESSION['pesan_sukses_iuran'] = "Pengajuan janji bayar Kamar {$nomorKamar} ditolak. Akun tetap dalam status Freeze.";
                header('Location: ' . urlIuran($current_page));
                exit;
            } catch (PDOException $e) {
                catatError('tolak unfreeze iuran', $e);
                $error_modal = 'Gagal menolak pengajuan janji bayar.';
            }
        }
    }

    // 3C. SIMPAN PENGELUARAN
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

                $_SESSION['pesan_sukses_iuran'] = 'Pengeluaran berhasil dicatat ke kas iuran.';
                header('Location: ' . urlIuran($current_page));
                exit;
            } catch (PDOException $e) {
                catatError('simpan pengeluaran', $e);
                $error_modal = 'Gagal menyimpan pengeluaran.';
            }
        }
        if ($error_modal !== '') $show_modal_pengeluaran = true;
    }

    // 3D. SET NOMINAL & PERIODE IURAN (TERBITKAN TAGIHAN PERIODE BARU TANPA MENIMPA TUNGGAKAN LAMA)
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
                $pdo->beginTransaction();

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
                    $id_kategori_iuran = (int)$pdo->lastInsertId();
                }

                // Terbitkan baris tagihan baru untuk periode ini (jika tanggal jatuh temponya baru, tunggakan bulan lalu tetap diakumulasi)
                sinkronTagihanIuranPeriodeAktif($pdo, [
                    'id'              => $id_kategori_iuran,
                    'tanggal_mulai'   => $tgl_m_baru,
                    'tanggal_selesai' => $tgl_s_baru,
                ]);

                $pdo->exec("UPDATE tagihan SET reminder_sent_by = NULL, reminder_sent_at = NULL, reminder_fase = NULL WHERE kategori_iuran_id IS NOT NULL AND LOWER(TRIM(status_tagihan)) != 'lunas'");

                $pdo->commit();
                $_SESSION['pesan_sukses_iuran'] = 'Pengaturan periode & nominal iuran berhasil disimpan.';
                header('Location: ' . urlIuran($current_page));
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                catatError('simpan nominal iuran', $e);
                $error_modal = 'Gagal memperbarui nominal & rentang tanggal iuran.';
            }
        }
        if ($error_modal !== '') $show_modal_set_iuran = true;
    }
}

// ==========================================================================
// 4. FILTER TANGGAL & SALDO / RIWAYAT MUTASI BUKU KAS IURAN
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

$saldo_kas_iuran = 0;
try {
    $stmtSaldo = $pdo->query("
        SELECT
            COALESCE(SUM(CASE WHEN LOWER(bk.tipe_transaksi) = 'pemasukan' THEN bk.nominal ELSE 0 END), 0) -
            COALESCE(SUM(CASE WHEN LOWER(bk.tipe_transaksi) = 'pengeluaran' THEN bk.nominal ELSE 0 END), 0) AS saldo
        FROM buku_kas bk
        LEFT JOIN pembayaran p ON bk.pembayaran_id = p.id
        LEFT JOIN tagihan t ON p.tagihan_id = t.id
        WHERE (bk.pembayaran_id IS NULL OR t.kategori_iuran_id IS NOT NULL)
          AND bk.keterangan NOT LIKE '%Sewa%'
    ");
    $saldo_kas_iuran = (float) $stmtSaldo->fetchColumn();
} catch (PDOException $e) {
    catatError('hitung saldo', $e);
}

$daftar_transaksi = [];
try {
    $sqlKas = "
        SELECT 
            bk.id, 
            bk.keterangan AS judul, 
            bk.tipe_transaksi AS tipe, 
            bk.nominal, 
            bk.tanggal_transaksi AS tanggal 
        FROM buku_kas bk
        LEFT JOIN pembayaran p ON bk.pembayaran_id = p.id
        LEFT JOIN tagihan t ON p.tagihan_id = t.id
        WHERE (bk.pembayaran_id IS NULL OR t.kategori_iuran_id IS NOT NULL)
          AND bk.keterangan NOT LIKE '%Sewa%'
    ";
    $paramsKas = [];
    if ($tgl_mulai !== '' && $tgl_selesai !== '') {
        $sqlKas .= " AND DATE(bk.tanggal_transaksi) BETWEEN :tgl_mulai AND :tgl_selesai";
        $paramsKas[':tgl_mulai']   = $tgl_mulai;
        $paramsKas[':tgl_selesai'] = $tgl_selesai;
    }
    $sqlKas .= " ORDER BY bk.tanggal_transaksi DESC";
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

// ==========================================================================
// 5. DAFTAR PENGHUNI + AKUMULASI SISA TUNGGAKAN IURAN (+) & STATISTIK
// ==========================================================================
$filter_status = strtolower(trim($_GET['filter'] ?? 'semua'));
if (!in_array($filter_status, ['semua', 'belum_bayar', 'verifikasi'], true)) {
    $filter_status = 'semua';
}

$total_penghuni      = 0;
$sudah_bayar         = 0;
$count_belum         = 0;
$count_verifikasi    = 0;
$daftar_penghuni     = [];
$daftar_freeze_iuran = [];

if ($can_manage) {
    try {
        // Akumulasi seluruh sisa tagihan iuran (bulan lalu + bulan ini - cicilan diterima) per penghuni
        $sqlPenghuni = "
            SELECT 
                pk.id AS penghuni_kamar_id,
                pen.id AS penghuni_id,
                pen.nama, 
                pen.no_wa,
                LOWER(COALESCE(pen.status_akun, 'aktif')) AS status_akun,
                LOWER(TRIM(COALESCE(pen.freeze_reason, ''))) AS freeze_reason,
                COALESCE(k.no_kamar, '-') AS kamar,
                COALESCE(SUM(
                    CASE 
                        WHEN t.id IS NOT NULL AND LOWER(TRIM(COALESCE(t.status_tagihan, 'belum_bayar'))) <> 'lunas'
                        THEN GREATEST(0, COALESCE(ki.nominal, :nom_def) - COALESCE(bayar_ok.total_diterima, 0))
                        ELSE 0
                    END
                ), 0) AS nominal,
                COALESCE(SUM(COALESCE(bayar_pend.jml_pending, 0)), 0) AS jml_pending,
                MAX(t.tanggal_janji_bayar) AS tanggal_janji_bayar,
                MAX(t.catatan_tunggakan) AS catatan_tunggakan,
                MAX(t.reminder_sent_by) AS reminder_sent_by,
                MAX(t.reminder_sent_at) AS reminder_sent_at,
                MAX(t.reminder_fase) AS reminder_fase
            FROM penghuni_kamar pk
            JOIN penghuni pen ON pk.penghuni_id = pen.id
            JOIN kamar k ON pk.kamar_id = k.id AND LOWER(TRIM(k.status_kamar)) = 'terisi'
            LEFT JOIN tagihan t ON pk.id = t.penghuni_kamar_id AND t.kategori_iuran_id IS NOT NULL
            LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
            LEFT JOIN (
                SELECT tagihan_id, SUM(jumlah_bayar) AS total_diterima
                FROM pembayaran
                WHERE LOWER(TRIM(status_verifikasi)) = 'diterima'
                GROUP BY tagihan_id
            ) bayar_ok ON bayar_ok.tagihan_id = t.id
            LEFT JOIN (
                SELECT tagihan_id, COUNT(*) AS jml_pending
                FROM pembayaran
                WHERE LOWER(TRIM(status_verifikasi)) = 'pending'
                GROUP BY tagihan_id
            ) bayar_pend ON bayar_pend.tagihan_id = t.id
            WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) IN ('aktif', 'freeze')
            GROUP BY pk.id, pen.id, pen.nama, pen.no_wa, pen.status_akun, pen.freeze_reason, k.no_kamar
            ORDER BY NULLIF(regexp_replace(k.no_kamar, '\D', '', 'g'), '')::INTEGER ASC NULLS LAST, k.no_kamar ASC
        ";

        $stmtP = $pdo->prepare($sqlPenghuni);
        $stmtP->execute([':nom_def' => $nominal_iuran_saat_ini]);
        $rows = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            $sisaNominal = (float)($r['nominal'] ?? 0);
            $hasPending  = ((int)($r['jml_pending'] ?? 0) > 0);

            $r['nominal']           = $sisaNominal;
            $r['is_lunas']          = ($sisaNominal <= 0);
            $r['is_verifikasi']     = $hasPending;
            $r['status_tagihan']    = $r['is_lunas'] ? 'lunas' : 'belum_bayar';
            $r['status_verifikasi'] = $hasPending ? 'pending' : ($r['is_lunas'] ? 'diterima' : '');

            $total_penghuni++;
            if ($r['is_lunas']) {
                $sudah_bayar++;
            } else {
                $count_belum++;
            }
            if ($hasPending) {
                $count_verifikasi++;
            }

            if ($r['status_akun'] === 'freeze' && $r['freeze_reason'] === 'iuran') {
                $r['bisa_di_acc'] = ($is_pemilik || ((int)$r['penghuni_id'] !== $user_login_id));
                $daftar_freeze_iuran[] = $r;
            }

            $r['sudah_diingatkan'] = (
                !$r['is_lunas']
                && !empty($r['reminder_sent_by'])
                && ($r['reminder_fase'] ?? '') === $fase_reminder_sekarang
            );
            $r['reminder_jam'] = !empty($r['reminder_sent_at'])
                ? date('H:i', strtotime($r['reminder_sent_at']))
                : '';

            $daftar_penghuni[] = $r;
        }
    } catch (PDOException $e) {
        catatError('daftar penghuni', $e);
    }
}

$filter_tabs = [
    'semua'       => ['label' => 'Semua',       'jumlah' => $total_penghuni],
    'belum_bayar' => ['label' => 'Belum Bayar', 'jumlah' => $count_belum],
    'verifikasi'  => ['label' => 'Verifikasi',  'jumlah' => $count_verifikasi],
];