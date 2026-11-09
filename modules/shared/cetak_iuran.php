<?php
/**
 * LAPORAN BUKU KAS IURAN BERSAMA (BISA DIAKSES SEMUA ROLE)
 * Lokasi: modules/shared/cetak_iuran.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = $_SESSION['user'] ?? [];
if (empty($currentUser)) {
    header("Location: /?page=login");
    exit;
}

global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $possible_paths = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../../../config/database.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/database.php'
    ];
    foreach ($possible_paths as $p) {
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

if ($periode === 'bulanan') {
    $label_periode = ($nama_bulan_arr[$bulan] ?? 'Bulan ' . $bulan) . ' ' . $tahun;
} elseif ($periode === 'tahunan') {
    $label_periode = 'Tahun ' . $tahun;
} else {
    $label_periode = 'Semua Periode (Seluruh Tahun)';
}

$sql = "
    SELECT 
        bk.id,
        bk.keterangan,
        bk.tipe_transaksi,
        bk.nominal,
        bk.tanggal_transaksi
    FROM buku_kas bk
    WHERE 1=1
";

$params = [];
if ($periode === 'bulanan') {
    $sql .= " AND EXTRACT(MONTH FROM bk.tanggal_transaksi) = :bulan AND EXTRACT(YEAR FROM bk.tanggal_transaksi) = :tahun ";
    $params[':bulan'] = $bulan;
    $params[':tahun'] = $tahun;
} elseif ($periode === 'tahunan') {
    $sql .= " AND EXTRACT(YEAR FROM bk.tanggal_transaksi) = :tahun ";
    $params[':tahun'] = $tahun;
}

$sql .= " ORDER BY bk.tanggal_transaksi DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data_transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Buku Kas Iuran - <?= htmlspecialchars($label_periode); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="laporan-print-body">

    <div class="laporan-page-container">
        <!-- BAR TOMBOL PRATINJAU -->
        <div class="no-print-bar">
            <div><strong>Pratinjau PDF:</strong> Laporan Buku Kas Iuran (<?= htmlspecialchars($label_periode); ?>)</div>
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
                LAPORAN REKAPITULASI BUKU KAS IURAN BERSAMA
            </div>
        </div>

        <!-- KARTU INFORMASI PERIODE -->
        <div class="laporan-meta-card">
            <div class="laporan-meta-col">
                <span><strong>Periode Laporan:</strong> <?= htmlspecialchars($label_periode); ?></span>
                <span><strong>Total Transaksi:</strong> <?= count($data_transaksi); ?> baris data</span>
            </div>
            <div class="laporan-meta-col" style="text-align: right;">
                <span><strong>Dicetak Oleh:</strong> <?= htmlspecialchars($currentUser['nama'] ?? 'Pengguna'); ?> (<?= ucfirst($currentUser['role'] ?? 'Penghuni'); ?>)</span>
                <span><strong>Waktu Cetak:</strong> <?= date('d/m/Y H:i'); ?> WIB</span>
            </div>
        </div>

        <!-- TABEL DATA BUKU KAS -->
        <table class="laporan-data-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 40px;">No</th>
                    <th>Keterangan Transaksi</th>
                    <th class="text-center" style="width: 90px;">Jenis</th>
                    <th style="width: 140px;">Waktu Transaksi</th>
                    <th class="text-right" style="width: 130px;">Pemasukan</th>
                    <th class="text-right" style="width: 130px;">Pengeluaran</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data_transaksi)): ?>
                    <tr>
                        <td colspan="6" class="laporan-empty-cell">
                            Tidak ada catatan transaksi iuran pada periode <?= htmlspecialchars($label_periode); ?>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    $tot_masuk = 0;
                    $tot_keluar = 0;

                    foreach ($data_transaksi as $row): 
                        $nominal = (float)($row['nominal'] ?? 0);
                        $is_masuk = (strtolower($row['tipe_transaksi']) === 'pemasukan');
                        if ($is_masuk) {
                            $tot_masuk += $nominal;
                        } else {
                            $tot_keluar += $nominal;
                        }
                    ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['keterangan']); ?></td>
                            <td class="text-center"><?= ucfirst($row['tipe_transaksi']); ?></td>
                            <td><?= date('d-m-Y H:i', strtotime($row['tanggal_transaksi'])); ?></td>
                            <td class="text-right laporan-text-masuk"><?= $is_masuk ? 'Rp ' . number_format($nominal, 0, ',', '.') : '-'; ?></td>
                            <td class="text-right laporan-text-keluar"><?= !$is_masuk ? 'Rp ' . number_format($nominal, 0, ',', '.') : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="laporan-row-total">
                        <td colspan="4" class="text-right">TOTAL TRANSAKSI:</td>
                        <td class="text-right laporan-text-masuk">Rp <?= number_format($tot_masuk, 0, ',', '.'); ?></td>
                        <td class="text-right laporan-text-keluar">Rp <?= number_format($tot_keluar, 0, ',', '.'); ?></td>
                    </tr>
                    <tr class="laporan-row-saldo-bersih">
                        <td colspan="4" class="text-right">SISA SALDO KAS BERSIH:</td>
                        <td colspan="2" class="text-right laporan-saldo-val">
                            Rp <?= number_format($tot_masuk - $tot_keluar, 0, ',', '.'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- AREA TANDA TANGAN -->
        <div class="laporan-ttd-wrapper">
            <div class="laporan-ttd-box">
                <p>Malang, <?= date('d') . ' ' . ($nama_bulan_arr[(int)date('n')] ?? '') . ' ' . date('Y'); ?></p>
                <p class="laporan-ttd-nama"><strong>( <?= htmlspecialchars($currentUser['nama'] ?? 'Penanggung Jawab'); ?> )</strong></p>
                <p class="laporan-ttd-role">Pengurus Operasional Kos</p>
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