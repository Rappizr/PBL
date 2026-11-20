<?php
require_once __DIR__ . '/handler/iuran_handler.php';

// Filter default: Jika tidak sedang memfilter rentang tanggal, hanya tampilkan riwayat HARI INI
if (!$is_filtered && !empty($daftar_transaksi)) {
    $hari_ini = date('Y-m-d');
    $daftar_transaksi = array_values(array_filter($daftar_transaksi, function ($tx) use ($hari_ini) {
        return date('Y-m-d', strtotime($tx['tanggal'])) === $hari_ini;
    }));
}

// Susun peta data freeze iuran per penghuni_id untuk kebutuhan form ACC/Tolak di dalam accordion
$map_freeze_iuran_pid = [];
foreach ($daftar_freeze_iuran as $frzItem) {
    $map_freeze_iuran_pid[(int)$frzItem['penghuni_id']] = $frzItem;
}

// Pisahkan notifikasi menjadi 2 kelompok (seperti Manajemen Kamar):
// 1) Akun Freeze (pink/merah) — diurutkan yang sudah mengajukan janji bayar (belum di-ACC) di paling atas
// 2) Mendekati Jatuh Tempo / Masa Tenggang (kuning)
$notif_iuran_freeze = array_values(array_filter($daftar_notif_iuran, fn($n) => ($n['fase'] ?? '') === 'freeze'));
usort($notif_iuran_freeze, function ($a, $b) {
    $janjiA = !empty($a['tanggal_janji_bayar']) ? 0 : 1;
    $janjiB = !empty($b['tanggal_janji_bayar']) ? 0 : 1;
    if ($janjiA !== $janjiB) return $janjiA <=> $janjiB;
    return ((int)$a['no_kamar']) <=> ((int)$b['no_kamar']);
});

$notif_iuran_warn = array_values(array_filter($daftar_notif_iuran, fn($n) => ($n['fase'] ?? '') !== 'freeze'));
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | <?= htmlspecialchars($judul_halaman); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= filemtime(__DIR__ . '/../../public/css/global.css'); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= filemtime(__DIR__ . '/../../public/css/modules.css'); ?>">
</head>

<body class="dashboard-body">

    <!-- FLOATING TOAST NOTIFIKASI -->
    <div class="toast-floating-container">
        <?php if (!empty($pesan_sukses)): ?>
        <div class="toast-alert toast-success" id="toastAlertBox">
            <div class="toast-icon"><i class="fa-solid fa-check"></i></div>
            <div class="toast-content">
                <h5 class="toast-title">Berhasil</h5>
                <p class="toast-message"><?= htmlspecialchars($pesan_sukses); ?></p>
            </div>
            <button type="button" class="toast-close" onclick="closeToastIuran()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($error_modal) && !$show_modal_pengeluaran && !$show_modal_set_iuran): ?>
        <div class="toast-alert toast-error" id="toastAlertBox">
            <div class="toast-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="toast-content">
                <h5 class="toast-title">Perhatian</h5>
                <p class="toast-message"><?= htmlspecialchars($error_modal); ?></p>
            </div>
            <button type="button" class="toast-close" onclick="closeToastIuran()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <?php endif; ?>
    </div>

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <main class="kelola-container">
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title"><?= htmlspecialchars($judul_halaman); ?></h2>
                    <a href="<?= $link_kembali; ?>" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <?php if ($can_manage): ?>
                <div class="kelola-stats-row">
                    <div class="kelola-stat-card">
                        <span class="kelola-stat-label">Total Penghuni</span>
                        <span class="kelola-stat-value"><?= $total_penghuni; ?></span>
                    </div>
                    <div class="kelola-stat-card">
                        <span class="kelola-stat-label">Sudah Bayar</span>
                        <span class="kelola-stat-value"><?= $sudah_bayar . '/' . $total_penghuni; ?></span>
                    </div>
                    <div class="kelola-stat-card">
                        <span class="kelola-stat-label"><?= htmlspecialchars($nama_iuran_saat_ini ?? 'Iuran Aktif'); ?></span>
                        <span class="kelola-stat-value">Rp. <?= number_format((float)$nominal_iuran_saat_ini, 0, ',', '.'); ?></span>
                        <small class="kelola-stat-periode">
                            Periode: <?= date('d/m/Y', strtotime($tgl_mulai_iuran)); ?> s/d
                            <?= date('d/m/Y', strtotime($tgl_selesai_iuran)); ?>
                        </small>
                    </div>
                </div>

                <!-- ACCORDION 1: AKUN FREEZE IURAN (MOBILE - MERAH/PINK) -->
                <?php if (!empty($notif_iuran_freeze)): ?>
                <div class="kamar-notif-accordion accordion-danger">
                    <button type="button" class="kamar-notif-accordion-header" onclick="toggleNotifIuran('listNotifIuranFrzMob', 'iconNotifIuranFrzMob')">
                        <div class="kamar-notif-acc-left">
                            <span class="kamar-notif-acc-icon"><i class="fa-solid fa-lock"></i></span>
                            <div class="kamar-notif-acc-text">
                                <strong><?= count($notif_iuran_freeze); ?> Akun Penghuni Freeze (Iuran)</strong>
                                <span>Ketuk untuk melihat &amp; setujui pengajuan</span>
                            </div>
                        </div>
                        <span class="kamar-notif-acc-toggle">
                            Lihat <i class="fa-solid fa-chevron-down" id="iconNotifIuranFrzMob"></i>
                        </span>
                    </button>

                    <div class="kamar-notif-accordion-body" id="listNotifIuranFrzMob">
                        <?php foreach ($notif_iuran_freeze as $ni): ?>
                        <?php
                            $pidNi   = (int)($ni['penghuni_id'] ?? 0);
                            $frzData = $map_freeze_iuran_pid[$pidNi] ?? null;
                            $tglAj   = $ni['tanggal_janji_bayar'] ?? '';
                            $catRaw  = trim((string)($ni['catatan_tunggakan'] ?? ''));
                            $isDtlk  = str_starts_with($catRaw, '[Ditolak]');
                        ?>
                        <div class="kamar-notif-compact-item item-danger">
                            <div class="compact-item-top">
                                <strong>
                                    <i class="fa-solid fa-lock"></i>
                                    Kamar <?= htmlspecialchars(sprintf('%02d', (int)$ni['no_kamar'])); ?> &bull; <?= htmlspecialchars($ni['nama_penghuni']); ?>
                                </strong>
                                <span class="compact-badge badge-text-danger">
                                    <?= $tglAj !== '' ? 'Ajuan Janji Bayar' : 'Freeze'; ?>
                                </span>
                            </div>
                            <p class="compact-item-desc">
                                <?= htmlspecialchars($ni['pesan_ringkas']); ?> (<strong>Rp <?= number_format((float)$ni['total_tunggakan'], 0, ',', '.'); ?></strong>)
                                <?php if ($tglAj !== ''): ?>
                                    <br><i class="fa-solid fa-handshake"></i> <strong>Ajuan Janji Bayar:</strong> <?= date('d/m/Y', strtotime($tglAj)); ?>
                                <?php elseif ($isDtlk): ?>
                                    <br><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($catRaw); ?>
                                <?php endif; ?>
                            </p>

                            <?php if ($frzData): ?>
                                <?php if ($frzData['bisa_di_acc']): ?>
                                <form method="POST" action="<?= htmlspecialchars(urlIuran($current_page)); ?>" class="detail-freeze-form">
                                    <input type="hidden" name="penghuni_id" value="<?= $pidNi; ?>">
                                    <input type="hidden" name="nomor_kamar" value="<?= htmlspecialchars($ni['no_kamar']); ?>">

                                    <div class="row-input-dual">
                                        <input type="date" name="tanggal_janji_bayar"
                                               min="<?= date('Y-m-d'); ?>"
                                               value="<?= htmlspecialchars($tglAj); ?>"
                                               class="detail-freeze-date-input" required>
                                        <input type="text" name="catatan_tunggakan"
                                               value="<?= htmlspecialchars($isDtlk ? '' : $catRaw); ?>"
                                               placeholder="Catatan persetujuan..."
                                               class="detail-freeze-text-input">
                                    </div>

                                    <div class="kamar-notif-mob-actions">
                                        <button type="submit" name="setujui_unfreeze_iuran" value="1"
                                                onclick="ubahIkonKunci(this)"
                                                class="btn-detail-unfreeze">
                                            <i class="fa-solid fa-lock-open"></i>
                                            <span>Setujui &amp; Unfreeze</span>
                                        </button>
                                        <?php if ($tglAj !== ''): ?>
                                        <button type="submit" name="tolak_unfreeze_iuran" value="1" class="kamar-notif-btn-kelola">
                                            <i class="fa-solid fa-xmark"></i> Tolak
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                                <?php else: ?>
                                <p class="compact-item-desc"><em>Akun Anda sendiri sedang ter-freeze. Hubungi Pemilik Kos untuk membuka akses.</em></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ACCORDION 2: JATUH TEMPO / MASA TENGGANG IURAN (MOBILE - KUNING) -->
                <?php if (!empty($notif_iuran_warn)): ?>
                <div class="kamar-notif-accordion accordion-warning">
                    <button type="button" class="kamar-notif-accordion-header" onclick="toggleNotifIuran('listNotifIuranWarnMob', 'iconNotifIuranWarnMob')">
                        <div class="kamar-notif-acc-left">
                            <span class="kamar-notif-acc-icon"><i class="fa-solid fa-clock"></i></span>
                            <div class="kamar-notif-acc-text">
                                <strong><?= count($notif_iuran_warn); ?> Tagihan Iuran Mendekati / Masa Tenggang</strong>
                                <span>Ketuk untuk kirim pengingat WA</span>
                            </div>
                        </div>
                        <span class="kamar-notif-acc-toggle">
                            Lihat <i class="fa-solid fa-chevron-down" id="iconNotifIuranWarnMob"></i>
                        </span>
                    </button>

                    <div class="kamar-notif-accordion-body" id="listNotifIuranWarnMob">
                        <?php foreach ($notif_iuran_warn as $ni): ?>
                        <div class="kamar-notif-compact-item item-warning">
                            <div class="compact-item-top">
                                <strong>
                                    <i class="fa-solid fa-bell"></i>
                                    Kamar <?= htmlspecialchars(sprintf('%02d', (int)$ni['no_kamar'])); ?> &bull; <?= htmlspecialchars($ni['nama_penghuni']); ?>
                                </strong>
                                <span class="compact-badge badge-text-warning">
                                    <?= htmlspecialchars($ni['label_status']); ?>
                                </span>
                            </div>
                            <p class="compact-item-desc">
                                <?= htmlspecialchars($ni['pesan_ringkas']); ?> (<strong>Rp <?= number_format((float)$ni['total_tunggakan'], 0, ',', '.'); ?></strong>)
                            </p>
                            <div class="kamar-notif-mob-actions">
                                <?php if (!empty($ni['wa_pengingat_url'])): ?>
                                <a href="<?= htmlspecialchars($ni['wa_pengingat_url']); ?>" target="_blank" class="kamar-notif-btn-wa">
                                    <i class="fa-brands fa-whatsapp"></i> Ingatkan WA
                                </a>
                                <?php endif; ?>
                                <a href="<?= urlIuran($current_page, ['filter' => 'belum_bayar', 'date_range' => $date_range]); ?>"
                                   onclick="filterTabPenghuni(event, 'belum_bayar')" class="kamar-notif-btn-kelola">
                                    Lihat Daftar
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="kelola-action-row iuran-action-row-mobile">
                    <a href="/?page=<?= urlencode($current_page); ?>&modal=tambah_pengeluaran" class="btn-kelola-action">
                        <span class="icon-kelola-white"><i class="fa-solid fa-plus"></i></span>
                        Tambah Pengeluaran
                    </a>
                    <a href="/?page=<?= urlencode($current_page); ?>&modal=set_nominal_iuran" class="btn-set-iuran">
                        <i class="fa-solid fa-gear"></i> Set Nominal Iuran
                    </a>
                    <div class="box-kelola-saldo">
                        <span class="icon-kelola-white"><i class="fa-solid fa-wallet"></i></span>
                        Rp. <?= number_format((float)$saldo_kas_iuran, 0, ',', '.'); ?>
                    </div>
                    <button type="button" onclick="bukaModalCetakIuran()" class="btn-cetak-pdf iuran-btn-full">
                        <i class="fa-solid fa-file-pdf"></i> Cetak PDF Laporan Iuran
                    </button>
                </div>
                <?php else: ?>
                <div class="kas-total-card iuran-saldo-card-mobile">
                    <span class="kas-total-label">Saldo Bersih</span>
                    <span class="kas-total-value">Rp. <?= number_format((float)$saldo_kas_iuran, 0, ',', '.'); ?></span>
                </div>
                <div class="iuran-cetak-wrap-mobile">
                    <button type="button" onclick="bukaModalCetakIuran()" class="btn-cetak-pdf iuran-btn-full">
                        <i class="fa-solid fa-file-pdf"></i> Cetak PDF Rekap Iuran
                    </button>
                </div>
                <h3 class="section-title-white iuran-subtitle-mobile">
                    <?= $is_filtered ? 'Riwayat Transaksi Iuran' : 'Transaksi Iuran Hari Ini'; ?>
                </h3>
                <?php endif; ?>

                <!-- Filter tanggal Mobile -->
                <form method="GET" action="" class="filter-range-form iuran-filter-form-mobile">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                    <?php if ($filter_status !== 'semua'): ?>
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter_status); ?>">
                    <?php endif; ?>
                    <div class="flatpickr-box-wrapper">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                            placeholder="Filter Riwayat Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                        <a href="<?= urlIuran($current_page, ['filter' => $filter_status]); ?>" class="btn-reset-range" title="Reset ke Hari Ini">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Riwayat Transaksi Mobile -->
                <div class="kelola-transaksi-list">
                    <?php if (empty($daftar_transaksi)): ?>
                    <div class="empty-state-card">
                        <i class="fa-solid fa-folder-open"></i>
                        <span><?= $is_filtered ? 'Tidak ada riwayat transaksi pada tanggal tersebut' : 'Belum ada transaksi iuran hari ini. Gunakan filter tanggal untuk riwayat sebelumnya.'; ?></span>
                    </div>
                    <?php else: ?>
                    <?php foreach ($daftar_transaksi as $tx): ?>
                    <div class="kelola-transaksi-card">
                        <div class="kelola-tx-left">
                            <div class="kelola-tx-icon"><i class="<?= $tx['icon']; ?>"></i></div>
                            <div class="kelola-tx-info">
                                <h4 class="kelola-tx-title"><?= htmlspecialchars($tx['judul']); ?></h4>
                                <span class="kelola-tx-date"><?= htmlspecialchars($tx['tanggal']); ?></span>
                            </div>
                        </div>
                        <span class="<?= $tx['tipe'] === 'keluar' ? 'kelola-nominal-red' : 'kelola-nominal-green'; ?>">
                            <?= htmlspecialchars($tx['nominal']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($can_manage): ?>
                <div class="kelola-filter-row">
                    <?php foreach ($filter_tabs as $key => $tab): ?>
                    <a href="<?= urlIuran($current_page, ['filter' => $key, 'date_range' => $date_range]); ?>"
                        data-filter-tab="<?= $key; ?>"
                        onclick="filterTabPenghuni(event, '<?= $key; ?>')"
                        class="filter-pill <?= $filter_status === $key ? 'active' : 'inactive'; ?>">
                        <?= htmlspecialchars($tab['label']); ?> (<?= (int)$tab['jumlah']; ?>)
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="kelola-penghuni-list">
                    <?php if (empty($daftar_penghuni)): ?>
                    <div class="iuran-empty-penghuni-mobile">
                        <i class="fa-solid fa-user-slash"></i>
                        <p>Tidak ada data penghuni</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($daftar_penghuni as $p): ?>
                    <div class="kelola-penghuni-card penghuni-filter-item"
                         data-lunas="<?= $p['is_lunas'] ? '1' : '0'; ?>"
                         data-verifikasi="<?= $p['is_verifikasi'] ? '1' : '0'; ?>">
                        <div class="kelola-penghuni-info">
                            <div class="kelola-penghuni-top">
                                <h4 class="kelola-penghuni-name"><?= htmlspecialchars($p['nama']); ?></h4>
                                <span class="badge-kamar-putih">Kamar <?= htmlspecialchars($p['kamar']); ?></span>
                            </div>
                            <span class="kelola-penghuni-nominal">Rp. <?= number_format((float)$p['nominal'], 0, ',', '.'); ?></span>
                        </div>
                        <div class="iuran-penghuni-actions">
                            <?php if (($p['status_verifikasi'] ?? '') === 'pending'): ?>
                            <a href="/?page=verifikasi_bayar" class="iuran-btn-verif-pill" title="Konfirmasi Bukti Transfer">
                                <i class="fa-solid fa-clock-rotate-left"></i> Verifikasi
                            </a>
                            <?php endif; ?>

                            <?php if (!$p['is_lunas'] && !empty($p['sudah_diingatkan'])): ?>
                            <span class="badge-kamar-putih" title="Sudah diwakilkan pada fase <?= htmlspecialchars($fase_reminder_sekarang); ?>">
                                <i class="fa-solid fa-check-double"></i> Diingatkan <?= htmlspecialchars($p['reminder_sent_by']); ?> (<?= $p['reminder_jam']; ?>)
                            </span>
                            <?php endif; ?>

                            <a href="<?= htmlspecialchars(buatLinkWaIuran($p, $nama_iuran_saat_ini, $tgl_selesai_iuran, $selisih_hari, $tgl_batas_freeze_iuran, $pengirim_tag)); ?>"
                               target="_blank"
                               onclick="catatKlikWaIuran(<?= (int)$p['penghuni_kamar_id']; ?>, this)"
                               class="btn-chat-wa">
                                <i class="fa-brands fa-whatsapp"></i> <?= !empty($p['sudah_diingatkan']) ? 'Chat Ulang' : 'Chat'; ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </main>
        </div>
        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title"><?= htmlspecialchars($judul_halaman); ?></h2>
            <p class="kelola-desktop-subtitle"><?= htmlspecialchars($subjudul_halaman); ?></p>

            <?php if ($can_manage): ?>
            <div class="desktop-kelola-stats">
                <div class="desktop-kelola-stat-card">
                    <span class="desktop-kelola-stat-label">Total Penghuni</span>
                    <span class="desktop-kelola-stat-val"><?= $total_penghuni; ?></span>
                </div>
                <div class="desktop-kelola-stat-card">
                    <span class="desktop-kelola-stat-label">Sudah Bayar</span>
                    <span class="desktop-kelola-stat-val"><?= $sudah_bayar . '/' . $total_penghuni; ?></span>
                </div>
                <div class="desktop-kelola-stat-card">
                    <span class="desktop-kelola-stat-label"><?= htmlspecialchars($nama_iuran_saat_ini ?? 'Iuran Aktif'); ?></span>
                    <span class="desktop-kelola-stat-val iuran-stat-val-white">Rp. <?= number_format((float)$nominal_iuran_saat_ini, 0, ',', '.'); ?></span>
                    <span class="desktop-kelola-stat-periode">
                        Periode: <?= date('d/m/Y', strtotime($tgl_mulai_iuran)); ?> s/d
                        <?= date('d/m/Y', strtotime($tgl_selesai_iuran)); ?>
                    </span>
                </div>
            </div>

            <!-- ACCORDION 1: AKUN FREEZE IURAN (DESKTOP - MERAH/PINK, PENGAJUAN JANJI BAYAR DI ATAS) -->
            <?php if (!empty($notif_iuran_freeze)): ?>
            <div class="kamar-notif-accordion kamar-notif-acc-desktop accordion-danger">
                <button type="button" class="kamar-notif-accordion-header" onclick="toggleNotifIuran('listNotifIuranFrzDesk', 'iconNotifIuranFrzDesk')">
                    <div class="kamar-notif-acc-left">
                        <span class="kamar-notif-acc-icon"><i class="fa-solid fa-lock"></i></span>
                        <div class="kamar-notif-acc-text">
                            <strong><?= count($notif_iuran_freeze); ?> Akun Penghuni dalam Status Freeze Iuran (Dibekukan)</strong>
                            <span>Klik untuk melihat pengajuan janji bayar &amp; mengaktifkan kembali akun</span>
                        </div>
                    </div>
                    <span class="kamar-notif-acc-toggle">
                        Lihat <i class="fa-solid fa-chevron-down" id="iconNotifIuranFrzDesk"></i>
                    </span>
                </button>

                <div class="kamar-notif-accordion-body" id="listNotifIuranFrzDesk">
                    <?php foreach ($notif_iuran_freeze as $ni): ?>
                    <?php
                        $pidNi   = (int)($ni['penghuni_id'] ?? 0);
                        $frzData = $map_freeze_iuran_pid[$pidNi] ?? null;
                        $tglAj   = $ni['tanggal_janji_bayar'] ?? '';
                        $catRaw  = trim((string)($ni['catatan_tunggakan'] ?? ''));
                        $isDtlk  = str_starts_with($catRaw, '[Ditolak]');
                    ?>
                    <div class="kamar-notif-compact-item item-danger">
                        <div class="kamar-notif-compact-row">
                            <div class="compact-row-info">
                                <i class="fa-solid fa-lock"></i>
                                <div>
                                    <strong>
                                        Kamar <?= htmlspecialchars(sprintf('%02d', (int)$ni['no_kamar'])); ?> &mdash; <?= htmlspecialchars($ni['nama_penghuni']); ?>
                                        <span class="compact-badge badge-text-danger">
                                            <?= $tglAj !== '' ? 'Pengajuan Janji Bayar (Menunggu ACC)' : htmlspecialchars($ni['label_status']); ?>
                                        </span>
                                    </strong>
                                    <span>
                                        <?= htmlspecialchars($ni['pesan_ringkas']); ?> Total: <strong>Rp <?= number_format((float)$ni['total_tunggakan'], 0, ',', '.'); ?></strong>
                                        <?php if ($tglAj !== ''): ?>
                                            &bull; Ajuan Janji Bayar: <strong><?= date('d/m/Y', strtotime($tglAj)); ?></strong>
                                        <?php elseif ($isDtlk): ?>
                                            &bull; <strong><?= htmlspecialchars($catRaw); ?></strong>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if ($frzData): ?>
                            <?php if ($frzData['bisa_di_acc']): ?>
                            <form method="POST" action="<?= htmlspecialchars(urlIuran($current_page)); ?>" class="detail-freeze-form">
                                <input type="hidden" name="penghuni_id" value="<?= $pidNi; ?>">
                                <input type="hidden" name="nomor_kamar" value="<?= htmlspecialchars($ni['no_kamar']); ?>">

                                <div class="row-input-dual">
                                    <input type="date" name="tanggal_janji_bayar"
                                           min="<?= date('Y-m-d'); ?>"
                                           value="<?= htmlspecialchars($tglAj); ?>"
                                           class="detail-freeze-date-input" required>
                                    <input type="text" name="catatan_tunggakan"
                                           value="<?= htmlspecialchars($isDtlk ? '' : $catRaw); ?>"
                                           placeholder="Catatan persetujuan (opsional)..."
                                           class="detail-freeze-text-input">
                                    <?php if ($tglAj !== ''): ?>
                                    <input type="text" name="alasan_tolak_janji"
                                           placeholder="Alasan penolakan (jika ditolak)..."
                                           class="detail-freeze-text-input">
                                    <?php endif; ?>
                                </div>

                                <div class="kamar-notif-desk-actions">
                                    <button type="submit" name="setujui_unfreeze_iuran" value="1"
                                            onclick="ubahIkonKunci(this)"
                                            class="btn-detail-unfreeze">
                                        <i class="fa-solid fa-lock-open"></i>
                                        <span>Setujui &amp; Aktifkan Kembali Akun (Unfreeze)</span>
                                    </button>
                                    <?php if ($tglAj !== ''): ?>
                                    <button type="submit" name="tolak_unfreeze_iuran" value="1" class="kamar-notif-desk-kelola">
                                        <i class="fa-solid fa-xmark"></i> Tolak Pengajuan
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                            <?php else: ?>
                            <p class="compact-item-desc"><em>Akun Anda sendiri sedang ter-freeze. Hubungi Pemilik Kos untuk membuka akses akun.</em></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ACCORDION 2: TAGIHAN IURAN MENDEKATI / MASA TENGGANG (DESKTOP - KUNING SEPERTI SEWA) -->
            <?php if (!empty($notif_iuran_warn)): ?>
            <div class="kamar-notif-accordion kamar-notif-acc-desktop accordion-warning">
                <button type="button" class="kamar-notif-accordion-header" onclick="toggleNotifIuran('listNotifIuranWarnDesk', 'iconNotifIuranWarnDesk')">
                    <div class="kamar-notif-acc-left">
                        <span class="kamar-notif-acc-icon"><i class="fa-solid fa-clock"></i></span>
                        <div class="kamar-notif-acc-text">
                            <strong><?= count($notif_iuran_warn); ?> Tagihan Iuran Mendekati Jatuh Tempo / Masa Tenggang</strong>
                            <span>Klik untuk mengirim pengingat pembayaran ke penghuni</span>
                        </div>
                    </div>
                    <span class="kamar-notif-acc-toggle">
                        Lihat <i class="fa-solid fa-chevron-down" id="iconNotifIuranWarnDesk"></i>
                    </span>
                </button>

                <div class="kamar-notif-accordion-body" id="listNotifIuranWarnDesk">
                    <?php foreach ($notif_iuran_warn as $ni): ?>
                    <div class="kamar-notif-compact-row">
                        <div class="compact-row-info">
                            <strong>
                                Kamar <?= htmlspecialchars(sprintf('%02d', (int)$ni['no_kamar'])); ?> - <?= htmlspecialchars($ni['nama_penghuni']); ?>
                                <span class="compact-badge badge-text-warning"><?= htmlspecialchars($ni['label_status']); ?></span>
                            </strong>
                            <span>
                                <?= htmlspecialchars($ni['pesan_ringkas']); ?> Total: <strong>Rp <?= number_format((float)$ni['total_tunggakan'], 0, ',', '.'); ?></strong>
                            </span>
                        </div>
                        <div class="kamar-notif-desk-actions">
                            <?php if (!empty($ni['wa_pengingat_url'])): ?>
                            <a href="<?= htmlspecialchars($ni['wa_pengingat_url']); ?>" target="_blank" class="kamar-notif-desk-wa">
                                <i class="fa-brands fa-whatsapp"></i> Ingatkan WA
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Baris Aksi Desktop Pengurus (Pemilik / PJ) -->
            <div class="iuran-desktop-action-bar">
                <a href="/?page=<?= urlencode($current_page); ?>&modal=tambah_pengeluaran" class="iuran-btn-pill-action iuran-nowrap">
                    <i class="fa-solid fa-plus"></i> Tambah Pengeluaran
                </a>
                <a href="/?page=<?= urlencode($current_page); ?>&modal=set_nominal_iuran" class="btn-set-iuran iuran-nowrap">
                    <i class="fa-solid fa-gear"></i> Set Nominal Iuran
                </a>
                <div class="iuran-box-pill-saldo iuran-nowrap">
                    <i class="fa-solid fa-wallet"></i> Rp. <?= number_format((float)$saldo_kas_iuran, 0, ',', '.'); ?>
                </div>

                <!-- Tombol Cetak PDF Desktop Pengurus -->
                <button type="button" onclick="bukaModalCetakIuran()" class="btn-cetak-pdf">
                    <i class="fa-solid fa-file-pdf"></i> Cetak PDF
                </button>

                <form method="GET" action="" class="filter-range-form-desktop iuran-desktop-filter-form">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                    <?php if ($filter_status !== 'semua'): ?>
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter_status); ?>">
                    <?php endif; ?>
                    <div class="flatpickr-box-wrapper iuran-flatpickr-full">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                            placeholder="Filter Riwayat Tanggal..." class="flatpickr-range-input iuran-input-full" readonly>
                        <?php if ($is_filtered): ?>
                        <a href="<?= urlIuran($current_page, ['filter' => $filter_status]); ?>" class="btn-reset-range" title="Reset ke Hari Ini">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="desktop-kelola-mutasi-list">
                <?php if (empty($daftar_transaksi)): ?>
                <div class="empty-state-card">
                    <i class="fa-solid fa-folder-open"></i>
                    <span><?= $is_filtered ? 'Tidak ada riwayat transaksi pada rentang tanggal yang dipilih' : 'Belum ada transaksi iuran hari ini. Pilih rentang tanggal di atas untuk melihat riwayat sebelumnya.'; ?></span>
                </div>
                <?php else: ?>
                <?php foreach ($daftar_transaksi as $tx): ?>
                <div class="desktop-mutasi-card">
                    <div class="desktop-mutasi-left">
                        <div class="desktop-mutasi-icon"><i class="<?= $tx['icon']; ?>"></i></div>
                        <div class="desktop-mutasi-info">
                            <h4><?= htmlspecialchars($tx['judul']); ?></h4>
                            <span><?= htmlspecialchars($tx['tanggal']); ?></span>
                        </div>
                    </div>
                    <span class="<?= $tx['tipe'] === 'keluar' ? 'desktop-mutasi-badge-red' : 'desktop-mutasi-badge-green'; ?>">
                        <?= htmlspecialchars($tx['nominal']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Tab Filter Penghuni Desktop -->
            <div class="iuran-desktop-filters">
                <?php foreach ($filter_tabs as $key => $tab): ?>
                <a href="<?= urlIuran($current_page, ['filter' => $key, 'date_range' => $date_range]); ?>"
                    data-filter-tab="<?= $key; ?>"
                    onclick="filterTabPenghuni(event, '<?= $key; ?>')"
                    class="iuran-filter-pill <?= $filter_status === $key ? 'active' : 'inactive'; ?>">
                    <?= htmlspecialchars($tab['label']); ?> (<?= (int)$tab['jumlah']; ?>)
                </a>
                <?php endforeach; ?>
            </div>

            <div class="desktop-penghuni-list">
                <?php if (empty($daftar_penghuni)): ?>
                <div class="iuran-empty-penghuni-desktop">
                    <i class="fa-solid fa-user-slash"></i>
                    <p>Tidak ada data penghuni</p>
                </div>
                <?php else: ?>
                <?php foreach ($daftar_penghuni as $p): ?>
                <div class="desktop-penghuni-card penghuni-filter-item"
                     data-lunas="<?= $p['is_lunas'] ? '1' : '0'; ?>"
                     data-verifikasi="<?= $p['is_verifikasi'] ? '1' : '0'; ?>">
                    <div class="desktop-penghuni-info">
                        <div class="desktop-penghuni-top">
                            <h4><?= htmlspecialchars($p['nama']); ?></h4>
                            <span class="iuran-kamar-pill">Kamar <?= htmlspecialchars($p['kamar']); ?></span>
                        </div>
                        <span class="desktop-penghuni-nominal">Rp. <?= number_format((float)$p['nominal'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="iuran-penghuni-actions">
                        <?php if (($p['status_verifikasi'] ?? '') === 'pending'): ?>
                        <a href="/?page=verifikasi_bayar" class="iuran-btn-verif-pill" title="Konfirmasi Bukti Transfer">
                            <i class="fa-solid fa-clock-rotate-left"></i> Verifikasi
                        </a>
                        <?php endif; ?>

                        <?php if (!$p['is_lunas'] && !empty($p['sudah_diingatkan'])): ?>
                        <span class="badge-kamar-putih" title="Sudah diwakilkan pada fase <?= htmlspecialchars($fase_reminder_sekarang); ?>">
                            <i class="fa-solid fa-check-double"></i> Diingatkan <?= htmlspecialchars($p['reminder_sent_by']); ?> (<?= $p['reminder_jam']; ?>)
                        </span>
                        <?php endif; ?>

                        <a href="<?= htmlspecialchars(buatLinkWaIuran($p, $nama_iuran_saat_ini, $tgl_selesai_iuran, $selisih_hari, $tgl_batas_freeze_iuran, $pengirim_tag)); ?>"
                           target="_blank"
                           onclick="catatKlikWaIuran(<?= (int)$p['penghuni_kamar_id']; ?>, this)"
                           class="iuran-btn-chat-pill">
                            <i class="fa-brands fa-whatsapp"></i> <?= !empty($p['sudah_diingatkan']) ? 'Chat Ulang' : 'Chat'; ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php else: ?>
            <!-- Tampilan Penghuni Biasa Desktop -->
            <div class="desktop-kelola-stat-card iuran-saldo-stat-desktop">
                <span class="desktop-kelola-stat-label">Saldo Bersih</span>
                <span class="desktop-kelola-stat-val">Rp. <?= number_format((float)$saldo_kas_iuran, 0, ',', '.'); ?></span>
            </div>

            <div class="iuran-header-row iuran-header-penghuni-desktop">
                <div class="iuran-header-left-group">
                    <h3 class="desktop-section-title iuran-no-margin">
                        <?= $is_filtered ? 'Riwayat Transaksi Iuran' : 'Transaksi Iuran Hari Ini'; ?>
                    </h3>
                    <button type="button" onclick="bukaModalCetakIuran()" class="btn-cetak-pdf">
                        <i class="fa-solid fa-file-pdf"></i> Cetak Rekap Iuran
                    </button>
                </div>

                <form method="GET" action="" class="filter-range-form-desktop iuran-no-margin">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                    <div class="flatpickr-box-wrapper iuran-no-margin">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                            placeholder="Filter Riwayat Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                        <a href="<?= urlIuran($current_page); ?>" class="btn-reset-range" title="Reset ke Hari Ini">
                            <i class="fa-solid fa-rotate-left"></i> Hari Ini
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="desktop-kelola-mutasi-list">
                <?php if (empty($daftar_transaksi)): ?>
                <div class="empty-state-card">
                    <i class="fa-solid fa-folder-open"></i>
                    <span><?= $is_filtered ? 'Tidak ada riwayat transaksi pada tanggal tersebut' : 'Belum ada transaksi iuran hari ini. Gunakan filter tanggal di atas untuk riwayat sebelumnya.'; ?></span>
                </div>
                <?php else: ?>
                <?php foreach ($daftar_transaksi as $tx): ?>
                <div class="desktop-mutasi-card">
                    <div class="desktop-mutasi-left">
                        <div class="desktop-mutasi-icon"><i class="<?= $tx['icon']; ?>"></i></div>
                        <div class="desktop-mutasi-info">
                            <h4><?= htmlspecialchars($tx['judul']); ?></h4>
                            <span><?= htmlspecialchars($tx['tanggal']); ?></span>
                        </div>
                    </div>
                    <span class="<?= $tx['tipe'] === 'keluar' ? 'desktop-mutasi-badge-red' : 'desktop-mutasi-badge-green'; ?>">
                        <?= htmlspecialchars($tx['nominal']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- MODAL 1: TAMBAH PENGELUARAN -->
    <?php if ($can_manage && $show_modal_pengeluaran): ?>
    <div class="modal-overlay active">
        <div class="modal-card">
            <h3 class="modal-title">Tambah Pengeluaran</h3>
            <?php if (!empty($error_modal)): ?>
            <p class="modal-error-text"><?= htmlspecialchars($error_modal); ?></p>
            <?php endif; ?>
            <form action="/?page=<?= urlencode($current_page); ?>&modal=tambah_pengeluaran" method="POST" class="modal-form">
                <input type="hidden" name="simpan_pengeluaran" value="1">
                <div class="modal-field">
                    <label class="modal-label" for="keterangan">Keterangan</label>
                    <input type="text" id="keterangan" name="keterangan" class="modal-input"
                        placeholder="Contoh: Pembelian galon & alat kebersihan" required>
                </div>
                <div class="modal-field">
                    <label class="modal-label" for="nominal">Nominal (Rp)</label>
                    <input type="text" id="nominal" name="nominal" class="modal-input format-rupiah-input"
                        placeholder="Contoh: 150.000" inputmode="numeric" required>
                </div>
                <div class="modal-actions">
                    <a href="/?page=<?= urlencode($current_page); ?>" class="btn-modal-cancel">Batal</a>
                    <button type="submit" class="btn-modal-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODAL 2: SET NOMINAL, NAMA & PERIODE IURAN -->
    <?php if ($can_manage && $show_modal_set_iuran): ?>
    <div class="modal-overlay active">
        <div class="modal-card">
            <h3 class="modal-title">Atur Nominal &amp; Periode Iuran</h3>
            <p class="modal-subtitle-text">Tentukan nama iuran, besaran nominal, dan rentang tanggal berlakunya.</p>
            <?php if (!empty($error_modal)): ?>
            <p class="modal-error-text"><?= htmlspecialchars($error_modal); ?></p>
            <?php endif; ?>
            <form action="/?page=<?= urlencode($current_page); ?>&modal=set_nominal_iuran" method="POST" class="modal-form">
                <input type="hidden" name="simpan_nominal_iuran" value="1">
                <div class="modal-field">
                    <label class="modal-label" for="nama_iuran">Keterangan / Nama Iuran</label>
                    <input type="text" id="nama_iuran" name="nama_iuran" class="modal-input"
                        value="<?= htmlspecialchars($nama_iuran_saat_ini ?? 'Iuran Kebersihan & Keamanan'); ?>"
                        placeholder="Contoh: Iuran Kebersihan & Keamanan" required>
                </div>
                <div class="modal-field">
                    <label class="modal-label" for="nominal_iuran">Nominal Iuran (Rp)</label>
                    <input type="text" id="nominal_iuran" name="nominal_iuran" class="modal-input format-rupiah-input"
                        value="<?= number_format((float)$nominal_iuran_saat_ini, 0, ',', '.'); ?>"
                        placeholder="Contoh: 50.000" inputmode="numeric" required>
                </div>
                <div class="modal-field">
                    <label class="modal-label" for="periode_iuran_range">Rentang Tanggal Berlaku</label>
                    <div class="modal-input-icon-wrapper">
                        <i class="fa-regular fa-calendar modal-input-icon"></i>
                        <input type="text" id="periode_iuran_range" name="periode_iuran_range"
                            class="modal-input modal-input-with-icon flatpickr-range-input-modal"
                            value="<?= htmlspecialchars($tgl_mulai_iuran . ' to ' . $tgl_selesai_iuran); ?>"
                            placeholder="Pilih Tanggal Mulai s/d Selesai..." readonly required>
                    </div>
                </div>
                <div class="modal-actions">
                    <a href="/?page=<?= urlencode($current_page); ?>" class="btn-modal-cancel">Batal</a>
                    <button type="submit" class="btn-modal-submit btn-submit-blue">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

<!-- MODAL 3: PILIH PERIODE CETAK BUKU KAS IURAN (SEMUA ROLE) -->
    <div id="modalFilterCetakIuran" class="modal-overlay" onclick="tutupModalCetakIuran()">
        <div class="modal-card modal-cetak-kas-card" onclick="event.stopPropagation()">
            <h3 class="modal-title"><i class="fa-solid fa-file-pdf icon-pdf-red"></i> Cetak Laporan Iuran</h3>
            <p class="modal-cetak-kas-sub">Pilih cakupan waktu laporan iuran kas bersama yang ingin dicetak.</p>

            <form action="/" method="GET" target="_blank" onsubmit="tutupModalCetakIuran()">
                <input type="hidden" name="page" value="cetak_iuran">

                <div class="modal-field modal-cetak-field">
                    <label class="modal-label">Cakupan Periode</label>
                    <div class="periode-radio-grid">
                        <label class="alasan-option-label periode-radio-option">
                            <input type="radio" name="periode" value="bulanan" checked onchange="toggleFormCetakIuran('bulanan')">
                            <span>Bulanan</span>
                        </label>
                        <label class="alasan-option-label periode-radio-option">
                            <input type="radio" name="periode" value="tahunan" onchange="toggleFormCetakIuran('tahunan')">
                            <span>Tahunan</span>
                        </label>
                        <label class="alasan-option-label periode-radio-option">
                            <input type="radio" name="periode" value="semua" onchange="toggleFormCetakIuran('semua')">
                            <span>Semua</span>
                        </label>
                    </div>
                </div>

                <div class="modal-field modal-cetak-field modal-cetak-field-bulan" id="boxIuranBulan">
                    <label class="modal-label" for="selectBulanIuran">Pilih Bulan</label>
                    <select name="bulan" id="selectBulanIuran" class="modal-input modal-cetak-select">
                        <?php
                        $bulan_list = [
                            1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April',
                            5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus',
                            9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'
                        ];
                        $current_m = (int)date('n');
                        foreach ($bulan_list as $num => $nm): ?>
                        <option value="<?= $num; ?>" <?= ($num === $current_m) ? 'selected' : ''; ?>><?= $nm; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-field modal-cetak-field modal-cetak-field-tahun" id="boxIuranTahun">
                    <label class="modal-label" for="selectTahunIuran">Pilih Tahun</label>
                    <select name="tahun" id="selectTahunIuran" class="modal-input modal-cetak-select">
                        <?php
                        $tahun_skrg = (int)date('Y');
                        for ($y = $tahun_skrg; $y >= $tahun_skrg - 5; $y--): ?>
                        <option value="<?= $y; ?>" <?= ($y === $tahun_skrg) ? 'selected' : ''; ?>><?= $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="modal-actions modal-cetak-actions">
                    <button type="button" class="btn-modal-cancel" onclick="tutupModalCetakIuran()">Batal</button>
                    <button type="submit" class="btn-modal-submit btn-cetak-submit-teal">Buka &amp; Cetak PDF</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    function closeToastIuran() {
        const toast = document.getElementById('toastAlertBox');
        if (toast) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-15px) scale(0.95)';
            setTimeout(() => toast.remove(), 300);
        }
    }

    function toggleNotifIuran(bodyId, iconId) {
        const body = document.getElementById(bodyId);
        const icon = document.getElementById(iconId);
        if (!body) return;
        const isOpen = body.classList.toggle('is-open');
        if (icon) {
            icon.style.transform = isOpen ? 'rotate(180deg)' : 'rotate(0deg)';
        }
    }

    function ubahIkonKunci(btn) {
        const form = btn.closest('form');
        if (form && !form.checkValidity()) return;

        const icon = btn.querySelector('i');
        const textSpan = btn.querySelector('span');

        if (icon) {
            icon.classList.remove('fa-lock-open');
            icon.classList.add('fa-lock');
        }
        if (textSpan) {
            textSpan.textContent = 'Memproses...';
        }

        btn.style.opacity = '0.85';
        btn.style.pointerEvents = 'none';
    }

    document.querySelectorAll('.flatpickr-range-input').forEach(function(input) {
        flatpickr(input, {
            mode: "range",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            onClose: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    instance.element.closest('form').submit();
                }
            }
        });
    });

    document.querySelectorAll('.flatpickr-range-input-modal').forEach(function(input) {
        flatpickr(input, {
            mode: "range",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            altInputClass: "modal-input modal-input-with-icon"
        });
    });

    document.querySelectorAll('.format-rupiah-input').forEach(function(input) {
        input.addEventListener('input', function() {
            let angka = this.value.replace(/[^0-9]/g, '');
            this.value = angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
    });

    function catatKlikWaIuran(pkId, el) {
        fetch('/?page=iuran&action=catat_reminder&pk_id=' + encodeURIComponent(pkId))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data && data.ok) {
                    const parent = el.parentElement;
                    let badge = parent.querySelector('.badge-reminder-sent');
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'badge-kamar-putih badge-reminder-sent';
                        parent.insertBefore(badge, el);
                    }
                    badge.innerHTML = '<i class="fa-solid fa-check-double"></i> Diingatkan ' + data.by + ' (' + data.jam + ')';
                    el.innerHTML = '<i class="fa-brands fa-whatsapp"></i> Chat Ulang';
                }
            })
            .catch(function() {});
    }

    function filterTabPenghuni(e, kategori) {
        if (e) e.preventDefault();

        document.querySelectorAll('[data-filter-tab]').forEach(function (tab) {
            if (tab.getAttribute('data-filter-tab') === kategori) {
                tab.classList.add('active');
                tab.classList.remove('inactive');
            } else {
                tab.classList.remove('active');
                tab.classList.add('inactive');
            }
        });

        document.querySelectorAll('.penghuni-filter-item').forEach(function (card) {
            const isLunas = card.getAttribute('data-lunas') === '1';
            const isVerif = card.getAttribute('data-verifikasi') === '1';

            let tampil = true;
            if (kategori === 'belum_bayar') {
                tampil = !isLunas;
            } else if (kategori === 'verifikasi') {
                tampil = isVerif;
            }

            card.style.display = tampil ? '' : 'none';
        });

        const url = new URL(window.location.href);
        if (kategori === 'semua') {
            url.searchParams.delete('filter');
        } else {
            url.searchParams.set('filter', kategori);
        }
        window.history.replaceState({}, '', url);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const toast = document.getElementById('toastAlertBox');
        if (toast) setTimeout(closeToastIuran, 5000);

        const initialFilter = <?= json_encode($filter_status); ?>;
        if (initialFilter !== 'semua') {
            filterTabPenghuni(null, initialFilter);
        }
    });

    function bukaModalCetakIuran() {
        const modal = document.getElementById('modalFilterCetakIuran');
        if (modal) modal.classList.add('active');
    }

    function tutupModalCetakIuran() {
        const modal = document.getElementById('modalFilterCetakIuran');
        if (modal) modal.classList.remove('active');
    }

    function toggleFormCetakIuran(tipe) {
        const boxBulan = document.getElementById('boxIuranBulan');
        const boxTahun = document.getElementById('boxIuranTahun');
        const selBulan = document.getElementById('selectBulanIuran');
        const selTahun = document.getElementById('selectTahunIuran');

        if (tipe === 'bulanan') {
            boxBulan.style.display = 'block';
            boxTahun.style.display = 'block';
            if (selBulan) selBulan.disabled = false;
            if (selTahun) selTahun.disabled = false;
        } else if (tipe === 'tahunan') {
            boxBulan.style.display = 'none';
            boxTahun.style.display = 'block';
            if (selBulan) selBulan.disabled = true;
            if (selTahun) selTahun.disabled = false;
        } else {
            boxBulan.style.display = 'none';
            boxTahun.style.display = 'none';
            if (selBulan) selBulan.disabled = true;
            if (selTahun) selTahun.disabled = true;
        }
    }
    </script>
</body>

</html>