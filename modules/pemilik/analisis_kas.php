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

    <!-- Flatpickr CSS untuk Kalender Range -->
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

                <!-- Form Mobile -->
                <form method="GET" action="" id="formFilterMobile" class="filter-range-form">
                    <input type="hidden" name="page" value="arus_kas">
                    <div class="flatpickr-box-wrapper">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                            placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                        <a href="/?page=arus_kas" class="btn-reset-range" title="Reset"><i
                                class="fa-solid fa-rotate-left"></i></a>
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
                                <span class="kas-item-date"><?= htmlspecialchars($tx['tanggal']); ?></span>
                            </div>
                        </div>
                        <span class="desktop-mutasi-badge-green">
                            Rp. <?= htmlspecialchars($tx['nominal']); ?>
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
                    <div class="empty-state-card"
                        style="background: #ffffff; padding: 24px; border-radius: 16px; text-align: center; color: #4a5568; margin-top: 12px; box-shadow: 2px 3px 0px #c8924b;">
                        <i class="fa-regular fa-folder-open"
                            style="font-size: 28px; margin-bottom: 8px; color: #4ca5b0;"></i>
                        <p style="margin: 0; font-size: 13.5px; font-weight: 700;">
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
                                <?= number_format((float)$tx['nominal'], 0, ',', '.'); ?></span>
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

            <div class="desktop-kelola-stats" style="grid-template-columns: minmax(280px, 340px);">
                <div class="desktop-kelola-stat-card">
                    <span class="desktop-kelola-stat-label">Total Pemasukan</span>
                    <span class="desktop-kelola-stat-val">Rp.
                        <?= number_format($total_pemasukan, 0, ',', '.'); ?></span>
                </div>
            </div>

            <!-- Header Riwayat Arus Kas & Form Range Sejajar -->
            <div class="desktop-range-filter-wrapper">
                <h3 class="desktop-section-title" style="margin: 0;">Riwayat Arus Kas</h3>

                <!-- Form Desktop -->
                <form method="GET" action="" id="formFilterDesktop" class="filter-range-form-desktop">
                    <input type="hidden" name="page" value="arus_kas">
                    <div class="flatpickr-box-wrapper">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                            placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                        <a href="/?page=arus_kas" class="btn-reset-range" title="Reset Filter">
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
                <div class="empty-state-card"
                    style="background: #ffffff; padding: 32px; border-radius: 20px; text-align: center; color: #4a5568; margin-top: 14px; box-shadow: 4px 6px 0px #c8924b; max-width: 820px;">
                    <i class="fa-regular fa-folder-open"
                        style="font-size: 32px; margin-bottom: 10px; color: #4ca5b0;"></i>
                    <p style="margin: 0; font-size: 14px; font-weight: 700;">
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
                            <span class="badge-kamar-putih">Kamar
                                <?= htmlspecialchars($p['kamar']); ?>
                            </span>
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

    <!-- Script Flatpickr -->
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
    </script>
</body>

</html>