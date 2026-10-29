<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_pj = $_SESSION['user']['is_pj'] ?? true;
$nama_penghuni = $_SESSION['user']['nama'] ?? "Mas Dafa";

$daftar_tagihan_saya = [
    [
        'id'          => 1,
        'jenis'       => 'Sewa',
        'nominal'     => 1000000,
        'jatuh_tempo' => '11-10-26'
    ],
    [
        'id'          => 2,
        'jenis'       => 'Iuran',
        'nominal'     => 50000,
        'jatuh_tempo' => '11-10-26'
    ]
];

$daftar_riwayat = [
    [
        'id'             => 101,
        'judul'          => 'Sewa Bulanan',
        'tanggal'        => '05 September 2026',
        'tanggal_filter' => '2026-09-05',
        'nominal'        => 1200000,
        'nomor_invoice'  => 'INV/202609/KOS-101',
        'tanggal_terbit' => '01 September 2026',
        'jatuh_tempo'    => '11 September 2026',
        'tanggal_bayar'  => '05 September 2026, 10:15 WIB',
        'periode_sewa'   => '11 Sep 2026 - 10 Okt 2026'
    ],
    [
        'id'             => 201,
        'judul'          => 'Iuran Bulanan',
        'tanggal'        => '06 September 2026',
        'tanggal_filter' => '2026-09-06',
        'nominal'        => 50000,
        'nomor_invoice'  => 'INV/202609/KOS-102',
        'tanggal_terbit' => '01 September 2026',
        'jatuh_tempo'    => '11 September 2026',
        'tanggal_bayar'  => '06 September 2026, 14:30 WIB',
        'periode_sewa'   => 'Pelunasan Tambahan'
    ],
    [
        'id'             => 103,
        'judul'          => 'Sewa Bulanan',
        'tanggal'        => '10 September 2026',
        'tanggal_filter' => '2026-09-10',
        'nominal'        => 100000,
        'nomor_invoice'  => 'INV/202609/KOS-103',
        'tanggal_terbit' => '01 September 2026',
        'jatuh_tempo'    => '11 September 2026',
        'tanggal_bayar'  => '10 September 2026, 19:00 WIB',
        'periode_sewa'   => 'Pelunasan Tambahan'
    ]
];

// FILTER RENTANG TANGGAL UNTUK RIWAYAT
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

// Proses filter array riwayat transaksi
$daftar_riwayat = array_filter($daftar_riwayat, function ($r) use ($tgl_mulai, $tgl_selesai) {
    if ($tgl_mulai === '') {
        return true;
    }
    return ($r['tanggal_filter'] >= $tgl_mulai && $r['tanggal_filter'] <= $tgl_selesai);
});

$is_filtered = ($date_range !== '');

// Deteksi Modal Bayar via Query Parameter URL
$modal_bayar_id = isset($_GET['bayar_id']) ? (int)$_GET['bayar_id'] : null;
$tagihan_dipilih = null;

if ($modal_bayar_id) {
    foreach ($daftar_tagihan_saya as $item) {
        if ($item['id'] === $modal_bayar_id) {
            $tagihan_dipilih = $item;
            break;
        }
    }
}

// Deteksi Modal Invoice via Query Parameter URL
$modal_invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : null;
$invoice_dipilih = null;

if ($modal_invoice_id) {
    foreach ($daftar_riwayat as $item) {
        if ($item['id'] === $modal_invoice_id) {
            $invoice_dipilih = $item;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Tagihan &amp; Riwayat</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Flatpickr CSS untuk kalender range -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Link CSS Modular -->
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="dashboard-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <main class="page-content">
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title">Tagihan &amp; Riwayat</h2>
                    <a href="/?page=dashboard_penghuni" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <h3 class="section-title-white">Tagihan Saya</h3>
                <div class="section-group">
                    <?php foreach ($daftar_tagihan_saya as $t): ?>
                        <div class="item-tagihan-card">
                            <div class="item-tagihan-header">
                                <h4 class="item-tagihan-name"><?= htmlspecialchars($t['jenis']) ?></h4>
                                <span class="badge-status-krem">Belum bayar</span>
                            </div>
                            <div class="item-tagihan-body">
                                <div class="item-tagihan-left">
                                    <span>Total tagihan</span>
                                    <span>Batas bayar</span>
                                </div>
                                <div class="item-tagihan-right">
                                    <span class="nominal-highlight">Rp <?= number_format($t['nominal'], 0, ',', '.') ?></span>
                                    <span><?= htmlspecialchars($t['jatuh_tempo']) ?></span>
                                </div>
                            </div>
                            <a href="/?page=tagihan_riwayat&bayar_id=<?= $t['id'] ?>" class="btn-card-action">
                                Bayar
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h3 class="section-title-white">Riwayat Transaksi</h3>
                
                <!-- Filter tanggal mobile -->
                <form method="GET" action="" class="filter-range-form" style="margin-bottom: 12px;">
                    <input type="hidden" name="page" value="tagihan_riwayat">
                    <div class="flatpickr-box-wrapper">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                               placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                            <a href="/?page=tagihan_riwayat" class="btn-reset-range" title="Reset">
                                <i class="fa-solid fa-rotate-left"></i> Reset
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="section-group">
                    <?php if (empty($daftar_riwayat)): ?>
                        
                        <div class="empty-state-card">
                            <i class="fa-solid fa-folder-open empty-icon"></i>
                                <span>Tidak ada riwayat transaksi pada rentang tanggal yang dipilih</span>
                            </div>
                        <?php else: ?>
                        <?php foreach ($daftar_riwayat as $r): ?>
                            <div class="item-tagihan-card">
                                <div class="item-tagihan-header">
                                    <h4 class="item-tagihan-name"><?= htmlspecialchars($r['judul']) ?></h4>
                                    <div class="riwayat-status-actions">
                                        <span class="badge-status-lunas">Lunas</span>
                                        <a href="/?page=tagihan_riwayat&invoice_id=<?= $r['id'] ?>" class="btn-invoice-pill">
                                            <i class="fa-solid fa-file-invoice"></i> Invoice
                                        </a>
                                    </div>
                                </div>
                                <div class="item-tagihan-body">
                                    <div class="item-tagihan-left">
                                        <span><?= htmlspecialchars($r['tanggal']) ?></span>
                                    </div>
                                    <div class="item-tagihan-right">
                                        <span class="nominal-highlight">Rp <?= number_format($r['nominal'], 0, ',', '.') ?></span>
                                    </div>
                                </div>
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
            <h2 class="desktop-header-title">Tagihan &amp; Riwayat</h2>
            <p class="tagihan-desktop-subtitle">Ringkasan tagihan dan riwayat pembayaran</p>

            <h3 class="tagihan-section-title">Tagihan Saya</h3>
            <div class="desktop-tagihan-grid">
                <?php foreach ($daftar_tagihan_saya as $t): ?>
                    <div class="item-tagihan-card">
                        <div class="item-tagihan-header">
                            <h4 class="item-tagihan-name"><?= htmlspecialchars($t['jenis']) ?></h4>
                            <span class="badge-status-krem">Belum bayar</span>
                        </div>
                        <div class="item-tagihan-body">
                            <div class="item-tagihan-left">
                                <span>Total tagihan</span>
                                <span>Batas bayar</span>
                            </div>
                            <div class="item-tagihan-right">
                                <span class="nominal-highlight">Rp <?= number_format($t['nominal'], 0, ',', '.') ?></span>
                                <span><?= htmlspecialchars($t['jatuh_tempo']) ?></span>
                            </div>
                        </div>
                        <a href="/?page=tagihan_riwayat&bayar_id=<?= $t['id'] ?>" class="btn-card-action">
                            Bayar
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Header Riwayat Transaksi Sejajar dengan Form Filter Tanggal (Desktop) -->
            <div class="riwayat-header-row">
                <h3 class="tagihan-section-title">Riwayat Transaksi</h3>

                <form method="GET" action="" class="filter-range-form-desktop">
                    <input type="hidden" name="page" value="tagihan_riwayat">
                    <div class="flatpickr-box-wrapper">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                               placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                            <a href="/?page=tagihan_riwayat" class="btn-reset-range" title="Reset Filter">
                                <i class="fa-solid fa-rotate-left"></i> Reset
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="desktop-riwayat-list">
                <?php if (empty($daftar_riwayat)): ?>
                    <div class="empty-state-card">
                        <i class="fa-solid fa-folder-open empty-icon"></i>
                        <span>Tidak ada riwayat transaksi pada rentang tanggal yang dipilih</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($daftar_riwayat as $r): ?>
                        <div class="desktop-riwayat-card">
                            <div class="desktop-riwayat-info">
                                <h4 class="desktop-riwayat-title"><?= htmlspecialchars($r['judul']) ?></h4>
                                <span class="desktop-riwayat-date"><?= htmlspecialchars($r['tanggal']) ?></span>
                            </div>
                            <div class="desktop-riwayat-right">
                                <div class="riwayat-status-actions">
                                    <span class="badge-lunas-pill">Lunas</span>
                                    <a href="/?page=tagihan_riwayat&invoice_id=<?= $r['id'] ?>" class="btn-invoice-pill">
                                        <i class="fa-solid fa-file-invoice"></i> Invoice
                                    </a>
                                </div>
                                <span class="desktop-riwayat-nominal">Rp <?= number_format($r['nominal'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- POP-UP MODAL INVOICE -->
    <?php if ($invoice_dipilih): ?>
        <div class="modal-overlay active">
            <div class="invoice-wrapper">
                <header class="invoice-header-row">
                    <div>
                        <h1 class="invoice-brand-title">SITEMAN - KOS</h1>
                        <p class="invoice-brand-sub">Sistem Manajemen Operasion Kos</p>
                        <p class="invoice-brand-sub">Jl. Semanggi Barat No. 2B, Malang</p>
                    </div>
                    <div class="invoice-tag-box">
                        <h2 class="invoice-main-title">INVOICE SEWA</h2>
                        <p class="invoice-number"><?= htmlspecialchars($invoice_dipilih['nomor_invoice']); ?></p>
                        <span class="badge-invoice-status badge-status-paid"><i class="fa-solid fa-circle-check"></i> Lunas</span>
                    </div>
                </header>

                <section class="invoice-meta-grid">
                    <div>
                        <div class="meta-col-title">Ditagihkan Kepada</div>
                        <h3 class="meta-text-strong"><?= htmlspecialchars($nama_penghuni); ?></h3>
                        <p class="meta-text-muted"><i class="fa-solid fa-door-open"></i> Kamar 01</p>
                        <p class="meta-text-muted"><i class="fa-brands fa-whatsapp"></i> 082764518892</p>
                    </div>
                    <div style="text-align: right;">
                        <div class="meta-col-title">Detail Waktu</div>
                        <p class="meta-text-muted"><strong>Tgl Terbit:</strong> <?= htmlspecialchars($invoice_dipilih['tanggal_terbit']); ?></p>
                        <p class="meta-text-muted"><strong>Jatuh Tempo:</strong> <?= htmlspecialchars($invoice_dipilih['jatuh_tempo']); ?></p>
                        <p class="meta-text-muted"><strong>Periode:</strong> <?= htmlspecialchars($invoice_dipilih['periode_sewa']); ?></p>
                        <p class="meta-text-muted" style="color: #0b5e39;"><strong>Dibayar:</strong> <?= htmlspecialchars($invoice_dipilih['tanggal_bayar']); ?></p>
                    </div>
                </section>

                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Deskripsi Tagihan</th>
                            <th>Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <p class="item-name"><?= htmlspecialchars($invoice_dipilih['judul']); ?></p>
                                <p class="item-desc"><?= htmlspecialchars($invoice_dipilih['periode_sewa']); ?></p>
                            </td>
                            <td>Rp <?= number_format($invoice_dipilih['nominal'], 0, ',', '.'); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="invoice-summary-row">
                    <div class="invoice-summary-box">
                        <span class="summary-total-label">Total Tagihan</span>
                        <span class="summary-total-value">Rp <?= number_format($invoice_dipilih['nominal'], 0, ',', '.'); ?></span>
                    </div>
                </div>

                <div class="invoice-notes-card">
                    <h4><i class="fa-solid fa-building-columns"></i> Petunjuk Pembayaran:</h4>
                    <p>Silakan transfer via <strong>Transfer Bank BCA</strong> ke rekening <strong>8271-9920-112 a/n Bapak Kos</strong>. Pembayaran telah diverifikasi lunas.</p>
                </div>

                <div class="invoice-actions">
                    <a href="/?page=tagihan_riwayat" class="btn-invoice-back">
                        <i class="fa-solid fa-xmark"></i> Tutup
                    </a>
                    <button type="button" onclick="window.print()" class="btn-invoice-print">
                        <i class="fa-solid fa-print"></i> Cetak / Simpan PDF
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- POP-UP MODAL BAYAR TAGIHAN -->
    <?php if ($tagihan_dipilih): ?>
        <div class="modal-overlay active">
            <div class="modal-bayar-card">
                <h3 class="modal-bayar-title">Bayar Tagihan</h3>

                <div class="modal-ringkasan-box">
                    <div class="modal-ringkasan-top">
                        <span class="modal-ringkasan-jenis"><?= htmlspecialchars($tagihan_dipilih['jenis']); ?></span>
                        <span class="modal-ringkasan-nominal">Rp <?= number_format($tagihan_dipilih['nominal'], 0, ',', '.'); ?></span>
                    </div>
                    <span class="modal-ringkasan-batas">Batas bayar <?= htmlspecialchars($tagihan_dipilih['jatuh_tempo']); ?></span>
                </div>

                <form action="/?page=tagihan_riwayat" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 12px; width: 100%;">
                    <input type="hidden" name="tagihan_id" value="<?= $tagihan_dipilih['id']; ?>">

                    <div class="modal-rekening-wrapper">
                        <label class="modal-rekening-label">Transfer Ke Rekening</label>
                        <div class="modal-rekening-val">
                            BCA 12345678 a.n Bapak Kos
                        </div>
                    </div>

                    <div class="modal-upload-wrapper">
                        <label class="modal-rekening-label">Upload Bukti Pembayaran</label>
                        <label class="modal-upload-box">
                            <input type="file" name="bukti_transfer" accept="image/*" required>
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#4ca5b0" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"></path>
                                <polyline points="7 9 12 4 17 9"></polyline>
                                <line x1="12" y1="4" x2="12" y2="16"></line>
                            </svg>
                            <p class="modal-upload-text">Ketuk untuk pilih foto</p>
                        </label>
                    </div>

                    <div class="modal-bayar-actions">
                        <a href="/?page=tagihan_riwayat" class="btn-bayar-batal">Batal</a>
                        <button type="submit" class="btn-bayar-kirim">Kirim Bukti</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Flatpickr JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            flatpickr(".flatpickr-range-input", {
                mode: "range",
                dateFormat: "Y-m-d",
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        instance.element.form.submit();
                    }
                }
            });
        });
    </script>
</body>
</html>