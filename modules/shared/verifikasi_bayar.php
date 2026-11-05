<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan koneksi $pdo tersedia
if (!isset($pdo)) {
    require_once __DIR__ . '/../../config/database.php'; 
}

// Panggil file handler yang berada satu folder di dalam 'handler'
require_once __DIR__ . '/handler/verifBayar.php';

// FILTER RENTANG TANGGAL (format: YYYY-MM-DD to YYYY-MM-DD)
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

// AMBIL DATA PENDING DARI DATABASE POSTGRESQL
$daftar_verifikasi = [];
if (isset($pdo)) {
    try {
        $sql = "
            SELECT 
                p.id AS pembayaran_id,
                p.jumlah_bayar,
                p.tanggal_bayar,
                p.bukti_pembayaran AS foto,
                p.metode_bayar,
                pg.nama,
                pg.no_wa,
                k.no_kamar AS kamar
            FROM pembayaran p
            JOIN tagihan t ON p.tagihan_id = t.id
            JOIN penghuni_kamar pk ON t.penghuni_kamar_id = pk.id
            JOIN penghuni pg ON pk.penghuni_id = pg.id
            JOIN kamar k ON pk.kamar_id = k.id
            WHERE p.status_verifikasi = 'pending'
        ";

        $params = [];
        if ($tgl_mulai !== '') {
            $sql .= " AND DATE(p.tanggal_bayar) >= :tgl_mulai AND DATE(p.tanggal_bayar) <= :tgl_selesai";
            $params[':tgl_mulai']   = $tgl_mulai;
            $params[':tgl_selesai'] = $tgl_selesai;
        }

        $sql .= " ORDER BY p.tanggal_bayar DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $daftar_verifikasi = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $daftar_verifikasi = [];
    }
}

$is_filtered  = ($date_range !== '');
$current_page = $_GET['page'] ?? 'verifikasi_pembayaran';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Verifikasi Pembayaran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="verifikasi-body">

    <?php if (!empty($error_verif)): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px; text-align: center; font-weight: 600;">
            <?= htmlspecialchars($error_verif); ?>
        </div>
    <?php endif; ?>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Verifikasi Pembayaran</h2>
            <p class="desktop-header-subtitle" style="margin-bottom: 16px;">Daftar konfirmasi bukti transfer dari penghuni kos</p>

            <!-- Filter tanggal desktop -->
            <form method="GET" action="" class="filter-range-form-desktop" style="max-width: 250px; width: 100%; margin-bottom: 30px;">
                <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                <div class="flatpickr-box-wrapper" style="width: 100%;">
                    <i class="fa-solid fa-filter date-icon"></i>
                    <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                           placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly style="width: 100%;">
                    <?php if ($is_filtered): ?>
                        <a href="/?page=<?= urlencode($current_page); ?>" class="btn-reset-range" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="card-list" style="max-width: 650px; margin-top: 30px;">
                <?php if (empty($daftar_verifikasi)): ?>
                    <div class="empty-state-card" style="width: 100%;">
                        <i class="fa-solid fa-folder-open"></i>
                        <span>Tidak ada data verifikasi pembayaran yang pending</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($daftar_verifikasi as $v): ?>
                        <div class="verifikasi-card">
                            <div class="verifikasi-card-body">
                                <div class="bukti-wrapper">
                                    <?php 
                                        $foto_path = !empty($v['foto']) ? '/' . ltrim($v['foto'], '/') : 'https://picsum.photos/200';
                                    ?>
                                    <img src="<?= htmlspecialchars($foto_path); ?>" alt="Bukti Transfer" class="bukti-img">
                                    <a href="<?= htmlspecialchars($foto_path); ?>" target="_blank" class="btn-zoom">
                                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                                    </a>
                                </div>
                                <div class="detail-wrapper">
                                    <div class="user-info-row">
                                        <span class="nama-penghuni"><?= htmlspecialchars($v['nama']); ?></span>
                                        <span class="badge-kamar">Kamar <?= htmlspecialchars($v['kamar']); ?></span>
                                    </div>
                                    <div class="nominal-text">Rp. <?= number_format($v['jumlah_bayar'], 0, ',', '.'); ?></div>
                                    <div class="metode-tanggal"><?= ucfirst($v['metode_bayar']); ?> ~ <?= htmlspecialchars($v['tanggal_bayar']); ?></div>
                                </div>
                            </div>
                            <div class="verifikasi-card-footer">
                                <a href="https://wa.me/<?= preg_replace('/^0/', '62', $v['no_wa']); ?>" target="_blank" class="btn-action btn-chat">
                                    <i class="fa-brands fa-whatsapp"></i> Chat
                                </a>
                                <!-- Tombol Tolak -->
                                <form action="" method="POST" style="display:inline;">
                                    <input type="hidden" name="pembayaran_id" value="<?= $v['pembayaran_id']; ?>">
                                    <input type="hidden" name="aksi_verifikasi" value="tolak">
                                    <button type="submit" class="btn-action btn-decline" onclick="return confirm('Tolak pembayaran ini?')">
                                        <i class="fa-solid fa-circle-xmark"></i> Tolak
                                    </button>
                                </form>
                                <!-- Tombol Terima -->
                                <form action="" method="POST" style="display:inline;">
                                    <input type="hidden" name="pembayaran_id" value="<?= $v['pembayaran_id']; ?>">
                                    <input type="hidden" name="aksi_verifikasi" value="terima">
                                    <button type="submit" class="btn-action btn-approve" onclick="return confirm('Terima pembayaran ini dan catat ke buku kas?')">
                                        <i class="fa-solid fa-circle-check"></i> Terima
                                    </button>
                                </form>
                            </div>
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