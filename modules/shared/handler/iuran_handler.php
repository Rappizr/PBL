<?php
/**
 * HANDLER LOGIK & DATABASE KELOLA / MONITORING IURAN
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
        $nominal = number_format((float)($p['nominal'] ?? 0), 0, ',', '.');
        $batas   = date('d/m/Y', strtotime($tgl_selesai));
        $batasFz = $tgl_batas_freeze !== ''
            ? date('d/m/Y', strtotime($tgl_batas_freeze))
            : date('d/m/Y', strtotime($tgl_selesai . ' +7 days'));

        $status_tagihan = strtolower(trim($p['status_tagihan'] ?? 'belum_bayar'));
        $status_verif   = strtolower(trim($p['status_verifikasi'] ?? ''));

        if ($status_tagihan === 'lunas' && $status_verif === 'diterima') {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}), terima kasih pembayaran *{$nama_iuran}* sebesar *Rp {$nominal}* untuk periode s/d {$batas} sudah kami terima & terverifikasi LUNAS. 🙏\n\n_{$pengirim_label} SITEMAN - KOS_";
        } elseif ($status_verif === 'pending') {
            $pesan = "Halo *{$nama}* (Kamar {$kamar}), bukti pembayaran *{$nama_iuran}* sebesar *Rp {$nominal}* sudah masuk dan sedang kami proses verifikasi ya. Terima kasih. 🙏\n\n_{$pengirim_label} SITEMAN - KOS_";
        } elseif ($selisih_hari < 0) {
            $telat = abs($selisih_hari);
            $sisaTenggang = max(0, 7 - $telat);
            if ($telat <= 7) {
                $pesan = "Halo *{$nama}* (Kamar {$kamar}),\n\n"
                       . "Kami dari *{$pengirim_label} SITEMAN - KOS* mengingatkan bahwa tagihan *{$nama_iuran}* sebesar *Rp {$nominal}* telah melewati batas bayar (*{$batas}*) selama *{$telat} hari*.\n\n"
                       . "Saat ini berlaku masa tenggang (+7 hari) hingga *{$batasFz}* (sisa *{$sisaTenggang} hari*). Mohon segera melunasi dan mengunggah bukti pembayaran agar akun SITEMAN - KOS Anda tidak dibekukan (freeze) otomatis. Terima kasih. 🙏";
            } else {
                $pesan = "Halo *{$nama}* (Kamar {$kamar}),\n\n"
                       . "Tagihan *{$nama_iuran}* sebesar *Rp {$nominal}* telah melewati batas masa tenggang (*{$batasFz}*), sehingga akun SITEMAN - KOS Anda dibekukan sementara.\n\n"
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
                   . "Kami dari *{$pengirim_label} SITEMAN - KOS* ingin mengingatkan bahwa tagihan *{$nama_iuran}* sebesar *Rp {$nominal}* {$waktu_info}.\n\n"
                   . "Mohon untuk segera melakukan pembayaran dan mengunggah bukti bayar melalui aplikasi SITEMAN - KOS ya (batas toleransi +7 hari s/d *{$batasFz}* sebelum akun di-freeze). Terima kasih atas perhatiannya. 🙏";
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

// Evaluasi status seluruh kamar khusus Iuran (H-2, H-1, Hari H, Tenggang +7 Hari, & Auto-Freeze)
$daftar_notif_iuran     = $can_manage ? evaluasiStatusIuranSemuaKamar($pdo) : [];
$jumlah_freeze_iuran    = 0;
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
        $stmtCek = $pdo->prepare("SELECT id FROM tagihan WHERE penghuni_kamar_id = :pk AND kategori_iuran_id = :ki LIMIT 1");
        $stmtCek->execute([':pk' => $pkId, ':ki' => $id_kategori_iuran]);
        $tagihanId = $stmtCek->fetchColumn();

        if ($tagihanId) {
            $stmtUpd = $pdo->prepare("
                UPDATE tagihan 
                SET reminder_sent_by = :by, reminder_sent_at = NOW(), reminder_fase = :fase 
                WHERE id = :id
            ");
            $stmtUpd->execute([
                ':by'   => $pengirim_tag,
                ':fase' => $fase_reminder_sekarang,
                ':id'   => $tagihanId
            ]);
        } else {
            $stmtIns = $pdo->prepare("
                INSERT INTO tagihan (penghuni_kamar_id, kategori_iuran_id, tanggal_jatuh_tempo, status_tagihan, reminder_sent_by, reminder_sent_at, reminder_fase)
                VALUES (:pk, :ki, :jt, 'belum_bayar', :by, NOW(), :fase)
            ");
            $stmtIns->execute([
                ':pk'   => $pkId,
                ':ki'   => $id_kategori_iuran,
                ':jt'   => $tgl_selesai_iuran,
                ':by'   => $pengirim_tag,
                ':fase' => $fase_reminder_sekarang
            ]);
        }

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
// 3. HANDLER POST: PENGELUARAN, NOMINAL IURAN, & UNFREEZE IURAN (OPSI B)
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

                header('Location: ' . urlIuran($current_page));
                exit;
            } catch (PDOException $e) {
                catatError('simpan pengeluaran', $e);
                $error_modal = 'Gagal menyimpan pengeluaran.';
            }
        }
        if ($error_modal !== '') $show_modal_pengeluaran = true;
    }

    // 3D. SET NOMINAL & PERIODE IURAN
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

                $pdo->exec("UPDATE tagihan SET reminder_sent_by = NULL, reminder_sent_at = NULL, reminder_fase = NULL WHERE kategori_iuran_id IS NOT NULL AND LOWER(TRIM(status_tagihan)) != 'lunas'");

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
// 4. FILTER TANGGAL, HITUNG STATISTIK
// ==========================================================================
$date_range  = trim($_GET['date_range'] ?? '');
$tgl_mulai   = '';
$tgl_selesai = '';

if ($date_range !== '' && preg_match('/^\d{4}-\d{2}-\d{2}( to \d{4}-\d{2}-\d{2})?$/', $date_range)) {
    $parts       = explode(' to ', $date_range);
    $tgl_mulai   = trim($parts[0]);
    $tgl_selesai = trim($parts[1] ?? $tgl_mulai);
}

$is_filtered      = ($date_range !== '');
$total_penghuni   = 0;
$sudah_bayar      = 0;
$count_belum      = 0;
$count_verifikasi = 0;

if ($can_manage) {
    try {
        $stmtTotal = $pdo->query("
            SELECT COUNT(DISTINCT pk.id) 
            FROM penghuni_kamar pk
            JOIN penghuni pen ON pk.penghuni_id = pen.id
            JOIN kamar k ON pk.kamar_id = k.id AND k.status_kamar = 'terisi'
            WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) IN ('aktif', 'freeze')
        ");
        $total_penghuni = (int) $stmtTotal->fetchColumn();

        $stmtSudahBayar = $pdo->query("
            SELECT COUNT(DISTINCT pk.id)
            FROM penghuni_kamar pk
            JOIN penghuni pen ON pk.penghuni_id = pen.id
            JOIN kamar k ON pk.kamar_id = k.id AND k.status_kamar = 'terisi'
            JOIN tagihan t ON pk.id = t.penghuni_kamar_id AND t.kategori_iuran_id IS NOT NULL
            JOIN pembayaran pem ON t.id = pem.tagihan_id
            WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) IN ('aktif', 'freeze')
              AND LOWER(TRIM(pem.status_verifikasi)) = 'diterima'
              AND LOWER(TRIM(t.status_tagihan)) = 'lunas'
        ");
        $sudah_bayar = (int) $stmtSudahBayar->fetchColumn();

        $stmtVerifikasi = $pdo->query("
            SELECT COUNT(DISTINCT pk.id)
            FROM penghuni_kamar pk
            JOIN penghuni pen ON pk.penghuni_id = pen.id
            JOIN kamar k ON pk.kamar_id = k.id AND k.status_kamar = 'terisi'
            JOIN tagihan t ON pk.id = t.penghuni_kamar_id AND t.kategori_iuran_id IS NOT NULL
            JOIN pembayaran pem ON t.id = pem.tagihan_id
            WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) IN ('aktif', 'freeze')
              AND LOWER(TRIM(pem.status_verifikasi)) = 'pending'
        ");
        $count_verifikasi = (int) $stmtVerifikasi->fetchColumn();

        $count_belum = max(0, $total_penghuni - $sudah_bayar);
    } catch (PDOException $e) {
        catatError('hitung statistik iuran', $e);
    }
}

// ==========================================================================
// 5. LOGIKA BANNER NOTIFIKASI PENGINGAT JATUH TEMPO
// ==========================================================================
$banner_notif = null;
if ($can_manage && $count_belum > 0) {
    $tgl_batas_fmt  = date('d/m/Y', strtotime($tgl_selesai_iuran));
    $tgl_freeze_fmt = date('d/m/Y', strtotime($tgl_batas_freeze_iuran));

    if ($selisih_hari === 2) {
        $banner_notif = [
            'tipe'  => 'warning',
            'judul' => 'Pengingat H-2 Batas Iuran!',
            'pesan' => "Batas pembayaran {$nama_iuran_saat_ini} tinggal 2 hari lagi ({$tgl_batas_fmt}). Masih ada {$count_belum} penghuni yang belum lunas."
        ];
    } elseif ($selisih_hari === 1) {
        $banner_notif = [
            'tipe'  => 'warning',
            'judul' => 'Pengingat H-1 (Besok Jatuh Tempo)!',
            'pesan' => "Besok adalah batas akhir pembayaran {$nama_iuran_saat_ini} ({$tgl_batas_fmt}). Segera ingatkan {$count_belum} penghuni melalui tombol Chat WA di bawah."
        ];
    } elseif ($selisih_hari === 0) {
        $banner_notif = [
            'tipe'  => 'danger',
            'judul' => 'HARI H — Jatuh Tempo Pembayaran Iuran!',
            'pesan' => "Hari ini ({$tgl_batas_fmt}) adalah batas akhir pembayaran {$nama_iuran_saat_ini} sebelum masuk masa tenggang +7 hari (s/d {$tgl_freeze_fmt})."
        ];
    } elseif ($selisih_hari < 0 && abs($selisih_hari) <= 7) {
        $telat = abs($selisih_hari);
        $sisa  = 7 - $telat;
        $banner_notif = [
            'tipe'  => 'danger',
            'judul' => "Masa Tenggang +7 Hari (Telat {$telat} Hari — Sisa {$sisa} Hari)!",
            'pesan' => "Periode {$nama_iuran_saat_ini} melewati batas ({$tgl_batas_fmt}). {$count_belum} penghuni akan otomatis di-freeze jika belum lunas hingga {$tgl_freeze_fmt}."
        ];
    } elseif ($selisih_hari < -7) {
        $banner_notif = [
            'tipe'  => 'danger',
            'judul' => 'Masa Tenggang +7 Hari Berakhir (Akun Freeze Aktif)!',
            'pesan' => "Batas akhir {$tgl_freeze_fmt} telah lewat. Akun {$count_belum} penghuni yang belum melunasi iuran telah dibekukan otomatis."
        ];
    }
}

// ==========================================================================
// 6. SALDO & RIWAYAT MUTASI BUKU KAS IURAN
// ==========================================================================
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
// 7. FILTER TAB & DAFTAR PENGHUNI + STATUS FREEZE IURAN & ANTI-DOUBLE CHAT
// ==========================================================================
$filter_status = strtolower(trim($_GET['filter'] ?? 'semua'));
if (!in_array($filter_status, ['semua', 'belum_bayar', 'verifikasi'], true)) {
    $filter_status = 'semua';
}

$filter_tabs = [
    'semua'       => ['label' => 'Semua',       'jumlah' => $total_penghuni],
    'belum_bayar' => ['label' => 'Belum Bayar', 'jumlah' => $count_belum],
    'verifikasi'  => ['label' => 'Verifikasi',  'jumlah' => $count_verifikasi],
];

$daftar_penghuni     = [];
$daftar_freeze_iuran = [];

if ($can_manage) {
    try {
        $sqlPenghuni = "
            SELECT 
                pk.id AS penghuni_kamar_id,
                pen.id AS penghuni_id,
                pen.nama, 
                pen.no_wa,
                LOWER(COALESCE(pen.status_akun, 'aktif')) AS status_akun,
                LOWER(TRIM(COALESCE(pen.freeze_reason, ''))) AS freeze_reason,
                COALESCE(k.no_kamar, '-') AS kamar,
                COALESCE(ki.nominal, :nom_def) AS nominal,
                COALESCE(t.status_tagihan, 'belum_bayar') AS status_tagihan,
                t.tanggal_janji_bayar,
                t.catatan_tunggakan,
                t.reminder_sent_by,
                t.reminder_sent_at,
                t.reminder_fase,
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
            WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) IN ('aktif', 'freeze')
            ORDER BY k.no_kamar ASC
        ";

        $stmtP = $pdo->prepare($sqlPenghuni);
        $stmtP->execute([':nom_def' => $nominal_iuran_saat_ini]);
        $rows = $stmtP->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            $st_tagihan = strtolower(trim($r['status_tagihan'] ?? ''));
            $st_verif   = strtolower(trim($r['status_verifikasi'] ?? ''));

            $r['is_lunas']      = ($st_tagihan === 'lunas' && $st_verif === 'diterima');
            $r['is_verifikasi'] = ($st_verif === 'pending');

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