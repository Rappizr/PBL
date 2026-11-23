<?php
require_once __DIR__ . '/../../config/init.php';
/** @var PDO $pdo */

// 1. Load Handler Tagihan
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

// 2. Validasi Akses & Ambil Data Invoice
$nama_penghuni = $_SESSION['user']['nama'] ?? ($_SESSION['nama'] ?? 'Penghuni');
$pk_id         = function_exists('resolvePenghuniKamarId') 
                 ? resolvePenghuniKamarId($pdo, $_SESSION['user'] ?? []) 
                 : (int)($_SESSION['user']['penghuni_kamar_id'] ?? 0);

$semua_riwayat = function_exists('fetchRiwayatTransaksi') ? fetchRiwayatTransaksi($pdo, $pk_id) : [];

$invoice_id = (int)($_GET['id'] ?? $_GET['invoice_id'] ?? 0);
$invoice    = null;

foreach ($semua_riwayat as $item) {
    if ((int)$item['id'] === $invoice_id) {
        $invoice = $item;
        break;
    }
}

if (!$invoice) {
    echo "<script>alert('Data invoice tidak ditemukan atau Anda tidak memiliki akses.'); window.location.href='/?page=tagihan_riwayat';</script>";
    exit;
}

$is_sewa       = (stripos($invoice['judul'], 'sewa') !== false);
$judul_dokumen = $is_sewa ? 'KWITANSI / INVOICE SEWA KAMAR' : 'KWITANSI / INVOICE IURAN KOS';

// 3. Ambil Nama Pengesah Dokumen (Pemilik untuk Sewa, Ketua Kos untuk Iuran dengan fallback Pemilik)
$nama_pengesah    = 'Pengelola SITEMAN - KOS';
$jabatan_pengesah = $is_sewa ? 'Pemilik Kos,' : 'Ketua Kos (Pengelola Iuran),';

if (isset($pdo) && ($pdo instanceof PDO)) {
    try {
        $namaPemilikDb = '';
        $stmtPemilik   = $pdo->query("SELECT nama FROM pemilik ORDER BY id ASC LIMIT 1");
        $rowPemilik    = $stmtPemilik ? $stmtPemilik->fetch(PDO::FETCH_ASSOC) : null;
        if ($rowPemilik && !empty(trim((string)$rowPemilik['nama']))) {
            $namaPemilikDb = trim((string)$rowPemilik['nama']);
        }

        if ($is_sewa) {
            if ($namaPemilikDb !== '') {
                $nama_pengesah = $namaPemilikDb;
            }
        } else {
            $stmtKetua = $pdo->query("
                SELECT nama 
                FROM penghuni 
                WHERE is_ketua_kos = true 
                  AND LOWER(TRIM(COALESCE(status_akun, 'aktif'))) = 'aktif'
                LIMIT 1
            ");
            $rowKetua = $stmtKetua ? $stmtKetua->fetch(PDO::FETCH_ASSOC) : null;

            if ($rowKetua && !empty(trim((string)$rowKetua['nama']))) {
                $nama_pengesah    = trim((string)$rowKetua['nama']);
                $jabatan_pengesah = 'Ketua Kos (Pengelola Iuran),';
            } else {
                // Tidak ada Ketua Kos aktif -> diambil alih oleh Pemilik Kos
                $nama_pengesah    = ($namaPemilikDb !== '') ? $namaPemilikDb : 'Pemilik Kos';
                $jabatan_pengesah = 'Pemilik Kos (Pengelola Iuran),';
            }
        }
    } catch (Throwable $e) {
        // Fallback default jika query gagal
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($invoice['nomor_invoice']); ?> | SITEMAN KOS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/modules.css?v=<?= filemtime(__DIR__ . '/../../public/css/modules.css'); ?>">
</head>
<body class="laporan-print-body">

    <div class="laporan-page-container">
        <!-- BAR AKSI ATAS (HILANG SAAT DICETAK / SAVE PDF) -->
        <div class="no-print-bar">
            <span class="invoice-preview-label">
                <i class="fa-solid fa-file-invoice"></i> Pratinjau Dokumen Invoice
            </span>
            <div class="no-print-bar-actions">
                <a href="/?page=tagihan_riwayat" class="btn-action-close btn-invoice-kembali-bar">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
                <button type="button" onclick="window.print()" class="btn-action-print">
                    <i class="fa-solid fa-print"></i> Cetak / Simpan PDF
                </button>
            </div>
        </div>

        <!-- KOP SURAT RESMI -->
        <div class="laporan-kop-surat">
            <h1 class="laporan-kop-title">SITEMAN - KOS</h1>
            <p class="laporan-kop-sub">Sistem Manajemen Operasional &amp; Hunian Kos Terpadu</p>
            <div class="laporan-kop-heading"><?= htmlspecialchars($judul_dokumen); ?></div>
        </div>

        <!-- KOTAK INFORMASI TRANSAKSI -->
        <div class="laporan-meta-card">
            <div class="laporan-meta-col">
                <span><strong>Nomor Invoice:</strong> <?= htmlspecialchars($invoice['nomor_invoice']); ?></span>
                <span><strong>Ditagihkan Kepada:</strong> <?= htmlspecialchars($nama_penghuni); ?> (Kamar <?= htmlspecialchars($invoice['no_kamar']); ?>)</span>
                <span><strong>Periode Tagihan:</strong> <?= htmlspecialchars($invoice['periode_sewa']); ?></span>
            </div>
            <div class="laporan-meta-col invoice-meta-col-right">
                <span><strong>Tanggal Terbit:</strong> <?= htmlspecialchars($invoice['tanggal_terbit']); ?></span>
                <span><strong>Jatuh Tempo:</strong> <?= htmlspecialchars($invoice['jatuh_tempo']); ?></span>
                <span><strong>Waktu Pembayaran:</strong> <?= htmlspecialchars($invoice['tanggal_bayar']); ?></span>
                <span class="invoice-status-wrap">
                    <span class="badge-invoice-lunas-pill">
                        <i class="fa-solid fa-circle-check"></i> STATUS: LUNAS
                    </span>
                </span>
            </div>
        </div>

        <!-- TABEL RINCIAN TAGIHAN -->
        <table class="laporan-data-table">
            <thead>
                <tr>
                    <th class="invoice-col-no">No</th>
                    <th>Deskripsi Pembayaran</th>
                    <th>Keterangan Periode</th>
                    <th class="invoice-col-nominal">Nominal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="invoice-cell-center">1</td>
                    <td><strong><?= htmlspecialchars($invoice['judul']); ?></strong></td>
                    <td><?= htmlspecialchars($invoice['periode_sewa']); ?></td>
                    <td class="invoice-cell-nominal">Rp <?= number_format($invoice['nominal'], 0, ',', '.'); ?></td>
                </tr>
                <tr class="laporan-row-saldo-bersih">
                    <td colspan="3" class="invoice-total-label-cell">TOTAL PEMBAYARAN DITERIMA</td>
                    <td class="invoice-total-val-cell">Rp <?= number_format($invoice['nominal'], 0, ',', '.'); ?></td>
                </tr>
            </tbody>
        </table>

        <!-- CATATAN RESMI -->
        <div class="invoice-footer-note">
            <strong>Catatan:</strong> Dokumen ini merupakan bukti pembayaran sah yang diterbitkan secara elektronik oleh sistem <strong>SITEMAN - KOS</strong> dan telah diverifikasi lunas.
        </div>

        <!-- KOLOM PENGESAHAN -->
        <div class="laporan-ttd-wrapper">
            <div class="laporan-ttd-box">
                <p class="invoice-ttd-date">Malang, <?= htmlspecialchars($invoice['tanggal']); ?></p>
                <p class="invoice-ttd-jabatan"><?= htmlspecialchars($jabatan_pengesah); ?></p>
                <p class="laporan-ttd-nama"><?= htmlspecialchars($nama_pengesah); ?></p>
                <p class="laporan-ttd-role">Terverifikasi Otomatis oleh Sistem</p>
            </div>
        </div>
    </div>

</body>
</html>