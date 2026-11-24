<?php
require_once __DIR__ . '/../../config/init.php';
/** @var PDO $pdo */

require_once __DIR__ . '/handler/tagihan_handler.php';
require_once __DIR__ . '/../shared/handler/sewa_reminder_helper.php';
require_once __DIR__ . '/../shared/handler/iuran_reminder_helper.php';

// 2. Eksekusi Fungsi Handler
$nama_penghuni = $_SESSION['user']['nama'] ?? ($_SESSION['nama'] ?? 'Penghuni');
$penghuniId    = (int)($_SESSION['user']['id'] ?? 0);
$pk_id         = function_exists('resolvePenghuniKamarId') ? resolvePenghuniKamarId($pdo, $_SESSION['user'] ?? []) : (int)($_SESSION['user']['penghuni_kamar_id'] ?? 0);

$evalSewa  = ($penghuniId > 0 && isset($pdo)) ? evaluasiStatusSewaPenghuni($pdo, $penghuniId) : null;
$evalIuran = ($penghuniId > 0 && isset($pdo)) ? evaluasiStatusIuranPenghuni($pdo, $penghuniId) : null;

if (($evalIuran['fase'] ?? '') === 'tenggang' && ($evalSewa['fase'] ?? '') !== 'tenggang') {
    $peringatanTagihan = $evalIuran;
} else {
    $peringatanTagihan = $evalSewa ?? $evalIuran;
}

$isPeringatanIuran = (($peringatanTagihan['jenis_tunggakan'] ?? 'sewa') === 'iuran');
$judulPeringatan   = $isPeringatanIuran ? 'Pengingat Iuran Kas' : 'Pengingat Sewa Kamar';

$post_res      = function_exists('handleUploadBuktiBayar') ? handleUploadBuktiBayar($pdo) : ['sukses' => '', 'error' => ''];
$sukses_pesan  = $post_res['sukses'] ?? ($sukses_pesan ?? ($_SESSION['pesan_sukses'] ?? ''));
$error_pesan   = $post_res['error'] ?? ($error_pesan ?? ($_SESSION['pesan_error'] ?? ''));
unset($_SESSION['pesan_sukses'], $_SESSION['pesan_error']);

$daftar_tagihan_saya = function_exists('fetchDaftarTagihan') ? fetchDaftarTagihan($pdo, $pk_id) : ($daftar_tagihan_saya ?? []);
$semua_riwayat       = function_exists('fetchRiwayatTransaksi') ? fetchRiwayatTransaksi($pdo, $pk_id) : ($semua_riwayat ?? []);

// Ambil Rekening & Data Pengesah Dinamis
$rekening_sewa      = 'Rekening sewa belum diatur oleh Pemilik Kos';
$ada_rekening_sewa  = false;
$rekening_iuran     = 'Rekening kas iuran belum diatur';
$ada_rekening_iuran = false;
$pemRow             = null;
$ketuaRow           = null;

if (isset($pdo) && ($pdo instanceof PDO)) {
    try {
        $stmtPem = $pdo->query("SELECT nama, nama_bank, no_rekening FROM pemilik ORDER BY id ASC LIMIT 1");
        $pemRow  = $stmtPem ? $stmtPem->fetch(PDO::FETCH_ASSOC) : null;
        if ($pemRow && !empty(trim((string)($pemRow['no_rekening'] ?? '')))) {
            $bankPem = trim((string)($pemRow['nama_bank'] ?: 'Bank'));
            $rekPem  = trim((string)$pemRow['no_rekening']);
            $anPem   = trim((string)$pemRow['nama']);
            $rekening_sewa     = "{$bankPem} {$rekPem} a.n. {$anPem}";
            $ada_rekening_sewa = true;
        }

        $stmtKetua = $pdo->query("
            SELECT id, nama, nama_bank, no_rekening
            FROM penghuni
            WHERE is_ketua_kos = true
              AND LOWER(TRIM(COALESCE(status_akun, 'aktif'))) = 'aktif'
            LIMIT 1
        ");
        $ketuaRow = $stmtKetua ? $stmtKetua->fetch(PDO::FETCH_ASSOC) : null;

        if ($ketuaRow) {
            if (!empty(trim((string)($ketuaRow['no_rekening'] ?? '')))) {
                $bankKt = trim((string)($ketuaRow['nama_bank'] ?: 'Bank'));
                $rekKt  = trim((string)$ketuaRow['no_rekening']);
                $anKt   = trim((string)$ketuaRow['nama']);
                $rekening_iuran     = "{$bankKt} {$rekKt} a.n. {$anKt}";
                $ada_rekening_iuran = true;
            } else {
                $rekening_iuran     = 'Rekening kas belum diisi oleh Ketua Kos (' . trim((string)$ketuaRow['nama']) . ')';
                $ada_rekening_iuran = false;
            }
        } else {
            $rekening_iuran     = $rekening_sewa;
            $ada_rekening_iuran = $ada_rekening_sewa;
        }
    } catch (Exception $e) {}
}

$namaPemilikPengesah = !empty(trim((string)($pemRow['nama'] ?? ''))) ? trim((string)$pemRow['nama']) : 'Pemilik Kos';
$adaKetuaAktif       = ($ketuaRow && !empty(trim((string)($ketuaRow['nama'] ?? ''))));
$namaKetuaPengesah   = $adaKetuaAktif ? trim((string)$ketuaRow['nama']) : $namaPemilikPengesah;
$jabatanKetuaPengesah = $adaKetuaAktif ? 'Ketua Kos (Pengelola Iuran),' : 'Pemilik Kos (Pengelola Iuran),';

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

$daftar_riwayat = array_values(array_filter($semua_riwayat, function ($r) use ($tgl_mulai, $tgl_selesai) {
    if ($tgl_mulai === '') return true;
    return ($r['tanggal_filter'] >= $tgl_mulai && $r['tanggal_filter'] <= $tgl_selesai);
}));
$is_filtered = ($date_range !== '');

// Pagination Riwayat Transaksi (Desktop: 10 per halaman, Mobile: 5 per halaman)
$total_riwayat = count($daftar_riwayat);

$limit_desktop  = 10;
$page_desktop   = isset($_GET['hal']) ? max(1, (int)$_GET['hal']) : 1;
$total_hal_desk = max(1, (int)ceil($total_riwayat / $limit_desktop));
if ($page_desktop > $total_hal_desk) $page_desktop = $total_hal_desk;

$limit_mobile   = 5;
$page_mobile    = isset($_GET['hal_m']) ? max(1, (int)$_GET['hal_m']) : 1;
$total_hal_mob  = max(1, (int)ceil($total_riwayat / $limit_mobile));
if ($page_mobile > $total_hal_mob) $page_mobile = $total_hal_mob;

$query_tanggal = $is_filtered ? '&date_range=' . urlencode($date_range) : '';

// 5. Handling Initial Modal Data
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
    <link rel="stylesheet" href="/css/global.css?v=<?= filemtime(__DIR__ . '/../../public/css/global.css'); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= filemtime(__DIR__ . '/../../public/css/modules.css'); ?>">
    <style>
        .print-only-invoice-sheet {
            display: none;
        }
        @media print {
            body {
                background: #ffffff !important;
            }
            body * {
                visibility: hidden !important;
            }
            #areaCetakInvoiceResmi,
            #areaCetakInvoiceResmi * {
                visibility: visible !important;
            }
            #areaCetakInvoiceResmi {
                display: block !important;
                position: fixed !important;
                inset: 0 !important;
                width: 100% !important;
                background: #ffffff !important;
                padding: 20px 28px !important;
                z-index: 999999 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
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

                <?php if (!empty($peringatanTagihan)): ?>
                <?php $isTenggang = ($peringatanTagihan['fase'] === 'tenggang'); ?>
                <div class="iuran-notif-banner <?= $isTenggang ? 'notif-danger' : 'notif-warning'; ?>">
                    <div class="iuran-notif-icon">
                        <i class="fa-solid <?= $isTenggang ? 'fa-triangle-exclamation' : 'fa-bell'; ?>"></i>
                    </div>
                    <div class="iuran-notif-body">
                        <strong><?= htmlspecialchars($judulPeringatan); ?>: <?= htmlspecialchars($peringatanTagihan['label_status']); ?></strong>
                        <p><?= htmlspecialchars($peringatanTagihan['pesan_ringkas']); ?></p>
                    </div>
                </div>
                <?php endif; ?>

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
                        $sudahDibayar  = (float)($t['sudah_dibayar'] ?? 0);
                        $sisaTagihan   = (float)($t['nominal'] ?? 0);
                        $is_pending    = (($t['status_verif'] ?? '') === 'pending');
                        $is_lunas      = (strtolower(trim($t['status_tagihan'] ?? '')) === 'lunas' || (!$is_pending && $sisaTagihan <= 0));
                        $is_ditolak    = (!$is_lunas && !$is_pending && ($t['status_verif'] ?? '') === 'ditolak');
                        $nominalFmt    = number_format($sisaTagihan, 0, ',', '.');
                        $sudahBayarFmt = number_format($sudahDibayar, 0, ',', '.');
                        $isSewaItem    = (stripos($t['jenis'] ?? '', 'sewa') !== false);
                        $rekTujuanItem = $isSewaItem ? $rekening_sewa : $rekening_iuran;
                        $adaRekItem    = $isSewaItem ? $ada_rekening_sewa : $ada_rekening_iuran;
                        $lblTujuanItem = $isSewaItem ? 'Transfer ke Rekening Pemilik Kos' : 'Transfer ke Rekening Kas Iuran';
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
                            <?php elseif ($sudahDibayar > 0): ?>
                            <span class="badge-tagihan-pending">Dicicil (Masuk Rp <?= $sudahBayarFmt; ?>)</span>
                            <?php else: ?>
                            <span class="badge-tagihan-belum">Belum bayar</span>
                            <?php endif; ?>
                        </div>
                        <div class="item-tagihan-body">
                            <div class="item-tagihan-left">
                                <span><?= $sudahDibayar > 0 ? 'Sisa tagihan' : 'Total tagihan'; ?></span>
                                <span>Batas bayar</span>
                            </div>
                            <div class="item-tagihan-right">
                                <span class="nominal-highlight">Rp <?= $nominalFmt; ?></span>
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
                        <div class="btn-card-action btn-card-pending"><i class="fa-solid fa-clock"></i> Diproses (Rp <?= number_format((float)($t['nominal_pending'] ?? $sisaTagihan), 0, ',', '.'); ?>)</div>
                        <?php elseif ($is_ditolak): ?>
                        <a href="/?page=tagihan_riwayat&bayar_id=<?= (int)$t['id']; ?>"
                            onclick="bukaModalBayarInstan(event, <?= (int)$t['id']; ?>, <?= htmlspecialchars(json_encode($t['jenis']), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode('Rp ' . $nominalFmt), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode($t['jatuh_tempo']), ENT_QUOTES, 'UTF-8'); ?>, <?= (int)$sisaTagihan; ?>, <?= htmlspecialchars(json_encode($rekTujuanItem), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode($lblTujuanItem), ENT_QUOTES, 'UTF-8'); ?>, <?= $adaRekItem ? 'true' : 'false'; ?>)"
                            class="btn-card-action btn-card-upload-ulang">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Ulang Bukti
                        </a>
                        <?php else: ?>
                        <a href="/?page=tagihan_riwayat&bayar_id=<?= (int)$t['id']; ?>"
                            onclick="bukaModalBayarInstan(event, <?= (int)$t['id']; ?>, <?= htmlspecialchars(json_encode($t['jenis']), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode('Rp ' . $nominalFmt), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode($t['jatuh_tempo']), ENT_QUOTES, 'UTF-8'); ?>, <?= (int)$sisaTagihan; ?>, <?= htmlspecialchars(json_encode($rekTujuanItem), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode($lblTujuanItem), ENT_QUOTES, 'UTF-8'); ?>, <?= $adaRekItem ? 'true' : 'false'; ?>)"
                            class="btn-card-action"><?= $sudahDibayar > 0 ? 'Cicil / Lunasi Sisa' : 'Bayar / Cicil'; ?></a>
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
                        <a href="/?page=tagihan_riwayat" class="btn-reset-range"><i class="fa-solid fa-rotate-left"></i> Reset</a>
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
                    <?php foreach ($daftar_riwayat as $idx => $r): ?>
                    <?php
                        $page_m     = (int)floor($idx / $limit_mobile) + 1;
                        $nominalFmt = 'Rp ' . number_format($r['nominal'], 0, ',', '.');
                        $invJson    = htmlspecialchars(json_encode([
                            'id'             => (int)$r['id'],
                            'judul'          => $r['judul'],
                            'nomor_invoice'  => $r['nomor_invoice'],
                            'no_kamar'       => $r['no_kamar'],
                            'tanggal'        => $r['tanggal'] ?? '',
                            'tanggal_masuk'  => $r['tanggal_masuk'] ?? '-',
                            'tanggal_terbit' => $r['tanggal_terbit'],
                            'jatuh_tempo'    => $r['jatuh_tempo'],
                            'berlaku_sampai' => $r['berlaku_sampai'] ?? '-',
                            'periode_sewa'   => $r['periode_sewa'],
                            'tanggal_bayar'  => $r['tanggal_bayar'],
                            'nominal_fmt'    => $nominalFmt
                        ]), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="item-tagihan-card riwayat-item-mobile <?= $page_m !== $page_mobile ? 'verif-hidden-item' : ''; ?>"
                         data-page-m="<?= $page_m; ?>">
                        <div class="item-tagihan-header">
                            <h4 class="item-tagihan-name"><?= htmlspecialchars($r['judul']); ?></h4>
                            <div class="riwayat-status-actions">
                                <span class="badge-status-lunas">Diterima</span>
                                <a href="/?page=tagihan_riwayat&hal_m=<?= $page_m; ?><?= $query_tanggal; ?>&invoice_id=<?= $r['id']; ?>"
                                    onclick="bukaModalInvoiceInstan(event, <?= $invJson; ?>)"
                                    class="btn-invoice-pill">
                                    <i class="fa-solid fa-file-invoice"></i> Invoice
                                </a>
                            </div>
                        </div>
                        <div class="item-tagihan-body">
                            <div class="item-tagihan-left">
                                <span><?= htmlspecialchars($r['tanggal']); ?></span>
                                <span>Berlaku s/d: <strong><?= htmlspecialchars($r['berlaku_sampai'] ?? '-'); ?></strong></span>
                            </div>
                            <div class="item-tagihan-right"><span class="nominal-highlight"><?= $nominalFmt; ?></span></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if ($total_hal_mob > 1): ?>
                    <div class="verif-pagination-wrapper verif-pagination-mobile">
                        <div class="verif-page-controls">
                            <button type="button" class="verif-page-btn" id="btnPrevRiwayatMob" onclick="gantiHalRiwayatMobile(-1)" title="Sebelumnya" <?= $page_mobile <= 1 ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>

                            <span class="verif-page-badge" id="badgeRiwayatMob"><?= $page_mobile; ?> / <?= $total_hal_mob; ?></span>

                            <button type="button" class="verif-page-btn" id="btnNextRiwayatMob" onclick="gantiHalRiwayatMobile(1)" title="Berikutnya" <?= $page_mobile >= $total_hal_mob ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
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
            <p class="tagihan-desktop-subtitle">Ringkasan tagihan, cicilan, dan riwayat pembayaran</p>

            <?php if (!empty($peringatanTagihan)): ?>
            <?php $isTenggang = ($peringatanTagihan['fase'] === 'tenggang'); ?>
            <div class="iuran-notif-banner tagihan-banner-max <?= $isTenggang ? 'notif-danger' : 'notif-warning'; ?>">
                <div class="iuran-notif-icon">
                    <i class="fa-solid <?= $isTenggang ? 'fa-triangle-exclamation' : 'fa-bell'; ?>"></i>
                </div>
                <div class="iuran-notif-body">
                    <strong><?= htmlspecialchars($judulPeringatan); ?>: <?= htmlspecialchars($peringatanTagihan['label_status']); ?></strong>
                    <p><?= htmlspecialchars($peringatanTagihan['pesan_ringkas']); ?> Total sisa tagihan: <strong>Rp <?= number_format((float)$peringatanTagihan['total_tunggakan'], 0, ',', '.'); ?></strong></p>
                </div>
            </div>
            <?php endif; ?>

            <h3 class="tagihan-section-title">Tagihan Saya</h3>
            <div class="desktop-tagihan-grid">
                <?php if (empty($daftar_tagihan_saya)): ?>
                <div class="empty-state-card tagihan-empty-full">
                    <i class="fa-solid fa-circle-check empty-icon"></i>
                    <span>Tidak ada tagihan yang belum dibayar saat ini.</span>
                </div>
                <?php else: ?>
                <?php foreach ($daftar_tagihan_saya as $t): ?>
                <?php
                    $sudahDibayar  = (float)($t['sudah_dibayar'] ?? 0);
                    $sisaTagihan   = (float)($t['nominal'] ?? 0);
                    $is_pending    = (($t['status_verif'] ?? '') === 'pending');
                    $is_lunas      = (strtolower(trim($t['status_tagihan'] ?? '')) === 'lunas' || (!$is_pending && $sisaTagihan <= 0));
                    $is_ditolak    = (!$is_lunas && !$is_pending && ($t['status_verif'] ?? '') === 'ditolak');
                    $nominalFmt    = number_format($sisaTagihan, 0, ',', '.');
                    $sudahBayarFmt = number_format($sudahDibayar, 0, ',', '.');
                    $isSewaItem    = (stripos($t['jenis'] ?? '', 'sewa') !== false);
                    $rekTujuanItem = $isSewaItem ? $rekening_sewa : $rekening_iuran;
                    $adaRekItem    = $isSewaItem ? $ada_rekening_sewa : $ada_rekening_iuran;
                    $lblTujuanItem = $isSewaItem ? 'Transfer ke Rekening Pemilik Kos' : 'Transfer ke Rekening Kas Iuran';
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
                        <?php elseif ($sudahDibayar > 0): ?>
                        <span class="badge-tagihan-pending">Dicicil (Masuk Rp <?= $sudahBayarFmt; ?>)</span>
                        <?php else: ?>
                        <span class="badge-tagihan-belum">Belum bayar</span>
                        <?php endif; ?>
                    </div>
                    <div class="item-tagihan-body">
                        <div class="item-tagihan-left">
                            <span><?= $sudahDibayar > 0 ? 'Sisa tagihan' : 'Total tagihan'; ?></span>
                            <span>Batas bayar</span>
                        </div>
                        <div class="item-tagihan-right">
                            <span class="nominal-highlight">Rp <?= $nominalFmt; ?></span>
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
                    <div class="btn-card-action btn-card-pending"><i class="fa-solid fa-clock"></i> Diproses (Rp <?= number_format((float)($t['nominal_pending'] ?? $sisaTagihan), 0, ',', '.'); ?>)</div>
                    <?php elseif ($is_ditolak): ?>
                    <a href="/?page=tagihan_riwayat&bayar_id=<?= (int)$t['id']; ?>"
                        onclick="bukaModalBayarInstan(event, <?= (int)$t['id']; ?>, <?= htmlspecialchars(json_encode($t['jenis']), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode('Rp ' . $nominalFmt), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode($t['jatuh_tempo']), ENT_QUOTES, 'UTF-8'); ?>, <?= (int)$sisaTagihan; ?>, <?= htmlspecialchars(json_encode($rekTujuanItem), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode($lblTujuanItem), ENT_QUOTES, 'UTF-8'); ?>, <?= $adaRekItem ? 'true' : 'false'; ?>)"
                        class="btn-card-action btn-card-upload-ulang">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload Ulang Bukti
                    </a>
                    <?php else: ?>
                    <a href="/?page=tagihan_riwayat&bayar_id=<?= (int)$t['id']; ?>"
                        onclick="bukaModalBayarInstan(event, <?= (int)$t['id']; ?>, <?= htmlspecialchars(json_encode($t['jenis']), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode('Rp ' . $nominalFmt), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode($t['jatuh_tempo']), ENT_QUOTES, 'UTF-8'); ?>, <?= (int)$sisaTagihan; ?>, <?= htmlspecialchars(json_encode($rekTujuanItem), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode($lblTujuanItem), ENT_QUOTES, 'UTF-8'); ?>, <?= $adaRekItem ? 'true' : 'false'; ?>)"
                        class="btn-card-action"><?= $sudahDibayar > 0 ? 'Cicil / Lunasi Sisa' : 'Bayar / Cicil'; ?></a>
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
                        <a href="/?page=tagihan_riwayat" class="btn-reset-range"><i class="fa-solid fa-rotate-left"></i> Reset</a>
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
                <?php foreach ($daftar_riwayat as $idx => $r): ?>
                <?php
                    $page_d     = (int)floor($idx / $limit_desktop) + 1;
                    $nominalFmt = 'Rp ' . number_format($r['nominal'], 0, ',', '.');
                    $invJson    = htmlspecialchars(json_encode([
                        'id'             => (int)$r['id'],
                        'judul'          => $r['judul'],
                        'nomor_invoice'  => $r['nomor_invoice'],
                        'no_kamar'       => $r['no_kamar'],
                        'tanggal'        => $r['tanggal'] ?? '',
                        'tanggal_masuk'  => $r['tanggal_masuk'] ?? '-',
                        'tanggal_terbit' => $r['tanggal_terbit'],
                        'jatuh_tempo'    => $r['jatuh_tempo'],
                        'berlaku_sampai' => $r['berlaku_sampai'] ?? '-',
                        'periode_sewa'   => $r['periode_sewa'],
                        'tanggal_bayar'  => $r['tanggal_bayar'],
                        'nominal_fmt'    => $nominalFmt
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="desktop-riwayat-card riwayat-item-desktop <?= $page_d !== $page_desktop ? 'verif-hidden-item' : ''; ?>"
                     data-page-d="<?= $page_d; ?>">
                    <div class="desktop-riwayat-info">
                        <h4 class="desktop-riwayat-title"><?= htmlspecialchars($r['judul']); ?></h4>
                        <span class="desktop-riwayat-date">
                            <?= htmlspecialchars($r['tanggal']); ?> &bull; Berlaku s/d <strong><?= htmlspecialchars($r['berlaku_sampai'] ?? '-'); ?></strong>
                        </span>
                    </div>
                    <div class="desktop-riwayat-right">
                        <div class="riwayat-status-actions">
                            <span class="badge-lunas-pill">Diterima</span>
                            <a href="/?page=tagihan_riwayat&hal=<?= $page_d; ?><?= $query_tanggal; ?>&invoice_id=<?= $r['id']; ?>"
                                onclick="bukaModalInvoiceInstan(event, <?= $invJson; ?>)"
                                class="btn-invoice-pill">
                                <i class="fa-solid fa-file-invoice"></i> Invoice
                            </a>
                        </div>
                        <span class="desktop-riwayat-nominal"><?= $nominalFmt; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($total_hal_desk > 1): ?>
            <div class="verif-pagination-wrapper tagihan-banner-max">
                <div class="verif-page-controls">
                    <button type="button" class="verif-page-btn" id="btnPrevRiwayatDesk" onclick="gantiHalRiwayatDesktop(currentHalRiwayatDesk - 1)" title="Sebelumnya" <?= $page_desktop <= 1 ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <?php for ($i = 1; $i <= $total_hal_desk; $i++): ?>
                    <button type="button"
                        data-riwayat-page-btn="<?= $i; ?>"
                        onclick="gantiHalRiwayatDesktop(<?= $i; ?>)"
                        class="verif-page-num <?= ($i === $page_desktop) ? 'active' : ''; ?>">
                        <?= $i; ?>
                    </button>
                    <?php endfor; ?>

                    <button type="button" class="verif-page-btn" id="btnNextRiwayatDesk" onclick="gantiHalRiwayatDesktop(currentHalRiwayatDesk + 1)" title="Berikutnya" <?= $page_desktop >= $total_hal_desk ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- MODAL INVOICE (INSTAN TANPA RELOAD) -->
    <?php $is_sewa_init = $invoice_dipilih ? (stripos($invoice_dipilih['judul'], 'sewa') !== false) : true; ?>
    <div id="modalInvoicePenghuni" class="modal-overlay <?= $invoice_dipilih ? 'active' : ''; ?>" onclick="tutupModalTagihanInstan(event)">
        <div class="invoice-wrapper" onclick="event.stopPropagation()">
            <header class="invoice-header-row">
                <div>
                    <h1 class="invoice-brand-title">SITEMAN - KOS</h1>
                    <p class="invoice-brand-sub">Sistem Manajemen Operasional Kos</p>
                </div>
                <div class="invoice-tag-box">
                    <h2 class="invoice-main-title" id="invModalTitle"><?= $is_sewa_init ? 'INVOICE SEWA' : 'INVOICE IURAN'; ?></h2>
                    <p class="invoice-number" id="invModalNomor"><?= htmlspecialchars($invoice_dipilih['nomor_invoice'] ?? ''); ?></p>
                    <span class="badge-invoice-status badge-status-paid"><i class="fa-solid fa-circle-check"></i> Diterima</span>
                </div>
            </header>

            <section class="invoice-meta-grid">
                <div>
                    <div class="meta-col-title">Ditagihkan Kepada</div>
                    <h3 class="meta-text-strong"><?= htmlspecialchars($nama_penghuni); ?></h3>
                    <p class="meta-text-muted"><i class="fa-solid fa-door-open"></i> Kamar <span id="invModalKamar"><?= htmlspecialchars($invoice_dipilih['no_kamar'] ?? '-'); ?></span></p>
                    <p class="meta-text-muted"><strong>Tanggal Masuk:</strong> <span id="invModalTglMasuk"><?= htmlspecialchars($invoice_dipilih['tanggal_masuk'] ?? '-'); ?></span></p>
                    <p class="meta-text-muted"><strong>Berlaku Sampai:</strong> <span id="invModalBerlakuSampai"><?= htmlspecialchars($invoice_dipilih['berlaku_sampai'] ?? '-'); ?></span></p>
                </div>
                <div class="text-right">
                    <div class="meta-col-title">Detail Waktu</div>
                    <p class="meta-text-muted"><strong>Tgl Terbit:</strong> <span id="invModalTglTerbit"><?= htmlspecialchars($invoice_dipilih['tanggal_terbit'] ?? ''); ?></span></p>
                    <p class="meta-text-muted"><strong>Jatuh Tempo:</strong> <span id="invModalJatuhTempo"><?= htmlspecialchars($invoice_dipilih['jatuh_tempo'] ?? ''); ?></span></p>
                    <p class="meta-text-muted"><strong>Periode:</strong> <span id="invModalPeriode"><?= htmlspecialchars($invoice_dipilih['periode_sewa'] ?? ''); ?></span></p>
                    <p class="meta-text-muted"><strong>Dibayar:</strong> <span id="invModalTglBayar"><?= htmlspecialchars($invoice_dipilih['tanggal_bayar'] ?? ''); ?></span></p>
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
                            <p class="item-name" id="invModalItemName"><?= htmlspecialchars($invoice_dipilih['judul'] ?? ''); ?></p>
                            <p class="item-desc" id="invModalItemDesc">
                                <?= htmlspecialchars($invoice_dipilih['periode_sewa'] ?? ''); ?> &bull; Berlaku s/d <?= htmlspecialchars($invoice_dipilih['berlaku_sampai'] ?? '-'); ?>
                            </p>
                        </td>
                        <td id="invModalItemNominal"><?= $invoice_dipilih ? 'Rp ' . number_format($invoice_dipilih['nominal'], 0, ',', '.') : 'Rp 0'; ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="invoice-summary-row">
                <div class="invoice-summary-box">
                    <span class="summary-total-label">Nominal Dibayar</span>
                    <span class="summary-total-value" id="invModalTotal"><?= $invoice_dipilih ? 'Rp ' . number_format($invoice_dipilih['nominal'], 0, ',', '.') : 'Rp 0'; ?></span>
                </div>
            </div>

            <div class="invoice-actions">
                <button type="button" onclick="tutupModalTagihanInstan(event)" class="btn-invoice-back"><i class="fa-solid fa-xmark"></i> Tutup</button>
                <button type="button"
                        id="btnCetakInvoiceResmi"
                        onclick="cetakInvoiceLangsung()"
                        class="btn-invoice-print">
                    <i class="fa-solid fa-print"></i> Cetak / Simpan PDF
                </button>
            </div>
        </div>
    </div>

    <!-- AREA CETAK INVOICE RESMI (TAMPIL HANYA SAAT PRINT / SAVE PDF) -->
    <?php
        $judul_dok_init = $is_sewa_init ? 'KWITANSI / INVOICE SEWA KAMAR' : 'KWITANSI / INVOICE IURAN KOS';
        $pengesah_init  = $is_sewa_init ? $namaPemilikPengesah : $namaKetuaPengesah;
        $jabatan_init   = $is_sewa_init ? 'Pemilik Kos,' : $jabatanKetuaPengesah;
    ?>
    <div id="areaCetakInvoiceResmi" class="print-only-invoice-sheet" aria-hidden="true">
        <div class="laporan-kop-surat">
            <h1 class="laporan-kop-title">SITEMAN - KOS</h1>
            <p class="laporan-kop-sub">Sistem Manajemen Operasional &amp; Hunian Kos Terpadu</p>
            <div class="laporan-kop-heading" id="printInvHeading"><?= htmlspecialchars($judul_dok_init); ?></div>
        </div>

        <div class="laporan-meta-card">
            <div class="laporan-meta-col">
                <span><strong>Nomor Invoice:</strong> <span id="printInvNomor"><?= htmlspecialchars($invoice_dipilih['nomor_invoice'] ?? ''); ?></span></span>
                <span><strong>Ditagihkan Kepada:</strong> <?= htmlspecialchars($nama_penghuni); ?> (Kamar <span id="printInvKamar"><?= htmlspecialchars($invoice_dipilih['no_kamar'] ?? '-'); ?></span>)</span>
                <span><strong>Tanggal Masuk:</strong> <span id="printInvTglMasuk"><?= htmlspecialchars($invoice_dipilih['tanggal_masuk'] ?? '-'); ?></span></span>
                <span><strong>Periode Tagihan:</strong> <span id="printInvPeriode"><?= htmlspecialchars($invoice_dipilih['periode_sewa'] ?? ''); ?></span></span>
                <span><strong>Berlaku Sampai:</strong> <span id="printInvBerlaku"><?= htmlspecialchars($invoice_dipilih['berlaku_sampai'] ?? '-'); ?></span></span>
            </div>
            <div class="laporan-meta-col invoice-meta-col-right">
                <span><strong>Tanggal Terbit:</strong> <span id="printInvTglTerbit"><?= htmlspecialchars($invoice_dipilih['tanggal_terbit'] ?? ''); ?></span></span>
                <span><strong>Jatuh Tempo:</strong> <span id="printInvJatuhTempo"><?= htmlspecialchars($invoice_dipilih['jatuh_tempo'] ?? ''); ?></span></span>
                <span><strong>Waktu Pembayaran:</strong> <span id="printInvTglBayar"><?= htmlspecialchars($invoice_dipilih['tanggal_bayar'] ?? ''); ?></span></span>
                <span class="invoice-status-wrap">
                    <span class="badge-invoice-lunas-pill">
                        <i class="fa-solid fa-circle-check"></i> STATUS: LUNAS
                    </span>
                </span>
            </div>
        </div>

        <table class="laporan-data-table">
            <thead>
                <tr>
                    <th class="invoice-col-no">No</th>
                    <th>Deskripsi Pembayaran</th>
                    <th>Keterangan &amp; Masa Berlaku</th>
                    <th class="invoice-col-nominal">Nominal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="invoice-cell-center">1</td>
                    <td><strong id="printInvJudulItem"><?= htmlspecialchars($invoice_dipilih['judul'] ?? ''); ?></strong></td>
                    <td id="printInvKetItem"><?= htmlspecialchars($invoice_dipilih['periode_sewa'] ?? ''); ?> (Berlaku s/d <?= htmlspecialchars($invoice_dipilih['berlaku_sampai'] ?? '-'); ?>)</td>
                    <td class="invoice-cell-nominal" id="printInvNominalItem"><?= $invoice_dipilih ? 'Rp ' . number_format($invoice_dipilih['nominal'], 0, ',', '.') : 'Rp 0'; ?></td>
                </tr>
                <tr class="laporan-row-saldo-bersih">
                    <td colspan="3" class="invoice-total-label-cell">TOTAL PEMBAYARAN DITERIMA</td>
                    <td class="invoice-total-val-cell" id="printInvTotalItem"><?= $invoice_dipilih ? 'Rp ' . number_format($invoice_dipilih['nominal'], 0, ',', '.') : 'Rp 0'; ?></td>
                </tr>
            </tbody>
        </table>

        <div class="invoice-footer-note">
            <strong>Catatan:</strong> Dokumen ini merupakan bukti pembayaran sah yang diterbitkan secara elektronik oleh sistem <strong>SITEMAN - KOS</strong>, telah diverifikasi lunas, dan berlaku sampai dengan tanggal <strong id="printInvBerlakuNote"><?= htmlspecialchars($invoice_dipilih['berlaku_sampai'] ?? '-'); ?></strong>.
        </div>

        <div class="laporan-ttd-wrapper">
            <div class="laporan-ttd-box">
                <p class="invoice-ttd-date">Malang, <span id="printInvTglTtd"><?= htmlspecialchars($invoice_dipilih['tanggal'] ?? ''); ?></span></p>
                <p class="invoice-ttd-jabatan" id="printInvJabatanTtd"><?= htmlspecialchars($jabatan_init); ?></p>
                <p class="laporan-ttd-nama" id="printInvNamaTtd"><?= htmlspecialchars($pengesah_init); ?></p>
                <p class="laporan-ttd-role">Terverifikasi Otomatis oleh Sistem</p>
            </div>
        </div>
    </div>

    <!-- MODAL BAYAR / CICIL (INSTAN TANPA RELOAD) -->
    <?php
        $is_sewa_modal_init = $tagihan_dipilih ? (stripos($tagihan_dipilih['jenis'] ?? '', 'sewa') !== false) : true;
        $rek_modal_init     = $is_sewa_modal_init ? $rekening_sewa : $rekening_iuran;
        $ada_rek_modal_init = $is_sewa_modal_init ? $ada_rekening_sewa : $ada_rekening_iuran;
        $lbl_rek_modal_init = $is_sewa_modal_init ? 'Transfer ke Rekening Pemilik Kos' : 'Transfer ke Rekening Kas Iuran';
    ?>
    <div id="modalBayarTagihan" class="modal-overlay <?= $tagihan_dipilih ? 'active' : ''; ?>" onclick="tutupModalTagihanInstan(event)">
        <div class="modal-bayar-card" onclick="event.stopPropagation()">
            <h3 class="modal-bayar-title">Bayar / Cicil Tagihan</h3>
            <div class="modal-ringkasan-box">
                <div class="modal-ringkasan-top">
                    <span class="modal-ringkasan-jenis" id="bayarModalJenis"><?= htmlspecialchars($tagihan_dipilih['jenis'] ?? ''); ?></span>
                    <span class="modal-ringkasan-nominal" id="bayarModalNominal"><?= $tagihan_dipilih ? 'Rp ' . number_format($tagihan_dipilih['nominal'], 0, ',', '.') : 'Rp 0'; ?></span>
                </div>
                <span class="modal-ringkasan-batas">Batas bayar <span id="bayarModalBatas"><?= htmlspecialchars($tagihan_dipilih['jatuh_tempo'] ?? ''); ?></span></span>
            </div>

            <form action="/?page=tagihan_riwayat" method="POST" enctype="multipart/form-data"
                class="modal-form-upload-tagihan" onsubmit="handleKirimBuktiSubmit(event)">
                <input type="hidden" name="tagihan_id" id="bayarModalTagihanId" value="<?= (int)($tagihan_dipilih['id'] ?? 0); ?>">
                <input type="hidden" id="bayarModalMaxSisa" value="<?= (int)($tagihan_dipilih['nominal'] ?? 0); ?>">
                <input type="hidden" id="bayarModalAdaRek" value="<?= $ada_rek_modal_init ? '1' : '0'; ?>">

                <!-- INPUT NOMINAL PEMBAYARAN / CICILAN -->
                <div class="modal-rekening-wrapper">
                    <label class="modal-rekening-label" for="inputNominalBayar">Nominal Bayar / Cicilan (Rp)</label>
                    <input type="text" name="nominal_bayar" id="inputNominalBayar" class="modal-input"
                        value="<?= $tagihan_dipilih ? number_format((float)$tagihan_dipilih['nominal'], 0, ',', '.') : ''; ?>"
                        placeholder="Masukkan nominal bayar atau cicilan" inputmode="numeric" required
                        oninput="formatDanSyncNominalBayar(this)">
                </div>

                <!-- PILIHAN METODE PEMBAYARAN -->
                <div class="metode-bayar-group">
                    <label class="modal-rekening-label">Metode Pembayaran</label>
                    <div class="metode-bayar-grid">
                        <label class="metode-bayar-option">
                            <input type="radio" name="metode_bayar" id="radioMetodeTransfer" value="transfer" checked
                                onchange="toggleMetodeBayar('transfer')" class="metode-radio-input">
                            <span><i class="fa-solid fa-building-columns"></i> Transfer</span>
                        </label>
                        <label class="metode-bayar-option">
                            <input type="radio" name="metode_bayar" id="radioMetodeTunai" value="tunai" onchange="toggleMetodeBayar('tunai')"
                                class="metode-radio-input">
                            <span><i class="fa-solid fa-money-bill-wave"></i> Tunai (Cash)</span>
                        </label>
                    </div>
                </div>

                <!-- BLOK TRANSFER (REKENING DINAMIS + UPLOAD BUKTI) -->
                <div id="blokMetodeTransfer" class="blok-metode-transfer">
                    <div class="modal-rekening-wrapper">
                        <label class="modal-rekening-label" id="bayarModalRekLabel"><?= htmlspecialchars($lbl_rek_modal_init); ?></label>
                        <div class="modal-rekening-val" id="bayarModalRekVal"><?= htmlspecialchars($rek_modal_init); ?></div>
                    </div>

                    <div class="modal-upload-wrapper" id="wrapperUploadBuktiTransfer">
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
                                Silakan serahkan uang tunai sebesar <strong id="bayarModalTunaiNominal"><?= $tagihan_dipilih ? 'Rp ' . number_format($tagihan_dipilih['nominal'], 0, ',', '.') : 'Rp 0'; ?></strong> ke Pemilik
                                / Pengurus Kos. Setelah uang diterima, status tagihan Anda akan diverifikasi.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="modal-bayar-actions">
                    <button type="button" onclick="tutupModalTagihanInstan(event)" class="btn-bayar-batal">Batal</button>
                    <button type="submit" id="btnSubmitBukti" class="btn-bayar-kirim">
                        <span class="btn-text-content" id="btnSubmitText">Kirim Bukti</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
    const PENGESAH_SEWA = <?= json_encode([
        'nama'    => $namaPemilikPengesah,
        'jabatan' => 'Pemilik Kos,'
    ]); ?>;
    const PENGESAH_IURAN = <?= json_encode([
        'nama'    => $namaKetuaPengesah,
        'jabatan' => $jabatanKetuaPengesah
    ]); ?>;

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

    function formatAngkaRibuan(angka) {
        return String(angka).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function formatDanSyncNominalBayar(input) {
        const maxSisa = parseInt(document.getElementById('bayarModalMaxSisa').value || '0', 10);
        let digits = input.value.replace(/[^0-9]/g, '');
        let val = parseInt(digits || '0', 10);

        if (maxSisa > 0 && val > maxSisa) {
            val = maxSisa;
        }

        input.value = val > 0 ? formatAngkaRibuan(val) : '';
        const tunaiLabel = document.getElementById('bayarModalTunaiNominal');
        if (tunaiLabel) {
            tunaiLabel.textContent = 'Rp ' + (val > 0 ? formatAngkaRibuan(val) : '0');
        }
    }

    function bukaModalBayarInstan(e, id, jenis, nominalFmt, batas, sisaAngka, rekTujuan, rekLabel, adaRek) {
        if (e) e.preventDefault();

        const maxVal = parseInt(sisaAngka || 0, 10);
        const rekTersedia = (adaRek === true || adaRek === 'true' || adaRek === 1 || adaRek === '1');

        document.getElementById('bayarModalTagihanId').value = id;
        document.getElementById('bayarModalMaxSisa').value = maxVal;
        document.getElementById('bayarModalAdaRek').value = rekTersedia ? '1' : '0';
        document.getElementById('bayarModalJenis').textContent = jenis;
        document.getElementById('bayarModalNominal').textContent = nominalFmt;
        document.getElementById('bayarModalBatas').textContent = batas;
        document.getElementById('bayarModalTunaiNominal').textContent = nominalFmt;

        const elRekLabel = document.getElementById('bayarModalRekLabel');
        const elRekVal   = document.getElementById('bayarModalRekVal');
        if (elRekLabel && rekLabel) elRekLabel.textContent = rekLabel;
        if (elRekVal && rekTujuan) {
            elRekVal.textContent = rekTujuan;
            elRekVal.style.color = rekTersedia ? '' : '#b91c1c';
        }

        const inputNom = document.getElementById('inputNominalBayar');
        if (inputNom) {
            inputNom.value = maxVal > 0 ? formatAngkaRibuan(maxVal) : '';
        }

        const radioTransfer = document.getElementById('radioMetodeTransfer');
        if (radioTransfer) radioTransfer.checked = true;
        toggleMetodeBayar('transfer');

        const fileInput = document.getElementById('inputBuktiTransfer');
        if (fileInput) {
            fileInput.value = '';
            previewFotoBukti(fileInput);
        }

        document.getElementById('modalBayarTagihan').classList.add('active');

        const url = new URL(window.location.href);
        url.searchParams.set('bayar_id', id);
        url.searchParams.delete('invoice_id');
        window.history.replaceState({}, '', url);
    }

    function bukaModalInvoiceInstan(e, data) {
        if (e) e.preventDefault();

        const isSewa   = (data.judul || '').toLowerCase().includes('sewa');
        const berlaku  = data.berlaku_sampai || '-';
        const periode  = data.periode_sewa || '';
        const nomFmt   = data.nominal_fmt || 'Rp 0';
        const pengesah = isSewa ? PENGESAH_SEWA : PENGESAH_IURAN;

        // Sinkronisasi tampilan Modal
        document.getElementById('invModalTitle').textContent = isSewa ? 'INVOICE SEWA' : 'INVOICE IURAN';
        document.getElementById('invModalNomor').textContent = data.nomor_invoice || '';
        document.getElementById('invModalKamar').textContent = data.no_kamar || '-';
        document.getElementById('invModalTglMasuk').textContent = data.tanggal_masuk || '-';
        document.getElementById('invModalBerlakuSampai').textContent = berlaku;
        document.getElementById('invModalTglTerbit').textContent = data.tanggal_terbit || '';
        document.getElementById('invModalJatuhTempo').textContent = data.jatuh_tempo || '';
        document.getElementById('invModalPeriode').textContent = periode;
        document.getElementById('invModalTglBayar').textContent = data.tanggal_bayar || '';
        document.getElementById('invModalItemName').textContent = data.judul || '';
        document.getElementById('invModalItemDesc').textContent = periode + ' • Berlaku s/d ' + berlaku;
        document.getElementById('invModalItemNominal').textContent = nomFmt;
        document.getElementById('invModalTotal').textContent = nomFmt;

        // Sinkronisasi Sheet Cetak Resmi
        document.getElementById('printInvHeading').textContent = isSewa ? 'KWITANSI / INVOICE SEWA KAMAR' : 'KWITANSI / INVOICE IURAN KOS';
        document.getElementById('printInvNomor').textContent = data.nomor_invoice || '';
        document.getElementById('printInvKamar').textContent = data.no_kamar || '-';
        document.getElementById('printInvTglMasuk').textContent = data.tanggal_masuk || '-';
        document.getElementById('printInvPeriode').textContent = periode;
        document.getElementById('printInvBerlaku').textContent = berlaku;
        document.getElementById('printInvTglTerbit').textContent = data.tanggal_terbit || '';
        document.getElementById('printInvJatuhTempo').textContent = data.jatuh_tempo || '';
        document.getElementById('printInvTglBayar').textContent = data.tanggal_bayar || '';
        document.getElementById('printInvJudulItem').textContent = data.judul || '';
        document.getElementById('printInvKetItem').textContent = periode + ' (Berlaku s/d ' + berlaku + ')';
        document.getElementById('printInvNominalItem').textContent = nomFmt;
        document.getElementById('printInvTotalItem').textContent = nomFmt;
        document.getElementById('printInvBerlakuNote').textContent = berlaku;
        document.getElementById('printInvTglTtd').textContent = data.tanggal || (data.tanggal_bayar || '').split(',')[0] || '';
        document.getElementById('printInvJabatanTtd').textContent = pengesah.jabatan;
        document.getElementById('printInvNamaTtd').textContent = pengesah.nama;

        document.getElementById('modalInvoicePenghuni').classList.add('active');

        const url = new URL(window.location.href);
        url.searchParams.set('invoice_id', data.id);
        url.searchParams.delete('bayar_id');
        window.history.replaceState({}, '', url);
    }

function cetakInvoiceLangsung() {
        const btn = document.getElementById('btnCetakInvoiceResmi');
        const nomor = document.getElementById('invModalNomor')?.textContent?.trim() || 'Invoice';
        const judulAsli = document.title;

        if (btn) {
            btn.classList.add('is-pressed');
        }

        setTimeout(function () {
            document.title = 'Invoice_' + nomor.replace(/[^a-zA-Z0-9_-]/g, '_');
            window.print();

            setTimeout(function () {
                document.title = judulAsli;
                if (btn) {
                    btn.classList.remove('is-pressed');
                }
            }, 300);
        }, 170);
    }

    function tutupModalTagihanInstan(e) {
        if (e) e.preventDefault();
        document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));

        const url = new URL(window.location.href);
        url.searchParams.delete('bayar_id');
        url.searchParams.delete('invoice_id');
        window.history.replaceState({}, '', url);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') tutupModalTagihanInstan();
    });

    let currentHalRiwayatDesk = <?= (int)$page_desktop; ?>;
    const totalHalRiwayatDesk = <?= (int)$total_hal_desk; ?>;

    function gantiHalRiwayatDesktop(target) {
        if (target < 1 || target > totalHalRiwayatDesk) return;
        currentHalRiwayatDesk = target;

        document.querySelectorAll('.riwayat-item-desktop').forEach(card => {
            card.classList.toggle('verif-hidden-item', parseInt(card.getAttribute('data-page-d')) !== currentHalRiwayatDesk);
        });

        document.querySelectorAll('[data-riwayat-page-btn]').forEach(btn => {
            btn.classList.toggle('active', parseInt(btn.getAttribute('data-riwayat-page-btn')) === currentHalRiwayatDesk);
        });

        const btnPrev = document.getElementById('btnPrevRiwayatDesk');
        const btnNext = document.getElementById('btnNextRiwayatDesk');
        if (btnPrev) btnPrev.disabled = currentHalRiwayatDesk <= 1;
        if (btnNext) btnNext.disabled = currentHalRiwayatDesk >= totalHalRiwayatDesk;

        const url = new URL(window.location.href);
        if (currentHalRiwayatDesk === 1) url.searchParams.delete('hal');
        else url.searchParams.set('hal', currentHalRiwayatDesk);
        window.history.replaceState({}, '', url);
    }

    let currentHalRiwayatMob = <?= (int)$page_mobile; ?>;
    const totalHalRiwayatMob = <?= (int)$total_hal_mob; ?>;

    function gantiHalRiwayatMobile(delta) {
        const target = currentHalRiwayatMob + delta;
        if (target < 1 || target > totalHalRiwayatMob) return;
        currentHalRiwayatMob = target;

        document.querySelectorAll('.riwayat-item-mobile').forEach(card => {
            card.classList.toggle('verif-hidden-item', parseInt(card.getAttribute('data-page-m')) !== currentHalRiwayatMob);
        });

        const badge = document.getElementById('badgeRiwayatMob');
        if (badge) badge.textContent = ` ${currentHalRiwayatMob} / ${totalHalRiwayatMob}`;

        const btnPrev = document.getElementById('btnPrevRiwayatMob');
        const btnNext = document.getElementById('btnNextRiwayatMob');
        if (btnPrev) btnPrev.disabled = currentHalRiwayatMob <= 1;
        if (btnNext) btnNext.disabled = currentHalRiwayatMob >= totalHalRiwayatMob;

        const url = new URL(window.location.href);
        if (currentHalRiwayatMob === 1) url.searchParams.delete('hal_m');
        else url.searchParams.set('hal_m', currentHalRiwayatMob);
        window.history.replaceState({}, '', url);
    }

    function toggleMetodeBayar(metode) {
        const blokTransfer   = document.getElementById('blokMetodeTransfer');
        const blokTunai      = document.getElementById('blokMetodeTunai');
        const wrapperUpload  = document.getElementById('wrapperUploadBuktiTransfer');
        const fileInput      = document.getElementById('inputBuktiTransfer');
        const btnText        = document.getElementById('btnSubmitText');
        const btnSubmit      = document.getElementById('btnSubmitBukti');
        const rekTersedia    = (document.getElementById('bayarModalAdaRek')?.value === '1');

        if (metode === 'transfer') {
            if (blokTransfer) blokTransfer.style.display = 'flex';
            if (blokTunai) blokTunai.style.display = 'none';
            if (wrapperUpload) wrapperUpload.style.display = rekTersedia ? 'block' : 'none';
            if (fileInput) fileInput.required = rekTersedia;
            if (btnText) btnText.innerText = rekTersedia ? 'Kirim Bukti' : 'Rekening Belum Tersedia';
            if (btnSubmit) {
                btnSubmit.disabled = !rekTersedia;
                btnSubmit.style.opacity = rekTersedia ? '1' : '0.55';
            }
        } else {
            if (blokTransfer) blokTransfer.style.display = 'none';
            if (blokTunai) blokTunai.style.display = 'block';
            if (fileInput) {
                fileInput.required = false;
                fileInput.value = '';
            }
            if (btnText) btnText.innerText = 'Konfirmasi Bayar Tunai';
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.style.opacity = '1';
            }
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
        const inputNom = document.getElementById('inputNominalBayar');
        const maxSisa = parseInt(document.getElementById('bayarModalMaxSisa').value || '0', 10);
        const valNom = parseInt((inputNom ? inputNom.value.replace(/[^0-9]/g, '') : '0') || '0', 10);

        if (valNom < 1000 || (maxSisa > 0 && valNom > maxSisa)) {
            alert('Masukkan nominal bayar antara Rp 1.000 hingga Rp ' + formatAngkaRibuan(maxSisa));
            if (inputNom) inputNom.focus();
            e.preventDefault();
            return;
        }

        const radios = document.getElementsByName('metode_bayar');
        let metode = 'transfer';
        for (const r of radios) {
            if (r.checked) {
                metode = r.value;
                break;
            }
        }

        if (metode === 'transfer') {
            const rekTersedia = (document.getElementById('bayarModalAdaRek')?.value === '1');
            if (!rekTersedia) {
                alert('Rekening tujuan transfer belum diatur. Silakan pilih metode Tunai atau hubungi pengurus kos.');
                e.preventDefault();
                return;
            }
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