<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $possible_paths = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../../../config/database.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/database.php'
    ];
    foreach ($possible_paths as $p) {
        if (file_exists($p)) {
            require_once $p;
            if (isset($pdo) && ($pdo instanceof PDO)) break;
        }
    }
}

$current_page = 'manajemen_kamar';
$modal_aktif  = $_GET['modal'] ?? '';
$nomor_pilih  = trim($_GET['nomor'] ?? '');
$error_simpan = $_SESSION['error_simpan'] ?? '';
unset($_SESSION['error_simpan']);

// Jalankan handler POST (Tambah/Edit/Hapus Kamar, Penghuni, Skema, & Freeze)
require_once __DIR__ . '/handler/kamar_handlers.php';

// Ambil pesan sukses dari session untuk semua aksi (Tambah Kamar, Penghuni, & Skema)
$pesan_sukses = $_SESSION['pesan_sukses'] ?? '';
unset($_SESSION['pesan_sukses']);

// Status jatuh tempo sewa & freeze
$daftar_notif_sewa = [];
$map_info_sewa     = [];
if (isset($pdo) && $pdo instanceof PDO) {
    $helperSewaPath = __DIR__ . '/../shared/handler/sewa_reminder_helper.php';
    if (file_exists($helperSewaPath)) {
        require_once $helperSewaPath;
        if (function_exists('evaluasiStatusSewaSemuaKamar')) {
            $daftar_notif_sewa = evaluasiStatusSewaSemuaKamar($pdo);
            foreach ($daftar_notif_sewa as $itemSewa) {
                $map_info_sewa[$itemSewa['no_kamar']] = $itemSewa;
            }
        }
    }
}

// Query data kamar, penghuni aktif, skema sewa, dan ketua kost
$semua_kamar         = [];
$daftar_kamar_kosong = [];
$list_skema          = [];
$ketuaAktif          = null;
$kamar_terpilih      = null;

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmtSkema = $pdo->query("SELECT * FROM skema_sewa ORDER BY durasi_nilai ASC, id ASC");
        $list_skema = $stmtSkema ? $stmtSkema->fetchAll(PDO::FETCH_ASSOC) : [];

        $stmtKetua = $pdo->query("SELECT id, nama FROM penghuni WHERE is_ketua_kos = true LIMIT 1");
        $ketuaAktif = $stmtKetua ? ($stmtKetua->fetch(PDO::FETCH_ASSOC) ?: null) : null;

        $sqlKamar = "
            SELECT 
                k.id AS kamar_id,
                k.no_kamar,
                LOWER(TRIM(COALESCE(k.status_kamar, 'kosong'))) AS status_kamar,
                pk.id AS penghuni_kamar_id,
                pk.tanggal_masuk,
                pk.tanggal_selesai,
                pk.skema_id,
                s.nama_skema AS durasi_sewa,
                p.id AS penghuni_id,
                p.nama,
                p.no_wa,
                p.email,
                p.kontak_wali,
                p.foto_ktp,
                p.pin,
                p.is_ketua_kos,
                LOWER(TRIM(COALESCE(p.status_akun, 'aktif'))) AS status_akun,
                t_last.status_tagihan AS status_bayar,
                t_last.tanggal_janji_bayar,
                t_last.catatan_tunggakan
            FROM kamar k
            LEFT JOIN LATERAL (
                SELECT * FROM penghuni_kamar 
                WHERE kamar_id = k.id 
                ORDER BY id DESC LIMIT 1
            ) pk ON LOWER(TRIM(COALESCE(k.status_kamar, 'kosong'))) = 'terisi'
            LEFT JOIN penghuni p ON p.id = pk.penghuni_id
            LEFT JOIN skema_sewa s ON s.id = pk.skema_id
            LEFT JOIN LATERAL (
                SELECT status_tagihan, tanggal_janji_bayar, catatan_tunggakan
                FROM tagihan
                WHERE penghuni_kamar_id = pk.id
                  AND kategori_iuran_id IS NULL
                ORDER BY tanggal_jatuh_tempo DESC, id DESC
                LIMIT 1
            ) t_last ON true
            ORDER BY NULLIF(regexp_replace(k.no_kamar, '\D', '', 'g'), '')::INTEGER ASC NULLS LAST, k.no_kamar ASC
        ";

        $rows = $pdo->query($sqlKamar)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            $noKamar  = $r['no_kamar'];
            $isKosong = ($r['status_kamar'] === 'kosong' || empty($r['nama']));

            $tglMulai      = $r['tanggal_masuk'] ?? date('Y-m-d');
            $tglSelesaiFmt = !empty($r['tanggal_selesai'])
                ? date('d / m / Y', strtotime($r['tanggal_selesai']))
                : '-';

            $item = [
                'kamar_id'            => $r['kamar_id'],
                'nomor'               => $noKamar,
                'no_kamar'            => $noKamar,
                'status_kamar'        => $isKosong ? 'kosong' : 'terisi',
                'penghuni_id'         => $r['penghuni_id'] ?? null,
                'nama'                => $r['nama'] ?? '',
                'no_wa'               => $r['no_wa'] ?? '',
                'email'               => $r['email'] ?? '',
                'kontak_wali'         => $r['kontak_wali'] ?? '',
                'foto_ktp'            => $r['foto_ktp'] ?? '',
                'pin'                 => $r['pin'] ?? '123456',
                'is_ketua_kos'        => !empty($r['is_ketua_kos']),
                'status_akun'         => $r['status_akun'] ?? 'aktif',
                'skema_id'            => $r['skema_id'] ?? 0,
                'durasi_sewa'         => $r['durasi_sewa'] ?? '',
                'tgl_mulai'           => $tglMulai,
                'tgl_selesai_format'  => $tglSelesaiFmt,
                'status_bayar'        => strtolower(trim($r['status_bayar'] ?? 'belum_bayar')),
                'tanggal_janji_bayar' => $r['tanggal_janji_bayar'] ?? null,
                'catatan_tunggakan'   => $r['catatan_tunggakan'] ?? '',
                'info_sewa'           => $map_info_sewa[$noKamar] ?? null,
            ];

            $semua_kamar[] = $item;

            if ($isKosong) {
                $daftar_kamar_kosong[] = ['no_kamar' => $noKamar];
            }

            if ($modal_aktif === 'detail_kamar' && $nomor_pilih !== '' && $noKamar === $nomor_pilih) {
                $kamar_terpilih = $item;
            }
        }
    } catch (Exception $e) {
        $error_simpan = "Gagal memuat data kamar: " . $e->getMessage();
    }
}

// Pagination (8 kamar per halaman)
$per_halaman   = 8;
$total_kamar   = count($semua_kamar);
$total_halaman = max(1, (int) ceil($total_kamar / $per_halaman));
$halaman_aktif = max(1, min($total_halaman, (int) ($_GET['hal'] ?? 1)));
$offset        = ($halaman_aktif - 1) * $per_halaman;
$kamar_tampil  = array_slice($semua_kamar, $offset, $per_halaman);

$notif_freeze_list = array_filter($daftar_notif_sewa, fn($n) => ($n['fase'] ?? '') === 'freeze');
$notif_warn_list   = array_filter($daftar_notif_sewa, fn($n) => ($n['fase'] ?? '') !== 'freeze');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Manajemen Kamar &amp; Penghuni</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= filemtime(__DIR__ . '/../../public/css/global.css'); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= filemtime(__DIR__ . '/../../public/css/modules.css'); ?>">
</head>

<body class="dashboard-body">

    <!-- FLOATING TOAST NOTIFIKASI BERHASIL / GAGAL -->
    <div class="toast-floating-container">
        <?php if (!empty($pesan_sukses)): ?>
        <div class="toast-alert toast-success" id="toastAlertBoxKamar">
            <div class="toast-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            <div class="toast-content">
                <h5 class="toast-title">Berhasil</h5>
                <p class="toast-message"><?= htmlspecialchars($pesan_sukses); ?></p>
            </div>
            <button type="button" class="toast-close" onclick="closeToastKamar()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>

        <?php if (!empty($error_simpan)): ?>
        <div class="toast-alert toast-error" id="toastAlertBoxKamar">
            <div class="toast-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="toast-content">
                <h5 class="toast-title">Perhatian</h5>
                <p class="toast-message"><?= htmlspecialchars($error_simpan); ?></p>
            </div>
            <button type="button" class="toast-close" onclick="closeToastKamar()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- ==================== LAYOUT MOBILE ==================== -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <main class="page-content">
                <div class="kamar-title-card">
                    <h2>Manajemen Kamar &amp; Penghuni</h2>
                    <a href="/?page=dashboard_pemilik" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <!-- Accordion Ringkasan Freeze (Mobile) -->
                <?php if (!empty($notif_freeze_list)): ?>
                    <div class="kamar-notif-accordion accordion-danger">
                        <button type="button" class="kamar-notif-accordion-header" onclick="toggleNotifAccordion(this)">
                            <div class="kamar-notif-acc-left">
                                <div class="kamar-notif-acc-icon"><i class="fa-solid fa-lock"></i></div>
                                <div class="kamar-notif-acc-text">
                                    <strong><?= count($notif_freeze_list); ?> Akun Penghuni Freeze</strong>
                                    <span>Ketuk untuk melihat &amp; kirim pengingat</span>
                                </div>
                            </div>
                            <span class="kamar-notif-acc-toggle">Lihat <i class="fa-solid fa-chevron-down"></i></span>
                        </button>
                        <div class="kamar-notif-accordion-body">
                            <?php foreach ($notif_freeze_list as $nf): ?>
                                <div class="kamar-notif-compact-item">
                                    <div class="compact-item-top">
                                        <strong>Kamar <?= htmlspecialchars($nf['no_kamar']); ?> - <?= htmlspecialchars($nf['nama_penghuni']); ?></strong>
                                        <span class="compact-badge badge-text-danger">Freeze</span>
                                    </div>
                                    <p class="compact-item-desc"><?= htmlspecialchars($nf['pesan_ringkas']); ?></p>
                                    <div class="kamar-notif-mob-actions">
                                        <a href="<?= htmlspecialchars($nf['wa_pengingat_url']); ?>" target="_blank" class="kamar-notif-btn-wa">
                                            <i class="fa-brands fa-whatsapp"></i> WA
                                        </a>
                                        <a href="/?page=manajemen_kamar&hal=<?= $halaman_aktif; ?>&modal=detail_kamar&nomor=<?= urlencode($nf['no_kamar']); ?>" class="kamar-notif-btn-kelola">
                                            Kelola
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Accordion Ringkasan Jatuh Tempo / Masa Tenggang (Mobile) -->
                <?php if (!empty($notif_warn_list)): ?>
                    <div class="kamar-notif-accordion accordion-warning">
                        <button type="button" class="kamar-notif-accordion-header" onclick="toggleNotifAccordion(this)">
                            <div class="kamar-notif-acc-left">
                                <div class="kamar-notif-acc-icon"><i class="fa-solid fa-clock"></i></div>
                                <div class="kamar-notif-acc-text">
                                    <strong><?= count($notif_warn_list); ?> Tagihan Mendekati / Masa Tenggang</strong>
                                    <span>Ketuk untuk kirim pengingat WA</span>
                                </div>
                            </div>
                            <span class="kamar-notif-acc-toggle">Lihat <i class="fa-solid fa-chevron-down"></i></span>
                        </button>
                        <div class="kamar-notif-accordion-body">
                            <?php foreach ($notif_warn_list as $nw): ?>
                                <div class="kamar-notif-compact-item">
                                    <div class="compact-item-top">
                                        <strong>Kamar <?= htmlspecialchars($nw['no_kamar']); ?> - <?= htmlspecialchars($nw['nama_penghuni']); ?></strong>
                                        <span class="compact-badge badge-text-warning"><?= htmlspecialchars($nw['label_status']); ?></span>
                                    </div>
                                    <p class="compact-item-desc"><?= htmlspecialchars($nw['pesan_ringkas']); ?></p>
                                    <div class="kamar-notif-mob-actions">
                                        <a href="<?= htmlspecialchars($nw['wa_pengingat_url']); ?>" target="_blank" class="kamar-notif-btn-wa">
                                            <i class="fa-brands fa-whatsapp"></i> WA
                                        </a>
                                        <a href="/?page=manajemen_kamar&hal=<?= $halaman_aktif; ?>&modal=detail_kamar&nomor=<?= urlencode($nw['no_kamar']); ?>" class="kamar-notif-btn-kelola">
                                            Detail
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tombol Aksi Mobile -->
                <div class="kamar-actions">
                    <a href="/?page=manajemen_kamar&hal=<?= $halaman_aktif; ?>&modal=tambah_kamar"
                       onclick="bukaModalInstan(event, 'modalTambahKamar', 'tambah_kamar')"
                       class="btn-add-outline">
                        <span class="plus-icon">+</span> Kamar
                    </a>
                    <a href="/?page=manajemen_kamar&hal=<?= $halaman_aktif; ?>&modal=tambah_penghuni"
                       onclick="bukaModalInstan(event, 'modalTambahPenghuni', 'tambah_penghuni')"
                       class="btn-add-outline">
                        <span class="plus-icon">+</span> Penghuni
                    </a>
                    <a href="/?page=manajemen_kamar&hal=<?= $halaman_aktif; ?>&modal=kelola_skema"
                       onclick="bukaModalInstan(event, 'modalKelolaSkema', 'kelola_skema')"
                       class="btn-add-outline kamar-btn-skema-action">
                        <i class="fa-solid fa-sliders"></i> Skema Sewa
                    </a>
                </div>

                <!-- Grid Kartu Kamar Mobile -->
                <div class="kamar-grid">
                    <?php if (empty($kamar_tampil)): ?>
                        <p class="kamar-empty-state-mobile">Belum ada kamar yang terdaftar.</p>
                    <?php else: ?>
                        <?php foreach ($kamar_tampil as $kmr): ?>
                            <?php
                                $isKosong = ($kmr['status_kamar'] === 'kosong');
                                $isFreeze = (!$isKosong && $kmr['status_akun'] === 'freeze');
                                $isLunas  = (!$isKosong && $kmr['status_bayar'] === 'lunas');
                            ?>
                            <a href="/?page=manajemen_kamar&hal=<?= $halaman_aktif; ?>&modal=detail_kamar&nomor=<?= urlencode($kmr['nomor']); ?>"
                               class="kamar-card">
                                <?php if ($isKosong): ?>
                                    <span class="badge-status badge-kosong">Kosong</span>
                                <?php elseif ($isFreeze): ?>
                                    <span class="badge-status badge-kamar-freeze">Freeze</span>
                                <?php elseif ($isLunas): ?>
                                    <span class="badge-status badge-lunas">Lunas</span>
                                <?php else: ?>
                                    <span class="badge-status badge-belum">Belum Bayar</span>
                                <?php endif; ?>

                                <span class="kamar-nomor"><?= htmlspecialchars($kmr['nomor']); ?></span>
                                <span class="kamar-nama">
                                    <?= $isKosong ? 'Kosong' : htmlspecialchars($kmr['nama']); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($total_halaman > 1): ?>
                    <div class="kamar-mobile-pagination">
                        <a href="/?page=manajemen_kamar&hal=<?= max(1, $halaman_aktif - 1); ?>"
                           class="kamar-mobile-page-btn <?= $halaman_aktif <= 1 ? 'disabled' : ''; ?>"
                           <?= $halaman_aktif <= 1 ? 'onclick="return false;"' : ''; ?>>
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                        <span class="kamar-mobile-page-info">Hal <?= $halaman_aktif; ?> / <?= $total_halaman; ?></span>
                        <a href="/?page=manajemen_kamar&hal=<?= min($total_halaman, $halaman_aktif + 1); ?>"
                           class="kamar-mobile-page-btn <?= $halaman_aktif >= $total_halaman ? 'disabled' : ''; ?>"
                           <?= $halaman_aktif >= $total_halaman ? 'onclick="return false;"' : ''; ?>>
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- ==================== LAYOUT DESKTOP ==================== -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Manajemen Kamar &amp; Penghuni</h2>
            <p class="desktop-header-subtitle">Kelola data kamar, penghuni kost, serta skema durasi sewa</p>

            <!-- Accordion Ringkasan Freeze (Desktop) -->
            <?php if (!empty($notif_freeze_list)): ?>
                <div class="kamar-notif-accordion kamar-notif-acc-desktop accordion-danger">
                    <button type="button" class="kamar-notif-accordion-header" onclick="toggleNotifAccordion(this)">
                        <div class="kamar-notif-acc-left">
                            <div class="kamar-notif-acc-icon"><i class="fa-solid fa-lock"></i></div>
                            <div class="kamar-notif-acc-text">
                                <strong><?= count($notif_freeze_list); ?> Akun Penghuni dalam Status Freeze (Dibekukan)</strong>
                                <span>Klik untuk melihat daftar penghuni yang melewati batas tenggang sewa</span>
                            </div>
                        </div>
                        <span class="kamar-notif-acc-toggle">Lihat <i class="fa-solid fa-chevron-down"></i></span>
                    </button>
                    <div class="kamar-notif-accordion-body">
                        <?php foreach ($notif_freeze_list as $nf): ?>
                            <div class="kamar-notif-compact-row">
                                <div class="compact-row-info">
                                    <strong>
                                        Kamar <?= htmlspecialchars($nf['no_kamar']); ?> - <?= htmlspecialchars($nf['nama_penghuni']); ?>
                                        <span class="compact-badge badge-text-danger">Rp <?= number_format((float)$nf['total_tunggakan'], 0, ',', '.'); ?></span>
                                    </strong>
                                    <span><?= htmlspecialchars($nf['pesan_ringkas']); ?></span>
                                </div>
                                <div class="kamar-notif-desk-actions">
                                    <a href="<?= htmlspecialchars($nf['wa_pengingat_url']); ?>" target="_blank" class="kamar-notif-desk-wa">
                                        <i class="fa-brands fa-whatsapp"></i> Ingatkan WA
                                    </a>
                                    <a href="/?page=manajemen_kamar&hal=<?= $halaman_aktif; ?>&modal=detail_kamar&nomor=<?= urlencode($nf['no_kamar']); ?>" class="kamar-notif-desk-kelola">
                                        Kelola / Unfreeze
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Accordion Ringkasan Jatuh Tempo / Masa Tenggang (Desktop) -->
            <?php if (!empty($notif_warn_list)): ?>
                <div class="kamar-notif-accordion kamar-notif-acc-desktop accordion-warning">
                    <button type="button" class="kamar-notif-accordion-header" onclick="toggleNotifAccordion(this)">
                        <div class="kamar-notif-acc-left">
                            <div class="kamar-notif-acc-icon"><i class="fa-solid fa-clock"></i></div>
                            <div class="kamar-notif-acc-text">
                                <strong><?= count($notif_warn_list); ?> Tagihan Sewa Mendekati Jatuh Tempo / Masa Tenggang</strong>
                                <span>Klik untuk mengirim pengingat pembayaran ke penghuni</span>
                            </div>
                        </div>
                        <span class="kamar-notif-acc-toggle">Lihat <i class="fa-solid fa-chevron-down"></i></span>
                    </button>
                    <div class="kamar-notif-accordion-body">
                        <?php foreach ($notif_warn_list as $nw): ?>
                            <div class="kamar-notif-compact-row">
                                <div class="compact-row-info">
                                    <strong>
                                        Kamar <?= htmlspecialchars($nw['no_kamar']); ?> - <?= htmlspecialchars($nw['nama_penghuni']); ?>
                                        <span class="compact-badge badge-text-warning"><?= htmlspecialchars($nw['label_status']); ?></span>
                                    </strong>
                                    <span><?= htmlspecialchars($nw['pesan_ringkas']); ?></span>
                                </div>
                                <div class="kamar-notif-desk-actions">
                                    <a href="<?= htmlspecialchars($nw['wa_pengingat_url']); ?>" target="_blank" class="kamar-notif-desk-wa">
                                        <i class="fa-brands fa-whatsapp"></i> Ingatkan WA
                                    </a>
                                    <a href="/?page=manajemen_kamar&hal=<?= $halaman_aktif; ?>&modal=detail_kamar&nomor=<?= urlencode($nw['no_kamar']); ?>" class="kamar-notif-desk-kelola">
                                        Detail Kamar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tombol Aksi Desktop -->
            <div class="desktop-kamar-top-actions">
                <a href="/?page=manajemen_kamar&hal=<?= $halaman_aktif; ?>&modal=tambah_kamar"
                   onclick="bukaModalInstan(event, 'modalTambahKamar', 'tambah_kamar')"
                   class="kamar-btn-pill-action">
                    <i class="fa-solid fa-plus"></i> Tambah Kamar
                </a>
                <a href="/?page=manajemen_kamar&hal=<?= $halaman_aktif; ?>&modal=tambah_penghuni"
                   onclick="bukaModalInstan(event, 'modalTambahPenghuni', 'tambah_penghuni')"
                   class="kamar-btn-pill-action">
                    <i class="fa-solid fa-user-plus"></i> Tambah Penghuni
                </a>
                <a href="/?page=manajemen_kamar&hal=<?= $halaman_aktif; ?>&modal=kelola_skema"
                   onclick="bukaModalInstan(event, 'modalKelolaSkema', 'kelola_skema')"
                   class="kamar-btn-pill-action kamar-btn-skema-action">
                    <i class="fa-solid fa-sliders"></i> Kelola Skema Sewa
                </a>
            </div>

            <!-- Panel Grid Kamar Desktop -->
            <div class="kamar-desktop-panel">
                <div class="kamar-desktop-grid-white">
                    <?php if (empty($kamar_tampil)): ?>
                        <p class="kamar-empty-state-desktop">Belum ada data kamar. Silakan klik tombol "Tambah Kamar" di atas.</p>
                    <?php else: ?>
                        <?php foreach ($kamar_tampil as $kmr): ?>
                            <?php
                                $isKosong = ($kmr['status_kamar'] === 'kosong');
                                $isFreeze = (!$isKosong && $kmr['status_akun'] === 'freeze');
                                $isLunas  = (!$isKosong && $kmr['status_bayar'] === 'lunas');
                            ?>
                            <a href="/?page=manajemen_kamar&hal=<?= $halaman_aktif; ?>&modal=detail_kamar&nomor=<?= urlencode($kmr['nomor']); ?>"
                               class="<?= $isKosong ? 'kamar-card-gray' : 'kamar-card-white'; ?>">
                                <?php if ($isKosong): ?>
                                    <span class="badge-kamar-status badge-kamar-kosong">Kosong</span>
                                <?php elseif ($isFreeze): ?>
                                    <span class="badge-kamar-status badge-kamar-freeze">Freeze</span>
                                <?php elseif ($isLunas): ?>
                                    <span class="badge-kamar-status badge-kamar-lunas">Lunas</span>
                                <?php else: ?>
                                    <span class="badge-kamar-status badge-kamar-belum">Belum Bayar</span>
                                <?php endif; ?>

                                <div class="kamar-card-number">
                                    <?= htmlspecialchars($kmr['nomor']); ?>
                                    <?php if (!$isKosong && $kmr['is_ketua_kos']): ?>
                                        <i class="fa-solid fa-user-shield" title="Ketua Kost"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="kamar-card-resident">
                                    <?= $isKosong ? 'Kosong' : htmlspecialchars($kmr['nama']); ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Legend & Pagination Desktop -->
                <div class="kamar-desktop-bottom-bar">
                    <div class="kamar-legend-row">
                        <span class="legend-item"><span class="legend-color-box color-lunas"></span> Lunas</span>
                        <span class="legend-item"><span class="legend-color-box color-belum"></span> Belum Bayar</span>
                        <span class="legend-item"><span class="legend-color-box color-freeze"></span> Freeze</span>
                        <span class="legend-item"><span class="legend-color-box color-kosong"></span> Kosong</span>
                    </div>

                    <?php if ($total_halaman > 1): ?>
                        <div class="kamar-pagination-row">
                            <a href="/?page=manajemen_kamar&hal=<?= max(1, $halaman_aktif - 1); ?>"
                               class="kamar-page-btn"
                               <?= $halaman_aktif <= 1 ? 'onclick="return false;"' : ''; ?>>
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                            <span class="kamar-page-info">Halaman <?= $halaman_aktif; ?> dari <?= $total_halaman; ?></span>
                            <a href="/?page=manajemen_kamar&hal=<?= min($total_halaman, $halaman_aktif + 1); ?>"
                               class="kamar-page-btn"
                               <?= $halaman_aktif >= $total_halaman ? 'onclick="return false;"' : ''; ?>>
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- ==================== INCLUDE SEMUA MODAL ==================== -->
    <?php require_once __DIR__ . '/modals/modal_tambah_kamar.php'; ?>
    <?php require_once __DIR__ . '/modals/modal_tambah_penghuni.php'; ?>
    <?php require_once __DIR__ . '/modals/modal_kelola_skema.php'; ?>
    <?php require_once __DIR__ . '/modals/modal_detail_kamar.php'; ?>

    <script>
        function closeToastKamar() {
            const toast = document.getElementById('toastAlertBoxKamar');
            if (toast) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-15px) scale(0.95)';
                setTimeout(() => toast.remove(), 300);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const toast = document.getElementById('toastAlertBoxKamar');
            if (toast) {
                setTimeout(closeToastKamar, 4500);
            }
        });

        function bukaModalInstan(e, modalId, modalParam) {
            if (e) e.preventDefault();
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
            }
            if (modalParam && window.history && window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.set('modal', modalParam);
                window.history.replaceState({}, '', url);
            }
        }

        function tutupSemuaModalInstan(e) {
            if (e) e.preventDefault();
            document.querySelectorAll('.modal-overlay.active').forEach(function (m) {
                m.classList.remove('active');
            });
            if (window.history && window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.delete('modal');
                url.searchParams.delete('nomor');
                window.history.replaceState({}, '', url);
            }
        }

        function toggleNotifAccordion(headerEl) {
            const card = headerEl.closest('.kamar-notif-accordion');
            if (!card) return;
            const body = card.querySelector('.kamar-notif-accordion-body');
            if (body) body.classList.toggle('is-open');
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                tutupSemuaModalInstan();
            }
        });
    </script>

</body>

</html>