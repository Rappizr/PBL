<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/handler/kas_handlers.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Analisis Kas</title>
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

            <main class="kas-container">
                <div class="kas-banner-card">
                    <h2 class="kas-banner-title">Analisis Kas</h2>
                    <a href="/?page=dashboard_pemilik" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="kas-total-card">
                    <span class="kas-total-label">Total Pemasukan</span>
                    <span class="kas-total-value">Rp. <?= number_format($total_pemasukan, 0, ',', '.'); ?></span>
                </div>

                <div class="kas-cetak-mobile-row">
                    <button type="button" onclick="bukaModalCetakKas()" class="btn-cetak-pdf kas-btn-cetak-full">
                        <i class="fa-solid fa-file-pdf"></i> Cetak PDF
                    </button>
                </div>

                <form method="GET" action="" id="formFilterMobile" class="filter-range-form kas-filter-form-mobile">
                    <input type="hidden" name="page" value="analisis_kas">
                    <div class="flatpickr-box-wrapper">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                            placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                        <a href="/?page=analisis_kas" class="btn-reset-range" title="Reset">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="kas-transaksi-list">
                    <?php if (empty($daftar_transaksi)): ?>
                    <div class="empty-state-card">
                        <i class="fa-regular fa-folder-open"></i>
                        <p>Tidak ada transaksi pada rentang tanggal ini</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($daftar_transaksi as $tx): ?>
                    <div class="kas-transaksi-card">
                        <div class="kas-transaksi-left">
                            <div class="kas-icon-box">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </div>
                            <div class="kas-transaksi-info">
                                <span class="kas-item-title"><?= htmlspecialchars($tx['judul']); ?></span>
                                <span
                                    class="kas-item-date"><?= date('Y-m-d H:i:s', strtotime($tx['tanggal'])); ?></span>
                            </div>
                        </div>
                        <span class="desktop-mutasi-badge-green">
                            Rp. <?= number_format((float)$tx['nominal'], 0, ',', '.'); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="kas-filter-row">
                    <a href="/?page=analisis_kas&filter=semua<?= $is_filtered ? '&date_range=' . urlencode($date_range) : ''; ?>"
                        class="filter-pill <?= $filter_status === 'semua' ? 'active' : 'inactive'; ?>">
                        Semua (<?= $count_semua; ?>)
                    </a>
                    <a href="/?page=analisis_kas&filter=belum_bayar<?= $is_filtered ? '&date_range=' . urlencode($date_range) : ''; ?>"
                        class="filter-pill <?= $filter_status === 'belum_bayar' ? 'active' : 'inactive'; ?>">
                        Belum Bayar (<?= $count_belum; ?>)
                    </a>
                    <a href="/?page=analisis_kas&filter=verifikasi<?= $is_filtered ? '&date_range=' . urlencode($date_range) : ''; ?>"
                        class="filter-pill <?= $filter_status === 'verifikasi' ? 'active' : 'inactive'; ?>">
                        Verifikasi (<?= $count_verifikasi; ?>)
                    </a>
                </div>

                <div class="kas-penghuni-list">
                    <?php if (empty($daftar_penghuni)): ?>
                    <div class="kas-empty-penghuni-mobile">
                        <i class="fa-regular fa-folder-open"></i>
                        <p>
                            <?php 
                                if ($filter_status === 'verifikasi') {
                                    echo 'Tidak ada pembayaran yang menunggu verifikasi';
                                } elseif ($filter_status === 'belum_bayar') {
                                    echo 'Semua penghuni sudah melunasi tagihan';
                                } else {
                                    echo 'Belum ada data penghuni';
                                }
                            ?>
                        </p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($daftar_penghuni as $p): ?>
                    <div class="kas-penghuni-card">
                        <div class="kas-penghuni-info">
                            <div class="kas-penghuni-top">
                                <h4 class="kas-penghuni-name"><?= htmlspecialchars($p['nama']); ?></h4>
                                <span class="badge-kamar-putih"><?= htmlspecialchars($p['kamar']); ?></span>
                            </div>
                            <span class="kas-penghuni-nominal">Rp.
                                <?= number_format((float)$p['nominal'], 0, ',', '.'); ?></span>
                        </div>
                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', $p['no_wa']); ?>?text=Halo%20<?= urlencode($p['nama']); ?>%2C%20ini%20mengenai%20tagihan%20sewa%20kamar."
                            target="_blank" class="btn-chat-wa">
                            <i class="fa-brands fa-whatsapp"></i> Chat
                        </a>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Analisis Kas</h2>
            <p class="desktop-header-subtitle">Laporan arus kas dan status pembayaran penghuni</p>

            <div class="desktop-kelola-stats kas-desktop-single-stat">
                <div class="desktop-kelola-stat-card">
                    <span class="desktop-kelola-stat-label">Total Pemasukan</span>
                    <span class="desktop-kelola-stat-val">Rp.
                        <?= number_format($total_pemasukan, 0, ',', '.'); ?></span>
                </div>
            </div>

            <div class="desktop-range-filter-wrapper">
                <div class="kas-desktop-section-left">
                    <h3 class="desktop-section-title kas-title-no-margin">Riwayat Arus Kas</h3>
                    <button type="button" onclick="bukaModalCetakKas()" class="btn-cetak-pdf">
                        <i class="fa-solid fa-file-pdf"></i> Cetak PDF
                    </button>
                </div>

                <form method="GET" action="" id="formFilterDesktop" class="filter-range-form-desktop">
                    <input type="hidden" name="page" value="analisis_kas">
                    <div class="flatpickr-box-wrapper">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                            placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                        <a href="/?page=analisis_kas" class="btn-reset-range" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="desktop-kelola-mutasi-list">
                <?php if (empty($daftar_transaksi)): ?>
                <div class="empty-state-card">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>Tidak ada transaksi arus kas pada rentang tanggal yang dipilih</p>
                </div>
                <?php else: ?>
                <?php foreach ($daftar_transaksi as $tx): ?>
                <div class="desktop-mutasi-card">
                    <div class="desktop-mutasi-left">
                        <div class="desktop-mutasi-icon"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
                        <div class="desktop-mutasi-info">
                            <h4><?= htmlspecialchars($tx['judul']); ?></h4>
                            <span><?= date('Y-m-d H:i:s', strtotime($tx['tanggal'])); ?></span>
                        </div>
                    </div>
                    <span class="desktop-mutasi-badge-green">
                        Rp. <?= number_format((float)$tx['nominal'], 0, ',', '.'); ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="desktop-kelola-filter-row">
                <a href="/?page=analisis_kas&filter=semua<?= $is_filtered ? '&date_range=' . urlencode($date_range) : ''; ?>"
                    class="filter-pill <?= $filter_status === 'semua' ? 'active' : 'inactive'; ?>">
                    Semua (<?= $count_semua; ?>)
                </a>
                <a href="/?page=analisis_kas&filter=belum_bayar<?= $is_filtered ? '&date_range=' . urlencode($date_range) : ''; ?>"
                    class="filter-pill <?= $filter_status === 'belum_bayar' ? 'active' : 'inactive'; ?>">
                    Belum Bayar (<?= $count_belum; ?>)
                </a>
                <a href="/?page=analisis_kas&filter=verifikasi<?= $is_filtered ? '&date_range=' . urlencode($date_range) : ''; ?>"
                    class="filter-pill <?= $filter_status === 'verifikasi' ? 'active' : 'inactive'; ?>">
                    Verifikasi (<?= $count_verifikasi; ?>)
                </a>
            </div>

            <div class="desktop-penghuni-list">
                <?php if (empty($daftar_penghuni)): ?>
                <div class="kas-empty-penghuni-desktop">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>
                        <?php 
                            if ($filter_status === 'verifikasi') {
                                echo 'Tidak ada pembayaran yang menunggu verifikasi';
                            } elseif ($filter_status === 'belum_bayar') {
                                echo 'Semua penghuni sudah melunasi tagihan';
                            } else {
                                echo 'Belum ada data penghuni';
                            }
                        ?>
                    </p>
                </div>
                <?php else: ?>
                <?php foreach ($daftar_penghuni as $p): ?>
                <div class="desktop-penghuni-card">
                    <div class="desktop-penghuni-info">
                        <div class="desktop-penghuni-top">
                            <h4><?= htmlspecialchars($p['nama']); ?></h4>
                            <span class="badge-kamar-putih">Kamar <?= htmlspecialchars($p['kamar']); ?></span>
                        </div>
                        <span class="desktop-penghuni-nominal">Rp.
                            <?= number_format((float)$p['nominal'], 0, ',', '.'); ?></span>
                    </div>
                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', $p['no_wa']); ?>?text=Halo%20<?= urlencode($p['nama']); ?>%2C%20ini%20mengenai%20tagihan%20sewa%20kamar."
                        target="_blank" class="btn-chat-wa">
                        <i class="fa-brands fa-whatsapp"></i> Chat
                    </a>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- MODAL PILIH PERIODE CETAK KAS (PEMILIK) -->
    <div id="modalFilterCetakKas" class="modal-overlay" onclick="tutupModalCetakKas()">
        <div class="modal-card modal-cetak-kas-card" onclick="event.stopPropagation()">
            <h3 class="modal-title"><i class="fa-solid fa-file-pdf icon-pdf-red"></i> Cetak Laporan Arus Kas</h3>
            <p class="modal-cetak-kas-sub">Pilih cakupan waktu laporan arus kas sewa yang ingin dicetak.</p>

            <form action="/" method="GET" target="_blank" onsubmit="tutupModalCetakKas()">
                <input type="hidden" name="page" value="cetak_kas">

                <div class="modal-field">
                    <label class="modal-label">Cakupan Periode</label>
                    <div class="periode-radio-grid">
                        <label class="alasan-option-label periode-radio-option">
                            <input type="radio" name="periode" value="bulanan" checked
                                onchange="toggleFormCetakKas('bulanan')">
                            <span>Bulanan</span>
                        </label>
                        <label class="alasan-option-label periode-radio-option">
                            <input type="radio" name="periode" value="tahunan" onchange="toggleFormCetakKas('tahunan')">
                            <span>Tahunan</span>
                        </label>
                        <label class="alasan-option-label periode-radio-option">
                            <input type="radio" name="periode" value="semua" onchange="toggleFormCetakKas('semua')">
                            <span>Semua</span>
                        </label>
                    </div>
                </div>

                <div class="modal-field" id="boxKasBulan">
                    <label class="modal-label" for="selectBulanKas">Pilih Bulan</label>
                    <select name="bulan" id="selectBulanKas" class="modal-input">
                        <?php
                        $bulan_list = [
                            1=>'Januari', 2=>'Februari', 3=>'Maret', 4=>'April',
                            5=>'Mei', 6=>'Juni', 7=>'Juli', 8=>'Agustus',
                            9=>'September', 10=>'Oktober', 11=>'November', 12=>'Desember'
                        ];
                        $current_m = (int)date('n');
                        foreach ($bulan_list as $num => $nm): ?>
                        <option value="<?= $num; ?>" <?= ($num === $current_m) ? 'selected' : ''; ?>><?= $nm; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-field" id="boxKasTahun">
                    <label class="modal-label" for="selectTahunKas">Pilih Tahun</label>
                    <select name="tahun" id="selectTahunKas" class="modal-input">
                        <?php
                        $tahun_skrg = (int)date('Y');
                        for ($y = $tahun_skrg; $y >= $tahun_skrg - 5; $y--): ?>
                        <option value="<?= $y; ?>" <?= ($y === $tahun_skrg) ? 'selected' : ''; ?>><?= $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="modal-actions modal-cetak-actions">
                    <button type="button" class="btn-modal-cancel" onclick="tutupModalCetakKas()">Batal</button>
                    <button type="submit" class="btn-modal-submit btn-cetak-submit-teal">Buka & Cetak PDF</button>
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

    function bukaModalCetakKas() {
        const modal = document.getElementById('modalFilterCetakKas');
        if (modal) modal.classList.add('active');
    }

    function tutupModalCetakKas() {
        const modal = document.getElementById('modalFilterCetakKas');
        if (modal) modal.classList.remove('active');
    }

    function toggleFormCetakKas(tipe) {
        const boxBulan = document.getElementById('boxKasBulan');
        const boxTahun = document.getElementById('boxKasTahun');
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