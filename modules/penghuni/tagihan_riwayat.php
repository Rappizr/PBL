<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Load Koneksi Database
global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $possible_paths = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../config/database.php',
        __DIR__ . '/../../../config/database.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/database.php'
    ];
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            if (isset($pdo) && ($pdo instanceof PDO)) break;
        }
    }
}

// 2. Load File Handler
$handler_paths = [
    __DIR__ . '/handler/tagihan_handler.php',
    __DIR__ . '/handler/tagihan_riwayat_handlers.php',
    __DIR__ . '/handlers/tagihan_riwayat_handlers.php'
];

foreach ($handler_paths as $hp) {
    if (file_exists($hp)) {
        require_once $hp;
        break;
    }
}

// 3. Eksekusi Fungsi Handler
$nama_penghuni = $_SESSION['user']['nama'] ?? ($_SESSION['nama'] ?? 'Penghuni');
$pk_id         = function_exists('resolvePenghuniKamarId') ? resolvePenghuniKamarId($pdo, $_SESSION['user'] ?? []) : (int)($_SESSION['user']['penghuni_kamar_id'] ?? 0);

$post_res      = function_exists('handleUploadBuktiBayar') ? handleUploadBuktiBayar($pdo) : ['sukses' => '', 'error' => ''];
$sukses_pesan  = $post_res['sukses'] ?? ($sukses_pesan ?? ($_SESSION['pesan_sukses'] ?? ''));
$error_pesan   = $post_res['error'] ?? ($error_pesan ?? ($_SESSION['pesan_error'] ?? ''));
unset($_SESSION['pesan_sukses'], $_SESSION['pesan_error']);

$daftar_tagihan_saya = function_exists('fetchDaftarTagihan') ? fetchDaftarTagihan($pdo, $pk_id) : ($daftar_tagihan_saya ?? []);
$semua_riwayat       = function_exists('fetchRiwayatTransaksi') ? fetchRiwayatTransaksi($pdo, $pk_id) : ($semua_riwayat ?? []);

// 4. Filter Range Tanggal
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

$daftar_riwayat = array_filter($semua_riwayat, function ($r) use ($tgl_mulai, $tgl_selesai) {
    if ($tgl_mulai === '') return true;
    return ($r['tanggal_filter'] >= $tgl_mulai && $r['tanggal_filter'] <= $tgl_selesai);
});
$is_filtered = ($date_range !== '');

// Pagination Riwayat Transaksi (Desktop: 10, Mobile: 5)
$total_riwayat = count($daftar_riwayat);

$limit_desktop  = 1;
$page_desktop   = isset($_GET['hal']) ? max(1, (int)$_GET['hal']) : 1;
$total_hal_desk = max(1, (int)ceil($total_riwayat / $limit_desktop));
if ($page_desktop > $total_hal_desk) $page_desktop = $total_hal_desk;
$riwayat_desktop = array_slice($daftar_riwayat, ($page_desktop - 1) * $limit_desktop, $limit_desktop);

$limit_mobile   = 1;
$page_mobile    = isset($_GET['hal_m']) ? max(1, (int)$_GET['hal_m']) : 1;
$total_hal_mob  = max(1, (int)ceil($total_riwayat / $limit_mobile));
if ($page_mobile > $total_hal_mob) $page_mobile = $total_hal_mob;
$riwayat_mobile  = array_slice($daftar_riwayat, ($page_mobile - 1) * $limit_mobile, $limit_mobile);

$query_tanggal = $is_filtered ? '&date_range=' . urlencode($date_range) : '';

// 5. Handling Modal Data
$tagihan_dipilih = null;
if (isset($_GET['bayar_id'])) {
    $bid = (int)$_GET['bayar_id'];
    foreach ($daftar_tagihan_saya as $item) {
        if ((int)$item['id'] === $bid) {
            $tagihan_dipilih = $item;
            break;
        }
    }
}

$invoice_dipilih = null;
if (isset($_GET['invoice_id'])) {
    $iid = (int)$_GET['invoice_id'];
    foreach ($daftar_riwayat as $item) {
        if ((int)$item['id'] === $iid) {
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>

<body class="dashboard-body">

    <!-- FLOATING TOAST NOTIFIKASI BERHASIL / GAGAL -->
    <div class="toast-floating-container">
        <?php if (!empty($sukses_pesan)): ?>
        <div class="toast-alert toast-success" id="toastAlertBox">
            <div class="toast-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            <div class="toast-content">
                <h5 class="toast-title">Berhasil Terkirim</h5>
                <p class="toast-message"><?= htmlspecialchars($sukses_pesan); ?></p>
            </div>
            <button type="button" class="toast-close" onclick="closeToast()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>

        <?php if (!empty($error_pesan)): ?>
        <div class="toast-alert toast-error" id="toastAlertBox">
            <div class="toast-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="toast-content">
                <h5 class="toast-title">Gagal Mengirim</h5>
                <p class="toast-message"><?= htmlspecialchars($error_pesan); ?></p>
            </div>
            <button type="button" class="toast-close" onclick="closeToast()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>
    </div>

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
                    <?php if (empty($daftar_tagihan_saya)): ?>
                    <div class="empty-state-card">
                        <i class="fa-solid fa-circle-check empty-icon"></i>
                        <span>Tidak ada tagihan yang belum dibayar saat ini.</span>
                    </div>
                    <?php else: ?>
                    <?php foreach ($daftar_tagihan_saya as $t): ?>
                    <?php
                            $is_lunas   = ($t['status_tagihan'] === 'lunas');
                            $is_pending = (($t['status_verif'] ?? '') === 'pending' || $t['status_tagihan'] === 'sebagian');
                            $is_ditolak = (($t['status_verif'] ?? '') === 'ditolak');
                            ?>
                    <div class="item-tagihan-card <?= $is_ditolak ? 'tagihan-ditolak' : ''; ?>">
                        <div class="item-tagihan-header">
                            <h4 class="item-tagihan-name"><?= htmlspecialchars($t['jenis']); ?></h4>
                            <?php if ($is_lunas): ?>
                            <span class="badge-tagihan-lunas">Lunas</span>
                            <?php elseif ($is_pending): ?>
                            <span class="badge-tagihan-pending">Menunggu Verifikasi</span>
                            <?php elseif ($is_ditolak): ?>
                            <span class="badge-tagihan-pending badge-tagihan-ditolak">Ditolak</span>
                            <?php else: ?>
                            <span class="badge-tagihan-belum">Belum bayar</span>
                            <?php endif; ?>
                        </div>
                        <div class="item-tagihan-body">
                            <div class="item-tagihan-left">
                                <span>Total tagihan</span>
                                <span>Batas bayar</span>
                            </div>
                            <div class="item-tagihan-right">
                                <span class="nominal-highlight">Rp
                                    <?= number_format($t['nominal'], 0, ',', '.'); ?></span>
                                <span><?= htmlspecialchars($t['jatuh_tempo']); ?></span>
                            </div>
                        </div>

                        <?php if ($is_ditolak && !empty($t['alasan_penolakan'])): ?>
                        <div class="box-alasan-ditolak">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span><strong>Ditolak:</strong> <?= htmlspecialchars($t['alasan_penolakan']); ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($is_lunas): ?>
                        <div class="btn-card-action btn-card-lunas"><i class="fa-solid fa-circle-check"></i> Lunas</div>
                        <?php elseif ($is_pending): ?>
                        <div class="btn-card-action btn-card-pending"><i class="fa-solid fa-clock"></i> Diproses</div>
                        <?php elseif ($is_ditolak): ?>
                        <a href="/?page=tagihan_riwayat&bayar_id=<?= (int)$t['id']; ?>"
                            class="btn-card-action btn-card-upload-ulang">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Ulang Bukti
                        </a>
                        <?php else: ?>
                        <a href="/?page=tagihan_riwayat&bayar_id=<?= (int)$t['id']; ?>"
                            class="btn-card-action">Bayar</a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h3 class="section-title-white">Riwayat Transaksi</h3>
                <form method="GET" action="" class="filter-range-form">
                    <input type="hidden" name="page" value="tagihan_riwayat">
                    <div class="flatpickr-box-wrapper">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                            placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                        <a href="/?page=tagihan_riwayat" class="btn-reset-range"><i class="fa-solid fa-rotate-left"></i>
                            Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="section-group">
                    <?php if (empty($daftar_riwayat)): ?>
                    <div class="empty-state-card">
                        <i class="fa-solid fa-folder-open empty-icon"></i>
                        <span>Belum ada riwayat transaksi yang disetujui.</span>
                    </div>
                    <?php else: ?>
                    <?php foreach ($riwayat_mobile as $r): ?>
                    <div class="item-tagihan-card">
                        <div class="item-tagihan-header">
                            <h4 class="item-tagihan-name"><?= htmlspecialchars($r['judul']); ?></h4>
                            <div class="riwayat-status-actions">
                                <span class="badge-status-lunas">Lunas</span>
                                <a href="/?page=tagihan_riwayat&hal_m=<?= $page_mobile; ?><?= $query_tanggal; ?>&invoice_id=<?= $r['id']; ?>"
                                    class="btn-invoice-pill">
                                    <i class="fa-solid fa-file-invoice"></i> Invoice
                                </a>
                            </div>
                        </div>
                        <div class="item-tagihan-body">
                            <div class="item-tagihan-left"><span><?= htmlspecialchars($r['tanggal']); ?></span></div>
                            <div class="item-tagihan-right"><span class="nominal-highlight">Rp
                                    <?= number_format($r['nominal'], 0, ',', '.'); ?></span></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if ($total_hal_mob > 1): ?>
                    <div class="verif-pagination-wrapper verif-pagination-mobile">
                        <div class="verif-page-controls">
                            <?php if ($page_mobile > 1): ?>
                            <a href="/?page=tagihan_riwayat&hal_m=<?= $page_mobile - 1; ?><?= $query_tanggal; ?>"
                                class="verif-page-btn" title="Sebelumnya">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                            <?php else: ?>
                            <button type="button" class="verif-page-btn" disabled><i
                                    class="fa-solid fa-chevron-left"></i></button>
                            <?php endif; ?>

                            <span class="verif-page-badge"> <?= $page_mobile; ?> / <?= $total_hal_mob; ?></span>

                            <?php if ($page_mobile < $total_hal_mob): ?>
                            <a href="/?page=tagihan_riwayat&hal_m=<?= $page_mobile + 1; ?><?= $query_tanggal; ?>"
                                class="verif-page-btn" title="Berikutnya">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                            <?php else: ?>
                            <button type="button" class="verif-page-btn" disabled><i
                                    class="fa-solid fa-chevron-right"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
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
                <?php if (empty($daftar_tagihan_saya)): ?>
                <div class="empty-state-card" style="grid-column: 1 / -1;">
                    <i class="fa-solid fa-circle-check empty-icon"></i>
                    <span>Tidak ada tagihan yang belum dibayar saat ini.</span>
                </div>
                <?php else: ?>
                <?php foreach ($daftar_tagihan_saya as $t): ?>
                <?php
                        $is_lunas   = ($t['status_tagihan'] === 'lunas');
                        $is_pending = (($t['status_verif'] ?? '') === 'pending' || $t['status_tagihan'] === 'sebagian');
                        $is_ditolak = (($t['status_verif'] ?? '') === 'ditolak');
                        ?>
                <div class="item-tagihan-card <?= $is_ditolak ? 'tagihan-ditolak' : ''; ?>">
                    <div class="item-tagihan-header">
                        <h4 class="item-tagihan-name"><?= htmlspecialchars($t['jenis']); ?></h4>
                        <?php if ($is_lunas): ?>
                        <span class="badge-tagihan-lunas">Lunas</span>
                        <?php elseif ($is_pending): ?>
                        <span class="badge-tagihan-pending">Menunggu Verifikasi</span>
                        <?php elseif ($is_ditolak): ?>
                        <span class="badge-tagihan-pending badge-tagihan-ditolak">Ditolak</span>
                        <?php else: ?>
                        <span class="badge-tagihan-belum">Belum bayar</span>
                        <?php endif; ?>
                    </div>
                    <div class="item-tagihan-body">
                        <div class="item-tagihan-left">
                            <span>Total tagihan</span>
                            <span>Batas bayar</span>
                        </div>
                        <div class="item-tagihan-right">
                            <span class="nominal-highlight">Rp <?= number_format($t['nominal'], 0, ',', '.'); ?></span>
                            <span><?= htmlspecialchars($t['jatuh_tempo']); ?></span>
                        </div>
                    </div>

                    <?php if ($is_ditolak && !empty($t['alasan_penolakan'])): ?>
                    <div class="box-alasan-ditolak">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><strong>Ditolak:</strong> <?= htmlspecialchars($t['alasan_penolakan']); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($is_lunas): ?>
                    <div class="btn-card-action btn-card-lunas"><i class="fa-solid fa-circle-check"></i> Lunas</div>
                    <?php elseif ($is_pending): ?>
                    <div class="btn-card-action btn-card-pending"><i class="fa-solid fa-clock"></i> Diproses</div>
                    <?php elseif ($is_ditolak): ?>
                    <a href="/?page=tagihan_riwayat&bayar_id=<?= (int)$t['id']; ?>"
                        class="btn-card-action btn-card-upload-ulang">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload Ulang Bukti
                    </a>
                    <?php else: ?>
                    <a href="/?page=tagihan_riwayat&bayar_id=<?= (int)$t['id']; ?>" class="btn-card-action">Bayar</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="riwayat-header-row">
                <h3 class="tagihan-section-title">Riwayat Transaksi</h3>
                <form method="GET" action="" class="filter-range-form-desktop">
                    <input type="hidden" name="page" value="tagihan_riwayat">
                    <div class="flatpickr-box-wrapper">
                        <i class="fa-regular fa-calendar date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                            placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                        <a href="/?page=tagihan_riwayat" class="btn-reset-range"><i class="fa-solid fa-rotate-left"></i>
                            Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="desktop-riwayat-list">
                <?php if (empty($daftar_riwayat)): ?>
                <div class="empty-state-card">
                    <i class="fa-solid fa-folder-open empty-icon"></i>
                    <span>Belum ada riwayat transaksi yang disetujui.</span>
                </div>
                <?php else: ?>
                <?php foreach ($riwayat_desktop as $r): ?>
                <div class="desktop-riwayat-card">
                    <div class="desktop-riwayat-info">
                        <h4 class="desktop-riwayat-title"><?= htmlspecialchars($r['judul']); ?></h4>
                        <span class="desktop-riwayat-date"><?= htmlspecialchars($r['tanggal']); ?></span>
                    </div>
                    <div class="desktop-riwayat-right">
                        <div class="riwayat-status-actions">
                            <span class="badge-lunas-pill">Lunas</span>
                            <a href="/?page=tagihan_riwayat&hal=<?= $page_desktop; ?><?= $query_tanggal; ?>&invoice_id=<?= $r['id']; ?>"
                                class="btn-invoice-pill">
                                <i class="fa-solid fa-file-invoice"></i> Invoice
                            </a>
                        </div>
                        <span class="desktop-riwayat-nominal">Rp
                            <?= number_format($r['nominal'], 0, ',', '.'); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($total_hal_desk > 1): ?>
            <div class="verif-pagination-wrapper" style="max-width: 784px;">
                <div class="verif-page-controls">
                    <?php if ($page_desktop > 1): ?>
                    <a href="/?page=tagihan_riwayat&hal=<?= $page_desktop - 1; ?><?= $query_tanggal; ?>"
                        class="verif-page-btn" title="Sebelumnya">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                    <?php else: ?>
                    <button type="button" class="verif-page-btn" disabled><i
                            class="fa-solid fa-chevron-left"></i></button>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_hal_desk; $i++): ?>
                    <a href="/?page=tagihan_riwayat&hal=<?= $i; ?><?= $query_tanggal; ?>"
                        class="verif-page-num <?= ($i === $page_desktop) ? 'active' : ''; ?>">
                        <?= $i; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($page_desktop < $total_hal_desk): ?>
                    <a href="/?page=tagihan_riwayat&hal=<?= $page_desktop + 1; ?><?= $query_tanggal; ?>"
                        class="verif-page-btn" title="Berikutnya">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    <?php else: ?>
                    <button type="button" class="verif-page-btn" disabled><i
                            class="fa-solid fa-chevron-right"></i></button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- MODAL INVOICE -->
    <?php if ($invoice_dipilih): ?>
    <div class="modal-overlay active">
        <div class="invoice-wrapper">
            <header class="invoice-header-row">
                <div>
                    <h1 class="invoice-brand-title">SITEMAN - KOS</h1>
                    <p class="invoice-brand-sub">Sistem Manajemen Operasional Kos</p>
                </div>
                <div class="invoice-tag-box">
                    <?php $is_sewa = (stripos($invoice_dipilih['judul'], 'sewa') !== false); ?>
                    <h2 class="invoice-main-title"><?= $is_sewa ? 'INVOICE SEWA' : 'INVOICE IURAN'; ?></h2>
                    <p class="invoice-number"><?= htmlspecialchars($invoice_dipilih['nomor_invoice']); ?></p>
                    <span class="badge-invoice-status badge-status-paid"><i class="fa-solid fa-circle-check"></i>
                        Lunas</span>
                </div>
            </header>

            <section class="invoice-meta-grid">
                <div>
                    <div class="meta-col-title">Ditagihkan Kepada</div>
                    <h3 class="meta-text-strong"><?= htmlspecialchars($nama_penghuni); ?></h3>
                    <p class="meta-text-muted"><i class="fa-solid fa-door-open"></i> Kamar
                        <?= htmlspecialchars($invoice_dipilih['no_kamar']); ?></p>
                </div>
                <div class="text-right">
                    <div class="meta-col-title">Detail Waktu</div>
                    <p class="meta-text-muted"><strong>Tgl Terbit:</strong>
                        <?= htmlspecialchars($invoice_dipilih['tanggal_terbit']); ?></p>
                    <p class="meta-text-muted"><strong>Jatuh Tempo:</strong>
                        <?= htmlspecialchars($invoice_dipilih['jatuh_tempo']); ?></p>
                    <p class="meta-text-muted"><strong>Periode:</strong>
                        <?= htmlspecialchars($invoice_dipilih['periode_sewa']); ?></p>
                    <p class="meta-text-muted"><strong>Dibayar:</strong>
                        <?= htmlspecialchars($invoice_dipilih['tanggal_bayar']); ?></p>
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
                    <span class="summary-total-value">Rp
                        <?= number_format($invoice_dipilih['nominal'], 0, ',', '.'); ?></span>
                </div>
            </div>

            <div class="invoice-actions">
                <a href="/?page=tagihan_riwayat&hal=<?= $page_desktop; ?>&hal_m=<?= $page_mobile; ?><?= $query_tanggal; ?>"
                    class="btn-invoice-back"><i class="fa-solid fa-xmark"></i> Tutup</a>
                <button type="button" onclick="window.print()" class="btn-invoice-print"><i
                        class="fa-solid fa-print"></i> Cetak / Simpan PDF</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- MODAL BAYAR (PILIHAN METODE TRANSFER ATAU TUNAI) -->
    <?php if ($tagihan_dipilih): ?>
    <div class="modal-overlay active">
        <div class="modal-bayar-card">
            <h3 class="modal-bayar-title">Bayar Tagihan</h3>
            <div class="modal-ringkasan-box">
                <div class="modal-ringkasan-top">
                    <span class="modal-ringkasan-jenis"><?= htmlspecialchars($tagihan_dipilih['jenis']); ?></span>
                    <span class="modal-ringkasan-nominal">Rp
                        <?= number_format($tagihan_dipilih['nominal'], 0, ',', '.'); ?></span>
                </div>
                <span class="modal-ringkasan-batas">Batas bayar
                    <?= htmlspecialchars($tagihan_dipilih['jatuh_tempo']); ?></span>
            </div>

            <form action="/?page=tagihan_riwayat" method="POST" enctype="multipart/form-data"
                class="modal-form-upload-tagihan" onsubmit="handleKirimBuktiSubmit(event)">
                <input type="hidden" name="tagihan_id" value="<?= $tagihan_dipilih['id']; ?>">

                <!-- PILIHAN METODE PEMBAYARAN -->
                <div class="metode-bayar-group">
                    <label class="modal-rekening-label">Metode Pembayaran</label>
                    <div class="metode-bayar-grid">
                        <label class="metode-bayar-option">
                            <input type="radio" name="metode_bayar" value="transfer" checked
                                onchange="toggleMetodeBayar('transfer')" class="metode-radio-input">
                            <span><i class="fa-solid fa-building-columns"></i> Transfer</span>
                        </label>
                        <label class="metode-bayar-option">
                            <input type="radio" name="metode_bayar" value="tunai" onchange="toggleMetodeBayar('tunai')"
                                class="metode-radio-input">
                            <span><i class="fa-solid fa-money-bill-wave"></i> Tunai (Cash)</span>
                        </label>
                    </div>
                </div>

                <!-- BLOK TRANSFER (REKENING + UPLOAD BUKTI) -->
                <div id="blokMetodeTransfer" class="blok-metode-transfer">
                    <div class="modal-rekening-wrapper">
                        <label class="modal-rekening-label">Transfer Ke Rekening</label>
                        <div class="modal-rekening-val">BCA 12345678 a.n Pemilik Kos</div>
                    </div>

                    <div class="modal-upload-wrapper">
                        <label class="modal-rekening-label">Upload Bukti Pembayaran</label>
                        <label class="modal-upload-box" id="dropAreaBukti">
                            <input type="file" name="bukti_transfer" id="inputBuktiTransfer" accept="image/*" required
                                onchange="previewFotoBukti(this)">

                            <div id="placeholderBukti" class="placeholder-bukti-content">
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#4ca5b0"
                                    stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"></path>
                                    <polyline points="7 9 12 4 17 9"></polyline>
                                    <line x1="12" y1="4" x2="12" y2="16"></line>
                                </svg>
                                <p class="modal-upload-text">Ketuk untuk pilih foto struk</p>
                            </div>

                            <img id="imgPreviewBukti" src="#" alt="Preview Bukti" class="preview-bukti-img">
                        </label>
                    </div>
                </div>

                <!-- BLOK TUNAI / CASH -->
                <div id="blokMetodeTunai" class="blok-metode-tunai">
                    <div class="tunai-info-wrapper">
                        <i class="fa-solid fa-hand-holding-dollar tunai-info-icon"></i>
                        <div>
                            <h4 class="tunai-info-title">Pembayaran Tunai Langsung</h4>
                            <p class="tunai-info-desc">
                                Silakan serahkan uang tunai sebesar <strong>Rp
                                    <?= number_format($tagihan_dipilih['nominal'], 0, ',', '.'); ?></strong> ke Pemilik
                                / Pengurus Kos. Setelah uang diterima, status tagihan Anda akan diverifikasi.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="modal-bayar-actions">
                    <a href="/?page=tagihan_riwayat" class="btn-bayar-batal">Batal</a>
                    <button type="submit" id="btnSubmitBukti" class="btn-bayar-kirim">
                        <span class="btn-text-content" id="btnSubmitText">Kirim Bukti</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        flatpickr(".flatpickr-range-input", {
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

        const toast = document.getElementById('toastAlertBox');
        if (toast) {
            setTimeout(closeToast, 5000);
        }
    });

    // Toggle Tampilan Antara Transfer dan Tunai
    function toggleMetodeBayar(metode) {
        const blokTransfer = document.getElementById('blokMetodeTransfer');
        const blokTunai = document.getElementById('blokMetodeTunai');
        const fileInput = document.getElementById('inputBuktiTransfer');
        const btnText = document.getElementById('btnSubmitText');

        if (metode === 'transfer') {
            if (blokTransfer) blokTransfer.style.display = 'flex';
            if (blokTunai) blokTunai.style.display = 'none';
            if (fileInput) fileInput.required = true;
            if (btnText) btnText.innerText = 'Kirim Bukti';
        } else {
            if (blokTransfer) blokTransfer.style.display = 'none';
            if (blokTunai) blokTunai.style.display = 'block';
            if (fileInput) {
                fileInput.required = false;
                fileInput.value = '';
            }
            if (btnText) btnText.innerText = 'Konfirmasi Bayar Tunai';
        }
    }

    function previewFotoBukti(input) {
        const file = input.files[0];
        const preview = document.getElementById('imgPreviewBukti');
        const placeholder = document.getElementById('placeholderBukti');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
            };
            reader.readAsDataURL(file);
        } else {
            preview.src = '#';
            preview.style.display = 'none';
            if (placeholder) {
                placeholder.style.display = 'flex';
            }
        }
    }

    function handleKirimBuktiSubmit(e) {
        const radios = document.getElementsByName('metode_bayar');
        let metode = 'transfer';
        for (const r of radios) {
            if (r.checked) {
                metode = r.value;
                break;
            }
        }

        if (metode === 'transfer') {
            const fileInput = document.getElementById('inputBuktiTransfer');
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Silakan pilih foto struk pembayaran transfer terlebih dahulu.');
                e.preventDefault();
                return;
            }
        }

        const btn = document.getElementById('btnSubmitBukti');
        if (btn) {
            btn.classList.add('btn-submit-loading');
        }
    }

    function closeToast() {
        const toast = document.getElementById('toastAlertBox');
        if (toast) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-15px) scale(0.95)';
            setTimeout(() => toast.remove(), 300);
        }
    }
    </script>
</body>

</html>