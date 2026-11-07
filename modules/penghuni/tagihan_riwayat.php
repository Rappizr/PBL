<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $possible_paths = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../config/database.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/database.php',
        dirname(__DIR__, 2) . '/config/database.php',
        dirname(__DIR__, 3) . '/config/database.php'
    ];
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

$nama_penghuni = $_SESSION['user']['nama'] ?? "Penghuni";
$daftar_tagihan_saya = [];
$semua_riwayat = [];
$sukses_pesan = '';
$error_pesan = '';
$sql_error_debug = '';

if (isset($pdo) && $pdo instanceof PDO) {

    // A. PROSES POST: UPLOAD BUKTI PEMBAYARAN
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tagihan_id'])) {
        $tagihan_id = (int)$_POST['tagihan_id'];
        
        if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['bukti_transfer']['tmp_name'];
            $file_name = $_FILES['bukti_transfer']['name'];
            $ext = pathinfo($file_name, PATHINFO_EXTENSION);
            
            $new_filename = 'bukti_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $upload_dir = __DIR__ . '/../../public/uploads/bukti/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
                try {
                    $pdo->beginTransaction();

                    // Ambil nominal dari kategori iuran jika ada, jika tidak default 500000
                    $stmtTag = $pdo->prepare("
                        SELECT COALESCE(ki.nominal, 500000) AS nominal 
                        FROM tagihan t 
                        LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id 
                        WHERE t.id = ?
                    ");
                    $stmtTag->execute([$tagihan_id]);
                    $tagData = $stmtTag->fetch(PDO::FETCH_ASSOC);
                    $jumlah_bayar = $tagData['nominal'] ?? 500000;

                    $stmtBayar = $pdo->prepare("
                        INSERT INTO pembayaran (tagihan_id, bukti_pembayaran, jumlah_bayar, metode_bayar, status_verifikasi, no_kwitansi)
                        VALUES (?, ?, ?, 'transfer', 'pending', ?)
                    ");
                    $no_kwitansi = 'KW/' . date('Ym') . '/' . rand(1000, 9999);
                    $stmtBayar->execute([$tagihan_id, $new_filename, $jumlah_bayar, $no_kwitansi]);

                    $stmtUpTagihan = $pdo->prepare("UPDATE tagihan SET status_tagihan = 'sebagian' WHERE id = ?");
                    $stmtUpTagihan->execute([$tagihan_id]);

                    $pdo->commit();
                    $sukses_pesan = "Bukti pembayaran berhasil dikirim dan sedang menunggu verifikasi.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error_pesan = "Gagal memproses pembayaran: " . $e->getMessage();
                }
            } else {
                $error_pesan = "Gagal mengunggah file foto bukti transfer.";
            }
        } else {
            $error_pesan = "Harap lampirkan file foto bukti pembayaran.";
        }
    }

    // B. AMBIL DATA TAGIHAN SAYA SECARA AMAN
    try {
        $sqlTagihan = "
            SELECT 
                t.id,
                COALESCE(ki.nama_iuran, 'Sewa Kos / Iuran') AS jenis,
                COALESCE(ki.nominal, 500000) AS nominal,
                TO_CHAR(t.tanggal_jatuh_tempo, 'DD-MM-YYYY') AS jatuh_tempo
            FROM tagihan t
            LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
            WHERE t.status_tagihan IN ('belum_bayar', 'sebagian', 'terlambat')
            ORDER BY t.tanggal_jatuh_tempo ASC
        ";
        $stmtT = $pdo->query($sqlTagihan);
        $daftar_tagihan_saya = $stmtT->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $sql_error_debug = $e->getMessage();
    }

    // C. AMBIL DATA RIWAYAT TRANSAKSI (LUNAS)
    try {
        $sqlRiwayat = "
            SELECT 
                p.id,
                COALESCE(ki.nama_iuran, 'Sewa Bulanan') AS judul,
                TO_CHAR(p.tanggal_bayar, 'DD Month YYYY') AS tanggal,
                TO_CHAR(p.tanggal_bayar, 'YYYY-MM-DD') AS tanggal_filter,
                p.jumlah_bayar AS nominal,
                p.no_kwitansi AS nomor_invoice,
                TO_CHAR(p.tanggal_bayar, 'DD Month YYYY') AS tanggal_terbit,
                TO_CHAR(p.tanggal_bayar, 'DD Month YYYY') AS jatuh_tempo,
                TO_CHAR(p.tanggal_bayar, 'DD Month YYYY, HH24:MI') || ' WIB' AS tanggal_bayar,
                'Bulanan' AS periode_sewa,
                '-' AS no_kamar
            FROM pembayaran p
            JOIN tagihan t ON p.tagihan_id = t.id
            LEFT JOIN kategori_iuran ki ON t.kategori_iuran_id = ki.id
            WHERE p.status_verifikasi = 'diterima'
            ORDER BY p.tanggal_bayar DESC
        ";
        $stmtR = $pdo->query($sqlRiwayat);
        $semua_riwayat = $stmtR->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $semua_riwayat = [];
    }
}

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
    if ($tgl_mulai === '') {
        return true;
    }
    return ($r['tanggal_filter'] >= $tgl_mulai && $r['tanggal_filter'] <= $tgl_selesai);
});

$is_filtered = ($date_range !== '');

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

$modal_invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : null;
$invoice_dipilih = null;
if ($modal_invoice_id) {
    foreach ($semua_riwayat as $item) {
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="dashboard-body">

    <?php if (!empty($sql_error_debug)): ?>
        <div style="background: #ef4444; color: white; padding: 12px 20px; margin: 10px; border-radius: 8px; font-weight: 700;">
            DEBUG ERROR SQL: <?= htmlspecialchars($sql_error_debug); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($sukses_pesan)): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 12px 20px; margin: 10px; border-radius: 8px; font-weight: 700; text-align: center;">
            <?= htmlspecialchars($sukses_pesan); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_pesan)): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px 20px; margin: 10px; border-radius: 8px; font-weight: 700; text-align: center;">
            <?= htmlspecialchars($error_pesan); ?>
        </div>
    <?php endif; ?>

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
                            <i class="fa-solid fa-circle-check empty-icon" style="color: #0b5e39;"></i>
                            <span>Hore! Tidak ada tagihan yang belum dibayar saat ini.</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($daftar_tagihan_saya as $t): ?>
                            <div class="item-tagihan-card">
                                <div class="item-tagihan-header">
                                    <h4 class="item-tagihan-name"><?= htmlspecialchars($t['jenis']); ?></h4>
                                    <span class="badge-status-krem">Belum bayar</span>
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
                                <a href="/?page=tagihan_riwayat&bayar_id=<?= $t['id']; ?>" class="btn-card-action">
                                    Bayar
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <h3 class="section-title-white">Riwayat Transaksi</h3>
                
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
                            <span>Belum ada riwayat transaksi yang disetujui.</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($daftar_riwayat as $r): ?>
                            <div class="item-tagihan-card">
                                <div class="item-tagihan-header">
                                    <h4 class="item-tagihan-name"><?= htmlspecialchars($r['judul']); ?></h4>
                                    <div class="riwayat-status-actions">
                                        <span class="badge-status-lunas">Lunas</span>
                                        <a href="/?page=tagihan_riwayat&invoice_id=<?= $r['id']; ?>" class="btn-invoice-pill">
                                            <i class="fa-solid fa-file-invoice"></i> Invoice
                                        </a>
                                    </div>
                                </div>
                                <div class="item-tagihan-body">
                                    <div class="item-tagihan-left">
                                        <span><?= htmlspecialchars($r['tanggal']); ?></span>
                                    </div>
                                    <div class="item-tagihan-right">
                                        <span class="nominal-highlight">Rp <?= number_format($r['nominal'], 0, ',', '.'); ?></span>
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
                <?php if (empty($daftar_tagihan_saya)): ?>
                    <div class="empty-state-card" style="max-width: 100%;">
                        <i class="fa-solid fa-circle-check empty-icon" style="color: #0b5e39;"></i>
                        <span>Hore! Tidak ada tagihan yang belum dibayar saat ini.</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($daftar_tagihan_saya as $t): ?>
                        <div class="item-tagihan-card">
                            <div class="item-tagihan-header">
                                <h4 class="item-tagihan-name"><?= htmlspecialchars($t['jenis']); ?></h4>
                                <span class="badge-status-krem">Belum bayar</span>
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
                            <a href="/?page=tagihan_riwayat&bayar_id=<?= $t['id']; ?>" class="btn-card-action">
                                Bayar
                            </a>
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
                            <a href="/?page=tagihan_riwayat" class="btn-reset-range" title="Reset Filter">
                                <i class="fa-solid fa-rotate-left"></i> Reset
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="desktop-riwayat-list">
                <?php if (empty($daftar_riwayat)): ?>
                    <div class="empty-state-card" style="max-width: 100%;">
                        <i class="fa-solid fa-folder-open empty-icon"></i>
                        <span>Belum ada riwayat transaksi yang disetujui.</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($daftar_riwayat as $r): ?>
                        <div class="desktop-riwayat-card">
                            <div class="desktop-riwayat-info">
                                <h4 class="desktop-riwayat-title"><?= htmlspecialchars($r['judul']); ?></h4>
                                <span class="desktop-riwayat-date"><?= htmlspecialchars($r['tanggal']); ?></span>
                            </div>
                            <div class="desktop-riwayat-right">
                                <div class="riwayat-status-actions">
                                    <span class="badge-lunas-pill">Lunas</span>
                                    <a href="/?page=tagihan_riwayat&invoice_id=<?= $r['id']; ?>" class="btn-invoice-pill">
                                        <i class="fa-solid fa-file-invoice"></i> Invoice
                                    </a>
                                </div>
                                <span class="desktop-riwayat-nominal">Rp <?= number_format($r['nominal'], 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
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
                        <h2 class="invoice-main-title">INVOICE SEWA</h2>
                        <p class="invoice-number"><?= htmlspecialchars($invoice_dipilih['nomor_invoice']); ?></p>
                        <span class="badge-invoice-status badge-status-paid"><i class="fa-solid fa-circle-check"></i> Lunas</span>
                    </div>
                </header>

                <section class="invoice-meta-grid">
                    <div>
                        <div class="meta-col-title">Ditagihkan Kepada</div>
                        <h3 class="meta-text-strong"><?= htmlspecialchars($nama_penghuni); ?></h3>
                        <p class="meta-text-muted"><i class="fa-solid fa-door-open"></i> Kamar <?= htmlspecialchars($invoice_dipilih['no_kamar']); ?></p>
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

    <!-- MODAL BAYAR TAGIHAN -->
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
                            BCA 12345678 a.n Pemilik Kos
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
        });
    </script>
</body>
</html>