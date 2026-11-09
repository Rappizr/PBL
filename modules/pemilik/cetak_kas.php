<?php
/**
 * LAPORAN ARUS KAS SEWA (KHUSUS PEMILIK)
 * Lokasi: modules/pemilik/cetak_kas.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi Ganda Hak Akses Pemilik
$currentUser =$_SESSION['user'] ?? [];
if (($currentUser['role'] ?? '') !== 'pemilik') {
    http_response_code(403);
    die("Akses ditolak: Halaman laporan kas sewa hanya milik Pemilik Kos.");
}

global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {$possible_paths = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../../../config/database.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/database.php'
    ];
    foreach ($possible_paths as$p) {
        if (file_exists($p)) {
            require_once $p;
            if (isset($pdo) && ($pdo instanceof PDO)) break;
        }
    }
}

// Parameter Filter
$periode = trim($_GET['periode'] ?? 'semua');
$bulan   = (int)($_GET['bulan'] ?? date('n'));
$tahun   = (int)($_GET['tahun'] ?? date('Y'));

$nama_bulan_arr = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

if ($periode === 'bulanan') {$label_periode = ($nama_bulan_arr[$bulan] ?? 'Bulan ' . $bulan) . ' ' .$tahun;
} elseif ($periode === 'tahunan') {
    $label_periode = 'Tahun ' .$tahun;
} else {
    $label_periode = 'Semua Periode (Seluruh Tahun)';
}

$sql = "
    SELECT 
        p.id,
        p.no_kwitansi,
        COALESCE(pg.nama, 'Penghuni') AS nama,
        COALESCE(k.no_kamar, '-') AS kamar,
        p.jumlah_bayar AS nominal,
        COALESCE(p.metode_bayar, 'transfer') AS metode_bayar,
        p.tanggal_bayar
    FROM pembayaran p
    JOIN tagihan t ON p.tagihan_id = t.id
    LEFT JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
    LEFT JOIN penghuni pg ON pk.penghuni_id = pg.id
    LEFT JOIN kamar k ON pk.kamar_id = k.id
    WHERE LOWER(TRIM(p.status_verifikasi)) IN ('diterima', 'terverifikasi', 'lunas')
      AND t.kategori_iuran_id IS NULL
";

$params = [];
if ($periode === 'bulanan') {$sql .= " AND EXTRACT(MONTH FROM p.tanggal_bayar) = :bulan AND EXTRACT(YEAR FROM p.tanggal_bayar) = :tahun ";
    $params[':bulan'] =$bulan;
    $params[':tahun'] =$tahun;
} elseif ($periode === 'tahunan') {$sql .= " AND EXTRACT(YEAR FROM p.tanggal_bayar) = :tahun ";
    $params[':tahun'] =$tahun;
}

$sql .= " ORDER BY p.tanggal_bayar DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data_transaksi =$stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Arus Kas Sewa - <?= htmlspecialchars($label_periode); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="laporan-print-body">

    <div class="laporan-page-container">
        <!-- BAR TOMBOL PRATINJAU -->
        <div class="no-print-bar">
            <div><strong>Pratinjau PDF:</strong> Laporan Arus Kas Sewa (<?= htmlspecialchars($label_periode); ?>)</div>
            <div class="no-print-bar-actions">
                <button type="button" onclick="window.print()" class="btn-action-print">
                    <i class="fa-solid fa-print"></i> Cetak / Simpan PDF
                </button>
                <button type="button" onclick="window.close()" class="btn-action-close">Tutup</button>
            </div>
        </div>

        <!-- KOP LAPORAN RESMI -->
        <div class="laporan-kop-surat">
            <h1 class="laporan-kop-title">SITEMAN - KOS</h1>
            <p class="laporan-kop-sub">Sistem Informasi Manajemen &amp; Pengelolaan Operasional Kos</p>
            <div class="laporan-kop-heading">
                LAPORAN ARUS KAS PEMASUKAN SEWA KAMAR (INTERNAL PEMILIK)
            </div>
        </div>

        <!-- KARTU INFORMASI PERIODE -->
        <div class="laporan-meta-card">
            <div class="laporan-meta-col">
                <span><strong>Periode Laporan:</strong> <?= htmlspecialchars($label_periode); ?></span>
                <span><strong>Total Transaksi:</strong> <?= count($data_transaksi); ?> baris data</span>
            </div>
            <div class="laporan-meta-col" style="text-align: right;">
                <span><strong>Dicetak Oleh:</strong> <?= htmlspecialchars($currentUser['nama'] ?? 'Pemilik'); ?> (Pemilik Kos)</span>
                <span><strong>Waktu Cetak:</strong> <?= date('d/m/Y H:i'); ?> WIB</span>
            </div>
        </div>

        <!-- TABEL DATA ARUS KAS -->
        <table class="laporan-data-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 40px;">No</th>
                    <th style="width: 140px;">No Kwitansi</th>
                    <th>Nama Penghuni</th>
                    <th class="text-center" style="width: 70px;">Kamar</th>
                    <th style="width: 130px;">Waktu Bayar</th>
                    <th class="text-center" style="width: 80px;">Metode</th>
                    <th class="text-right" style="width: 130px;">Nominal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data_transaksi)): ?>
                    <tr>
                        <td colspan="7" class="laporan-empty-cell">
                            Tidak ada riwayat transaksi sewa pada periode <?= htmlspecialchars($label_periode); ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    $grand_total = 0;
                    foreach ($data_transaksi as$row): 
                        $nominal = (float)($row['nominal'] ?? 0);
                        $grand_total +=$nominal;
                    ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['no_kwitansi'] ?? '-'); ?></td>
                            <td><strong><?= htmlspecialchars($row['nama']); ?></strong></td>
                            <td class="text-center"><?= htmlspecialchars($row['kamar']); ?></td>
                            <td><?= date('d-m-Y H:i', strtotime($row['tanggal_bayar'])); ?></td>
                            <td class="text-center"><?= strtoupper($row['metode_bayar']); ?></td>
                            <td class="text-right">Rp <?= number_format($nominal, 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="laporan-row-total">
                        <td colspan="6" class="text-right">TOTAL PEMASUKAN SEWA:</td>
                        <td class="text-right">Rp <?= number_format($grand_total, 0, ',', '.'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- AREA TANDA TANGAN -->
        <div class="laporan-ttd-wrapper">
            <div class="laporan-ttd-box">
                <p>Malang, <?= date('d') . ' ' . ($nama_bulan_arr[(int)date('n')] ?? '') . ' ' . date('Y'); ?></p>
                <p class="laporan-ttd-nama"><strong>( <?= htmlspecialchars($currentUser['nama'] ?? 'Pemilik Kos'); ?> )</strong></p>
                <p class="laporan-ttd-role">Pemilik Kos</p>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>