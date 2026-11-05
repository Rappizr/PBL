<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

$current_page = $_GET['page'] ?? 'manajemen_kamar';
$modal_aktif  = $_GET['modal'] ?? '';
$error_simpan = $_SESSION['error_simpan'] ?? '';
unset($_SESSION['error_simpan']);

// 1. Include Proses Backend POST
if (file_exists(__DIR__ . '/handler/kamar_handlers.php')) {
    require_once __DIR__ . '/handler/kamar_handlers.php';
} else {
    require_once __DIR__ . '/handlers/kamar_handlers.php';
}

// 2. Ambil Data Skema Sewa
$list_skema = $pdo->query("SELECT * FROM skema_sewa ORDER BY tipe_periode DESC, durasi_nilai ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. Ambil Data Kamar Realtime Sesuai Skema Baru
$queryKamar = "
    SELECT 
        k.id AS kamar_id,
        k.no_kamar,
        k.status_kamar,
        COALESCE(p.nama, '') AS nama_penghuni,
        COALESCE(p.no_wa, '') AS no_wa,
        COALESCE(s.nominal, 0) AS harga_sewa,
        pk.tanggal_masuk,
        COALESCE(t.status_tagihan, 'lunas') AS status_bayar
    FROM kamar k
    LEFT JOIN penghuni_kamar pk ON k.id = pk.kamar_id
    LEFT JOIN penghuni p ON pk.penghuni_id = p.id
    LEFT JOIN skema_sewa s ON pk.skema_id = s.id
    LEFT JOIN LATERAL (
        SELECT status_tagihan 
        FROM tagihan 
        WHERE penghuni_kamar_id = pk.id 
        ORDER BY tanggal_jatuh_tempo DESC 
        LIMIT 1
    ) t ON true
    ORDER BY k.no_kamar::INTEGER ASC
";
$daftar_kamar = $pdo->query($queryKamar)->fetchAll(PDO::FETCH_ASSOC);

// 4. Data Kamar Terpilih untuk Modal Detail
$nomor_pilih = $_GET['nomor'] ?? '';
$kamar_terpilih = null;

if ($modal_aktif === 'detail_kamar' && $nomor_pilih !== '') {
    $stmtDetail = $pdo->prepare("
        SELECT 
            k.no_kamar AS nomor,
            COALESCE(s.nominal, 0) AS harga_sewa,
            k.status_kamar,
            COALESCE(p.nama, '') AS nama,
            COALESCE(p.no_wa, '') AS no_wa,
            COALESCE(p.email, '') AS email,
            COALESCE(p.kontak_wali, '') AS kontak_wali,
            COALESCE(p.foto_ktp, '') AS foto_ktp,
            COALESCE(p.is_ketua_kos, false) AS is_ketua_kos,
            COALESCE(p.pin, '123456') AS pin,
            COALESCE(pk.skema_id, s.id) AS skema_id,
            COALESCE(s.nama_skema, 'Bulanan') AS durasi_sewa,
            COALESCE(pk.tanggal_masuk, CURRENT_DATE) AS tgl_mulai,
            COALESCE(pk.tanggal_selesai, CURRENT_DATE) AS tgl_selesai,
            COALESCE(t.status_tagihan, 'belum_bayar') AS status_bayar
        FROM kamar k
        LEFT JOIN penghuni_kamar pk ON k.id = pk.kamar_id
        LEFT JOIN penghuni p ON pk.penghuni_id = p.id
        LEFT JOIN skema_sewa s ON pk.skema_id = s.id
        LEFT JOIN LATERAL (
            SELECT status_tagihan 
            FROM tagihan 
            WHERE penghuni_kamar_id = pk.id 
            ORDER BY tanggal_jatuh_tempo DESC 
            LIMIT 1
        ) t ON true
        WHERE k.no_kamar = :no
        LIMIT 1
    ");
    $stmtDetail->execute([':no' => $nomor_pilih]);
    $kamar_terpilih = $stmtDetail->fetch(PDO::FETCH_ASSOC);

    if ($kamar_terpilih) {
        $kamar_terpilih['tgl_selesai_format'] = date('d / m / Y', strtotime($kamar_terpilih['tgl_selesai']));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Manajemen Kamar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="kamar-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <div class="kamar-title-card">
                <h2>Manajemen Kamar &amp; Penghuni</h2>
                <a href="/?page=dashboard_pemilik" class="btn-kembali-banner">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>

            <?php if ($error_simpan !== ''): ?>
                <div class="alert-error alert-error-kamar"><?= htmlspecialchars($error_simpan); ?></div>
            <?php endif; ?>

            <div class="kamar-actions">
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_kamar" class="btn-add-outline">
                    <span class="plus-icon">+</span> Tambah Kamar
                </a>
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_penghuni" class="btn-add-outline">
                    <span class="plus-icon">+</span> Tambah Penghuni
                </a>
                <a href="/?page=<?= urlencode($current_page) ?>&modal=kelola_skema" class="btn-add-outline btn-skema-highlight">
                    <i class="fa-solid fa-sliders"></i> Skema Sewa
                </a>
            </div>

            <div class="kamar-grid">
                <?php if (empty($daftar_kamar)): ?>
                    <p class="kamar-empty-state-mobile">Belum ada kamar di database.</p>
                <?php else: ?>
                    <?php foreach ($daftar_kamar as $kamar): ?>
                        <?php 
                            $nama_penghuni = !empty($kamar['nama_penghuni']) ? $kamar['nama_penghuni'] : 'Kosong';
                            $is_kosong = ($kamar['status_kamar'] === 'kosong' || empty($kamar['nama_penghuni']));
                            $status_bayar_lower = strtolower($kamar['status_bayar'] ?? 'lunas');
                        ?>
                        <a href="/?page=<?= urlencode($current_page) ?>&modal=detail_kamar&nomor=<?= urlencode($kamar['no_kamar']) ?>" 
                           class="kamar-card">
                            
                            <?php if ($is_kosong): ?>
                                <span class="badge-status badge-kosong">Kosong</span>
                            <?php elseif ($status_bayar_lower === 'lunas'): ?>
                                <span class="badge-status badge-lunas">Lunas</span>
                            <?php else: ?>
                                <span class="badge-status badge-belum">Belum</span>
                            <?php endif; ?>

                            <div class="kamar-nomor"><?= htmlspecialchars($kamar['no_kamar']) ?></div>
                            <div class="kamar-nama"><?= htmlspecialchars($nama_penghuni) ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Manajemen Kamar &amp; Penghuni</h2>
            <p class="desktop-header-subtitle">Manajemen kamar dan penghuni secara realtime</p>

            <?php if ($error_simpan !== ''): ?>
                <div class="alert-error alert-error-kamar-desktop"><?= htmlspecialchars($error_simpan); ?></div>
            <?php endif; ?>

            <h3 class="desktop-kamar-section-label">Data terbaru</h3>

            <div class="desktop-kamar-top-actions">
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_kamar" class="kamar-btn-pill-action">
                    <i class="fa-solid fa-plus"></i> Tambah Kamar
                </a>
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_penghuni" class="kamar-btn-pill-action">
                    <i class="fa-solid fa-plus"></i> Tambah Penghuni
                </a>
                <a href="/?page=<?= urlencode($current_page) ?>&modal=kelola_skema" class="kamar-btn-pill-action kamar-btn-skema-action">
                    <i class="fa-solid fa-sliders"></i> Skema Sewa
                </a>
            </div>

            <div class="kamar-desktop-panel">
                <div class="kamar-desktop-grid-white">
                    <?php if (empty($daftar_kamar)): ?>
                        <p class="kamar-empty-state-desktop">Belum ada kamar tersimpan.</p>
                    <?php else: ?>
                        <?php foreach ($daftar_kamar as $kamar): ?>
                            <?php 
                                $nama_penghuni = !empty($kamar['nama_penghuni']) ? $kamar['nama_penghuni'] : 'Kosong';
                                $is_kosong = ($kamar['status_kamar'] === 'kosong' || empty($kamar['nama_penghuni']));
                                $status_bayar_lower = strtolower($kamar['status_bayar'] ?? 'lunas');
                            ?>
                            <a href="/?page=<?= urlencode($current_page) ?>&modal=detail_kamar&nomor=<?= urlencode($kamar['no_kamar']) ?>" 
                               class="kamar-card-white">
                                
                                <?php if ($is_kosong): ?>
                                    <span class="badge-kamar-status badge-kamar-kosong">Kosong</span>
                                <?php elseif ($status_bayar_lower === 'lunas'): ?>
                                    <span class="badge-kamar-status badge-kamar-lunas">Lunas</span>
                                <?php else: ?>
                                    <span class="badge-kamar-status badge-kamar-belum">Belum</span>
                                <?php endif; ?>

                                <div class="kamar-card-number"><?= htmlspecialchars($kamar['no_kamar']) ?></div>
                                <div class="kamar-card-resident"><?= htmlspecialchars($nama_penghuni) ?></div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="kamar-desktop-bottom-bar">
                    <div class="kamar-legend-row">
                        <span class="legend-item"><span class="legend-color-box color-lunas"></span> Lunas</span>
                        <span class="legend-item"><span class="legend-color-box color-belum"></span> Belum bayar</span>
                        <span class="legend-item"><span class="legend-color-box color-kosong"></span> Kosong</span>
                    </div>

                    <div class="kamar-pagination-row">
                        <button type="button" class="kamar-page-btn"><i class="fa-solid fa-chevron-left"></i></button>
                        <span class="kamar-page-info">1/1</span>
                        <button type="button" class="kamar-page-btn"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- LOAD MODAL-MODAL TERPISAH -->
    <?php 
        require_once __DIR__ . '/modals/modal_tambah_kamar.php';
        require_once __DIR__ . '/modals/modal_kelola_skema.php';
        require_once __DIR__ . '/modals/modal_tambah_penghuni.php';
        require_once __DIR__ . '/modals/modal_detail_kamar.php';
    ?>

</body>
</html>