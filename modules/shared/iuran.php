<?php
/**
 * VIEW KELOLA / MONITORING IURAN
 */
require_once __DIR__ . '/handler/iuran_handler.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | <?= htmlspecialchars($judul_halaman); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>

<body class="dashboard-body">

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
                            Periode: <?= date('d/m/Y', strtotime($tgl_mulai_iuran)); ?> s/d <?= date('d/m/Y', strtotime($tgl_selesai_iuran)); ?>
                        </small>
                    </div>
                </div>

                <div class="kelola-action-row" style="flex-wrap: wrap; gap: 8px;">
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
                    <button type="button" onclick="bukaModalCetakIuran()" class="btn-cetak-pdf" style="width: 100%;">
                        <i class="fa-solid fa-file-pdf"></i> Cetak PDF Laporan Iuran
                    </button>
                </div>
                <?php else: ?>
                <div class="kas-total-card" style="margin-bottom: 4px;">
                    <span class="kas-total-label">Saldo Bersih</span>
                    <span class="kas-total-value">Rp. <?= number_format((float)$saldo_kas_iuran, 0, ',', '.'); ?></span>
                </div>
                <div style="margin: 6px 0 2px 0;">
                    <button type="button" onclick="bukaModalCetakIuran()" class="btn-cetak-pdf" style="width: 100%;">
                        <i class="fa-solid fa-file-pdf"></i> Cetak PDF Rekap Iuran
                    </button>
                </div>
                <h3 class="section-title-white" style="font-size: 15px; margin: 10px 0 4px 2px;">Riwayat Transaksi Iuran</h3>
                <?php endif; ?>

                <!-- Filter tanggal Mobile -->
                <form method="GET" action="" class="filter-range-form" style="margin-top: 10px;">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                    <?php if ($filter_status !== 'semua'): ?>
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter_status); ?>">
                    <?php endif; ?>
                    <div class="flatpickr-box-wrapper">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>" placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                        <a href="<?= urlIuran($current_page, ['filter' => $filter_status]); ?>" class="btn-reset-range" title="Reset">
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
                        <span><?= $is_filtered ? 'Tidak ada riwayat transaksi' : 'Belum ada riwayat transaksi iuran'; ?></span>
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
                    <a href="<?= urlIuran($current_page, ['filter' => $key, 'date_range' => $date_range]); ?>" class="filter-pill <?= $filter_status === $key ? 'active' : 'inactive'; ?>">
                        <?= htmlspecialchars($tab['label']); ?> (<?= (int)$tab['jumlah']; ?>)
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="kelola-penghuni-list">
                    <?php if (empty($daftar_penghuni)): ?>
                    <div class="empty-state-card" style="margin-top: 10px; background: #fff; padding: 20px; border-radius: 12px; text-align: center;">
                        <i class="fa-solid fa-user-slash" style="font-size: 24px; color: #a0aec0; margin-bottom: 8px;"></i>
                        <p style="margin: 0; font-size: 13.5px; font-weight: 600; color: #4a5568;">Tidak ada data penghuni</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($daftar_penghuni as $p): ?>
                    <div class="kelola-penghuni-card">
                        <div class="kelola-penghuni-info">
                            <div class="kelola-penghuni-top">
                                <h4 class="kelola-penghuni-name"><?= htmlspecialchars($p['nama']); ?></h4>
                                <span class="badge-kamar-putih">Kamar <?= htmlspecialchars($p['kamar']); ?></span>
                            </div>
                            <span class="kelola-penghuni-nominal">Rp. <?= number_format((float)$p['nominal'], 0, ',', '.'); ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <?php if (($p['status_verifikasi'] ?? '') === 'pending'): ?>
                                <a href="/?page=verifikasi_bayar" class="iuran-btn-verif-pill" title="Konfirmasi Bukti Transfer">
                                    <i class="fa-solid fa-clock-rotate-left"></i> Verifikasi
                                </a>
                            <?php endif; ?>
                            <a href="https://wa.me/<?= preg_replace('/^0/', '62', $p['no_wa']); ?>?text=Halo%20<?= urlencode($p['nama']); ?>" target="_blank" class="btn-chat-wa">
                                <i class="fa-brands fa-whatsapp"></i> Chat
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
                    <span class="desktop-kelola-stat-val" style="color: #ffffff;">Rp. <?= number_format((float)$nominal_iuran_saat_ini, 0, ',', '.'); ?></span>
                    <span class="desktop-kelola-stat-periode">
                        Periode: <?= date('d/m/Y', strtotime($tgl_mulai_iuran)); ?> s/d <?= date('d/m/Y', strtotime($tgl_selesai_iuran)); ?>
                    </span>
                </div>
            </div>

            <!-- Baris Aksi Desktop Pengurus (Pemilik / PJ) -->
            <div style="display: flex; align-items: center; gap: 10px; width: 100%; max-width: 824px; margin-bottom: 20px; flex-wrap: wrap;">
                <a href="/?page=<?= urlencode($current_page); ?>&modal=tambah_pengeluaran" class="iuran-btn-pill-action" style="white-space: nowrap;">
                    <i class="fa-solid fa-plus"></i> Tambah Pengeluaran
                </a>
                <a href="/?page=<?= urlencode($current_page); ?>&modal=set_nominal_iuran" class="btn-set-iuran" style="white-space: nowrap;">
                    <i class="fa-solid fa-gear"></i> Set Nominal Iuran
                </a>
                <div class="iuran-box-pill-saldo" style="white-space: nowrap;">
                    <i class="fa-solid fa-wallet"></i> Rp. <?= number_format((float)$saldo_kas_iuran, 0, ',', '.'); ?>
                </div>

                <!-- Tombol Cetak PDF Desktop Pengurus -->
                <button type="button" onclick="bukaModalCetakIuran()" class="btn-cetak-pdf">
                    <i class="fa-solid fa-file-pdf"></i> Cetak PDF
                </button>

                <form method="GET" action="" class="filter-range-form-desktop" style="margin: 0 0 0 auto; max-width: 240px; width: 100%;">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                    <?php if ($filter_status !== 'semua'): ?>
                    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter_status); ?>">
                    <?php endif; ?>
                    <div class="flatpickr-box-wrapper" style="margin: 0; width: 100%;">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>" placeholder="Rentang Tanggal..." class="flatpickr-range-input" readonly style="width: 100%;">
                        <?php if ($is_filtered): ?>
                        <a href="<?= urlIuran($current_page, ['filter' => $filter_status]); ?>" class="btn-reset-range" title="Reset Filter">
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
                    <span><?= $is_filtered ? 'Tidak ada riwayat transaksi pada rentang tanggal yang dipilih' : 'Belum ada riwayat transaksi iuran'; ?></span>
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
                <a href="<?= urlIuran($current_page, ['filter' => $key, 'date_range' => $date_range]); ?>" class="iuran-filter-pill <?= $filter_status === $key ? 'active' : 'inactive'; ?>">
                    <?= htmlspecialchars($tab['label']); ?> (<?= (int)$tab['jumlah']; ?>)
                </a>
                <?php endforeach; ?>
            </div>

            <div class="desktop-penghuni-list">
                <?php if (empty($daftar_penghuni)): ?>
                <div class="empty-state-card" style="margin-top: 10px; background: #fff; padding: 24px; border-radius: 12px; text-align: center; max-width: 824px;">
                    <i class="fa-solid fa-user-slash" style="font-size: 28px; color: #a0aec0; margin-bottom: 8px;"></i>
                    <p style="margin: 0; font-size: 14px; font-weight: 600; color: #4a5568;">Tidak ada data penghuni</p>
                </div>
                <?php else: ?>
                <?php foreach ($daftar_penghuni as $p): ?>
                <div class="desktop-penghuni-card">
                    <div class="desktop-penghuni-info">
                        <div class="desktop-penghuni-top">
                            <h4><?= htmlspecialchars($p['nama']); ?></h4>
                            <span class="iuran-kamar-pill">Kamar <?= htmlspecialchars($p['kamar']); ?></span>
                        </div>
                        <span class="desktop-penghuni-nominal">Rp. <?= number_format((float)$p['nominal'], 0, ',', '.'); ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <?php if (($p['status_verifikasi'] ?? '') === 'pending'): ?>
                            <a href="/?page=verifikasi_bayar" class="iuran-btn-verif-pill" title="Konfirmasi Bukti Transfer">
                                <i class="fa-solid fa-clock-rotate-left"></i> Verifikasi
                            </a>
                        <?php endif; ?>
                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', $p['no_wa']); ?>?text=Halo%20<?= urlencode($p['nama']); ?>" target="_blank" class="iuran-btn-chat-pill">
                            <i class="fa-brands fa-whatsapp"></i> Chat
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php else: ?>
            <!-- Tampilan Penghuni Biasa Desktop -->
            <div class="desktop-kelola-stat-card" style="max-width: 280px; margin-bottom: 24px;">
                <span class="desktop-kelola-stat-label">Saldo Bersih</span>
                <span class="desktop-kelola-stat-val">Rp. <?= number_format((float)$saldo_kas_iuran, 0, ',', '.'); ?></span>
            </div>

            <div class="iuran-header-row" style="display: flex; align-items: center; gap: 8px; max-width: 824px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h3 class="desktop-section-title" style="margin: 0;">Riwayat Transaksi Iuran</h3>
                    <!-- Tombol Cetak PDF Desktop Penghuni Biasa -->
                    <button type="button" onclick="bukaModalCetakIuran()" class="btn-cetak-pdf">
                        <i class="fa-solid fa-file-pdf"></i> Cetak Rekap Iuran
                    </button>
                </div>

                <form method="GET" action="" class="filter-range-form-desktop" style="margin: 0;">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                    <div class="flatpickr-box-wrapper" style="margin: 0;">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>" placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                        <a href="<?= urlIuran($current_page); ?>" class="btn-reset-range" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="desktop-kelola-mutasi-list">
                <?php if (empty($daftar_transaksi)): ?>
                <div class="empty-state-card">
                    <i class="fa-solid fa-folder-open"></i>
                    <span><?= $is_filtered ? 'Tidak ada riwayat transaksi' : 'Belum ada riwayat transaksi iuran'; ?></span>
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
            <p style="color: #b91c1c; font-size: 13px; font-weight: 700; margin-bottom: 10px;"><?= htmlspecialchars($error_modal); ?></p>
            <?php endif; ?>
            <form action="/?page=<?= urlencode($current_page); ?>&modal=tambah_pengeluaran" method="POST" class="modal-form">
                <input type="hidden" name="simpan_pengeluaran" value="1">
                <div class="modal-field">
                    <label class="modal-label" for="keterangan">Keterangan</label>
                    <input type="text" id="keterangan" name="keterangan" class="modal-input" placeholder="Contoh: Pembelian galon & alat kebersihan" required>
                </div>
                <div class="modal-field">
                    <label class="modal-label" for="nominal">Nominal (Rp)</label>
                    <input type="text" id="nominal" name="nominal" class="modal-input format-rupiah-input" placeholder="Contoh: 150.000" inputmode="numeric" required>
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
            <h3 class="modal-title">Atur Nominal & Periode Iuran</h3>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 14px;">Tentukan nama iuran, besaran nominal, dan rentang tanggal berlakunya.</p>
            <?php if (!empty($error_modal)): ?>
            <p style="color: #b91c1c; font-size: 13px; font-weight: 700; margin-bottom: 10px;"><?= htmlspecialchars($error_modal); ?></p>
            <?php endif; ?>
            <form action="/?page=<?= urlencode($current_page); ?>&modal=set_nominal_iuran" method="POST" class="modal-form">
                <input type="hidden" name="simpan_nominal_iuran" value="1">
                <div class="modal-field">
                    <label class="modal-label" for="nama_iuran">Keterangan / Nama Iuran</label>
                    <input type="text" id="nama_iuran" name="nama_iuran" class="modal-input" value="<?= htmlspecialchars($nama_iuran_saat_ini ?? 'Iuran Kebersihan & Keamanan'); ?>" placeholder="Contoh: Iuran Kebersihan & Keamanan" required>
                </div>
                <div class="modal-field">
                    <label class="modal-label" for="nominal_iuran">Nominal Iuran (Rp)</label>
                    <input type="text" id="nominal_iuran" name="nominal_iuran" class="modal-input format-rupiah-input" value="<?= number_format((float)$nominal_iuran_saat_ini, 0, ',', '.'); ?>" placeholder="Contoh: 50.000" inputmode="numeric" required>
                </div>
                <div class="modal-field">
                    <label class="modal-label" for="periode_iuran_range">Rentang Tanggal Berlaku</label>
                    <input type="text" id="periode_iuran_range" name="periode_iuran_range" class="modal-input flatpickr-range-input-modal" value="<?= htmlspecialchars($tgl_mulai_iuran . ' to ' . $tgl_selesai_iuran); ?>" placeholder="Pilih Tanggal Mulai s/d Selesai..." readonly required>
                </div>
                <div class="modal-actions">
                    <a href="/?page=<?= urlencode($current_page); ?>" class="btn-modal-cancel">Batal</a>
                    <button type="submit" class="btn-modal-submit" style="background-color: #2b6cb0;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODAL 3: PILIH PERIODE CETAK BUKU KAS IURAN (SEMUA ROLE) -->
    <div id="modalFilterCetakIuran" class="modal-overlay" onclick="tutupModalCetakIuran()">
        <div class="modal-card" onclick="event.stopPropagation()" style="max-width: 440px;">
            <h3 class="modal-title"><i class="fa-solid fa-file-pdf" style="color: #ef4444;"></i> Cetak Laporan Iuran</h3>
            <p style="font-size: 12.5px; color: #64748b; margin: 4px 0 16px 0;">Pilih cakupan waktu laporan iuran kas bersama yang ingin dicetak.</p>

            <form action="/" method="GET" target="_blank" onsubmit="tutupModalCetakIuran()">
                <input type="hidden" name="page" value="cetak_iuran">

                <div class="modal-field">
                    <label class="modal-label">Cakupan Periode</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px;">
                        <label class="alasan-option-label" style="padding: 8px; font-size: 11.5px; justify-content: center;">
                            <input type="radio" name="periode" value="bulanan" checked onchange="toggleFormCetakIuran('bulanan')">
                            <span>Bulanan</span>
                        </label>
                        <label class="alasan-option-label" style="padding: 8px; font-size: 11.5px; justify-content: center;">
                            <input type="radio" name="periode" value="tahunan" onchange="toggleFormCetakIuran('tahunan')">
                            <span>Tahunan</span>
                        </label>
                        <label class="alasan-option-label" style="padding: 8px; font-size: 11.5px; justify-content: center;">
                            <input type="radio" name="periode" value="semua" onchange="toggleFormCetakIuran('semua')">
                            <span>Semua</span>
                        </label>
                    </div>
                </div>

                <div class="modal-field" id="boxIuranBulan">
                    <label class="modal-label" for="selectBulanIuran">Pilih Bulan</label>
                    <select name="bulan" id="selectBulanIuran" class="modal-input">
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

                <div class="modal-field" id="boxIuranTahun">
                    <label class="modal-label" for="selectTahunIuran">Pilih Tahun</label>
                    <select name="tahun" id="selectTahunIuran" class="modal-input">
                        <?php
                        $tahun_skrg = (int)date('Y');
                        for ($y = $tahun_skrg; $y >= $tahun_skrg - 5; $y--): ?>
                            <option value="<?= $y; ?>" <?= ($y === $tahun_skrg) ? 'selected' : ''; ?>><?= $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="modal-actions" style="margin-top: 20px;">
                    <button type="button" class="btn-modal-cancel" onclick="tutupModalCetakIuran()">Batal</button>
                    <button type="submit" class="btn-modal-submit" style="background-color: #0f766e;">Buka & Cetak PDF</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
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
            altFormat: "d/m/Y"
        });
    });

    document.querySelectorAll('.format-rupiah-input').forEach(function(input) {
        input.addEventListener('input', function() {
            let angka = this.value.replace(/[^0-9]/g, '');
            this.value = angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        });
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
        if (tipe === 'bulanan') {
            boxBulan.style.display = 'block';
            boxTahun.style.display = 'block';
        } else if (tipe === 'tahunan') {
            boxBulan.style.display = 'none';
            boxTahun.style.display = 'block';
        } else {
            boxBulan.style.display = 'none';
            boxTahun.style.display = 'none';
        }
    }
    </script>
</body>

</html>