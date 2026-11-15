<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../shared/handler/sewa_reminder_helper.php';

$current_page = $_GET['page'] ?? 'manajemen_kamar';
$modal_aktif  = $_GET['modal'] ?? '';
$error_simpan = $_SESSION['error_simpan'] ?? '';
$pesan_sukses = $_SESSION['pesan_sukses'] ?? '';
unset($_SESSION['error_simpan'], $_SESSION['pesan_sukses']);

// 1. Include Proses Backend POST
if (file_exists(__DIR__ . '/handler/kamar_handlers.php')) {
    require_once __DIR__ . '/handler/kamar_handlers.php';
} else {
    require_once __DIR__ . '/handlers/kamar_handlers.php';
}

// 2. Evaluasi Status Jatuh Tempo Sewa & Auto-Freeze Akun yang Melewati Masa Tenggang 7 Hari
$daftar_notif_sewa = evaluasiStatusSewaSemuaKamar($pdo);
$map_notif_kamar   = [];
foreach ($daftar_notif_sewa as $itemNotif) {
    $map_notif_kamar[(string)$itemNotif['no_kamar']] = $itemNotif;
}

// 3. Ambil Data Skema Sewa
$list_skema = $pdo->query("SELECT * FROM skema_sewa ORDER BY tipe_periode DESC, durasi_nilai ASC")->fetchAll(PDO::FETCH_ASSOC);

// 4. Ambil Data Kamar Realtime
$semua_kamar = $pdo->query("
    SELECT 
        k.id AS kamar_id,
        k.no_kamar,
        k.status_kamar,
        p.id AS penghuni_id,
        COALESCE(p.nama, '') AS nama_penghuni,
        COALESCE(p.no_wa, '') AS no_wa,
        LOWER(TRIM(COALESCE(p.status_akun, 'aktif'))) AS status_akun,
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
          AND kategori_iuran_id IS NULL
        ORDER BY tanggal_jatuh_tempo DESC 
        LIMIT 1
    ) t ON true
    ORDER BY k.no_kamar::INTEGER ASC
")->fetchAll(PDO::FETCH_ASSOC);

$total_kamar         = count($semua_kamar);
$daftar_kamar        = $semua_kamar;
$daftar_kamar_mobile = $semua_kamar;

$daftar_kamar_kosong = array_values(array_filter($semua_kamar, function ($kmr) {
    return ($kmr['status_kamar'] === 'kosong' || empty($kmr['nama_penghuni']));
}));

$stmtCekKetua = $pdo->query("SELECT id, nama FROM penghuni WHERE is_ketua_kos = true LIMIT 1");
$ketuaAktif   = $stmtCekKetua ? $stmtCekKetua->fetch(PDO::FETCH_ASSOC) : null;

// --- A. Pagination Desktop (12 Kamar per Halaman) ---
$limit_kamar   = 12;
$halaman_aktif = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$total_halaman = max(1, (int) ceil($total_kamar / $limit_kamar));
if ($halaman_aktif > $total_halaman) $halaman_aktif = $total_halaman;

// --- B. Pagination Mobile (8 Kamar per Halaman) ---
$limit_kamar_mobile   = 8;
$halaman_aktif_mobile = isset($_GET['halaman_m']) ? max(1, (int)$_GET['halaman_m']) : 1;
$total_halaman_mobile = max(1, (int) ceil($total_kamar / $limit_kamar_mobile));
if ($halaman_aktif_mobile > $total_halaman_mobile) $halaman_aktif_mobile = $total_halaman_mobile;

// 5. Data Kamar Terpilih untuk Modal Detail
$nomor_pilih    = $_GET['nomor'] ?? '';
$kamar_terpilih = null;

if ($modal_aktif === 'detail_kamar' && $nomor_pilih !== '') {
    $stmtDetail = $pdo->prepare("
        SELECT 
            k.no_kamar AS nomor,
            COALESCE(s.nominal, 0) AS harga_sewa,
            k.status_kamar,
            p.id AS penghuni_id,
            COALESCE(p.nama, '') AS nama,
            COALESCE(p.no_wa, '') AS no_wa,
            COALESCE(p.email, '') AS email,
            COALESCE(p.kontak_wali, '') AS kontak_wali,
            COALESCE(p.foto_ktp, '') AS foto_ktp,
            COALESCE(p.is_ketua_kos, false) AS is_ketua_kos,
            COALESCE(p.pin, '123456') AS pin,
            COALESCE(p.status_akun, 'aktif') AS status_akun,
            p.tanggal_janji_bayar,
            p.catatan_tunggakan,
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
              AND kategori_iuran_id IS NULL
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
        $kamar_terpilih['info_sewa']          = $map_notif_kamar[(string)$kamar_terpilih['nomor']] ?? null;
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
    <link rel="stylesheet" href="/css/global.css?v=<?= filemtime(__DIR__ . '/../../public/css/global.css'); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= filemtime(__DIR__ . '/../../public/css/modules.css'); ?>">
</head>

<body class="dashboard-body">

    <!-- FLOATING TOAST NOTIFIKASI -->
    <div class="toast-floating-container">
        <?php if (!empty($pesan_sukses)): ?>
        <div class="toast-alert toast-success" id="toastAlertBox">
            <div class="toast-icon"><i class="fa-solid fa-check"></i></div>
            <div class="toast-content">
                <h5 class="toast-title">Berhasil</h5>
                <p class="toast-message"><?= htmlspecialchars($pesan_sukses); ?></p>
            </div>
            <button type="button" class="toast-close" onclick="closeToast()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <?php endif; ?>
    </div>

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

            <!-- NOTIFIKASI PENGINGAT SEWA & FREEZE (MOBILE) -->
            <?php if (!empty($daftar_notif_sewa)): ?>
            <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px;">
                <?php foreach ($daftar_notif_sewa as $notif): ?>
                <?php $isFrz = ($notif['fase'] === 'freeze'); ?>
                <div style="background-color: <?= $isFrz ? '#fee2e2' : '#fef3c7'; ?>; border: 1.5px solid <?= $isFrz ? '#ef4444' : '#f59e0b'; ?>; color: <?= $isFrz ? '#991b1b' : '#92400e'; ?>; border-radius: 16px; padding: 12px 14px; box-shadow: 3px 4px 0px #c8924b;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 4px;">
                        <strong style="font-size: 13px;">
                            <i class="fa-solid <?= $isFrz ? 'fa-lock' : 'fa-bell'; ?>"></i>
                            Kamar <?= htmlspecialchars(sprintf('%02d', (int)$notif['no_kamar'])); ?> &bull; <?= htmlspecialchars($notif['nama_penghuni']); ?>
                        </strong>
                        <span style="font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 999px; background: #ffffff; color: <?= $isFrz ? '#dc2626' : '#b45309'; ?>;">
                            <?= htmlspecialchars($notif['label_status']); ?>
                        </span>
                    </div>
                    <p style="margin: 0 0 8px 0; font-size: 11.5px; line-height: 1.4;">
                        <?= htmlspecialchars($notif['pesan_ringkas']); ?> (Total: <strong>Rp <?= number_format((float)$notif['total_tunggakan'], 0, ',', '.'); ?></strong>)
                    </p>
                    <div style="display: flex; gap: 8px;">
                        <a href="<?= htmlspecialchars($notif['wa_pengingat_url']); ?>" target="_blank"
                           style="flex: 1; text-align: center; background: #25D366; color: #ffffff; font-size: 11.5px; font-weight: 800; padding: 6px 10px; border-radius: 999px; text-decoration: none;">
                            <i class="fa-brands fa-whatsapp"></i> Ingatkan WA
                        </a>
                        <a href="/?page=<?= urlencode($current_page) ?>&modal=detail_kamar&nomor=<?= urlencode($notif['no_kamar']) ?>"
                           style="flex: 1; text-align: center; background: #ffffff; color: #1a4d55; font-size: 11.5px; font-weight: 800; padding: 6px 10px; border-radius: 999px; text-decoration: none;">
                            <i class="fa-solid fa-sliders"></i> Kelola
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="kamar-actions">
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_kamar" onclick="bukaModalInstan(event, 'modalTambahKamar', 'tambah_kamar')" class="btn-add-outline">
                    <span class="plus-icon">+</span> Tambah Kamar
                </a>
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_penghuni" onclick="bukaModalInstan(event, 'modalTambahPenghuni', 'tambah_penghuni')" class="btn-add-outline">
                    <span class="plus-icon">+</span> Tambah Penghuni
                </a>
                <a href="/?page=<?= urlencode($current_page) ?>&modal=kelola_skema" onclick="bukaModalInstan(event, 'modalKelolaSkema', 'kelola_skema')" class="btn-add-outline btn-skema-highlight">
                    <i class="fa-solid fa-sliders"></i> Skema Sewa
                </a>
            </div>

            <div class="kamar-grid" id="gridKamarMobile">
                <?php if (empty($daftar_kamar_mobile)): ?>
                <p class="kamar-empty-state-mobile">Belum ada kamar di database.</p>
                <?php else: ?>
                <?php foreach ($daftar_kamar_mobile as $idx => $kamar): ?>
                <?php
                    $nama_penghuni      = !empty($kamar['nama_penghuni']) ? $kamar['nama_penghuni'] : 'Kosong';
                    $is_kosong          = ($kamar['status_kamar'] === 'kosong' || empty($kamar['nama_penghuni']));
                    $status_bayar_lower = strtolower($kamar['status_bayar'] ?? 'lunas');
                    $is_freeze_card     = (!$is_kosong && ($kamar['status_akun'] ?? 'aktif') === 'freeze');
                    $page_m             = (int) floor($idx / $limit_kamar_mobile) + 1;
                ?>
                <a href="/?page=<?= urlencode($current_page) ?>&halaman_m=<?= $page_m; ?>&modal=detail_kamar&nomor=<?= urlencode($kamar['no_kamar']) ?>"
                    class="kamar-card kamar-item-mobile"
                    data-page-m="<?= $page_m; ?>"
                    style="<?= $page_m !== $halaman_aktif_mobile ? 'display: none;' : ''; ?>">

                    <?php if ($is_kosong): ?>
                    <span class="badge-status badge-kosong">Kosong</span>
                    <?php elseif ($is_freeze_card): ?>
                    <span class="badge-status" style="background-color: #fee2e2; color: #dc2626;">Freeze</span>
                    <?php elseif ($status_bayar_lower === 'lunas'): ?>
                    <span class="badge-status badge-lunas">Lunas</span>
                    <?php else: ?>
                    <span class="badge-status badge-belum">Belum</span>
                    <?php endif; ?>

                    <div class="kamar-nomor"><?= htmlspecialchars(sprintf('%02d', intval($kamar['no_kamar']))) ?></div>
                    <div class="kamar-nama"><?= htmlspecialchars($nama_penghuni) ?></div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- PAGINATION MOBILE -->
            <?php if ($total_halaman_mobile > 1): ?>
            <div class="kamar-mobile-pagination">
                <button type="button" class="kamar-mobile-page-btn" id="btnPrevMobile" onclick="gantiHalamanMobile(-1)" title="Halaman Sebelumnya" <?= $halaman_aktif_mobile <= 1 ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <span class="kamar-mobile-page-info" id="infoPageMobile">
                    Halaman <?= $halaman_aktif_mobile; ?> / <?= $total_halaman_mobile; ?>
                </span>

                <button type="button" class="kamar-mobile-page-btn" id="btnNextMobile" onclick="gantiHalamanMobile(1)" title="Halaman Berikutnya" <?= $halaman_aktif_mobile >= $total_halaman_mobile ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
            <?php endif; ?>
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

            <!-- NOTIFIKASI PENGINGAT SEWA & FREEZE (DESKTOP) -->
            <?php if (!empty($daftar_notif_sewa)): ?>
            <div style="display: flex; flex-direction: column; gap: 10px; max-width: 820px; margin-bottom: 20px;">
                <?php foreach ($daftar_notif_sewa as $notif): ?>
                <?php $isFrz = ($notif['fase'] === 'freeze'); ?>
                <div style="background-color: <?= $isFrz ? '#fee2e2' : '#fef3c7'; ?>; border: 1.5px solid <?= $isFrz ? '#ef4444' : '#f59e0b'; ?>; color: <?= $isFrz ? '#991b1b' : '#92400e'; ?>; border-radius: 16px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; gap: 14px; box-shadow: 3px 4px 0px #c8924b;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fa-solid <?= $isFrz ? 'fa-lock' : 'fa-bell'; ?>" style="font-size: 20px;"></i>
                        <div>
                            <strong style="display: block; font-size: 13.5px; margin-bottom: 2px;">
                                Kamar <?= htmlspecialchars(sprintf('%02d', (int)$notif['no_kamar'])); ?> atas nama <?= htmlspecialchars($notif['nama_penghuni']); ?> &mdash; <?= htmlspecialchars($notif['label_status']); ?>
                            </strong>
                            <span style="font-size: 12px;">
                                <?= htmlspecialchars($notif['pesan_ringkas']); ?> Total tagihan: <strong>Rp <?= number_format((float)$notif['total_tunggakan'], 0, ',', '.'); ?></strong>
                                <?php if (!empty($notif['tanggal_janji_bayar'])): ?>
                                    &bull; Janji bayar: <strong><?= date('d/m/Y', strtotime($notif['tanggal_janji_bayar'])); ?></strong>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                        <a href="<?= htmlspecialchars($notif['wa_pengingat_url']); ?>" target="_blank"
                           style="background: #25D366; color: #ffffff; font-size: 12px; font-weight: 800; padding: 7px 14px; border-radius: 999px; text-decoration: none; white-space: nowrap;">
                            <i class="fa-brands fa-whatsapp"></i> Ingatkan WA
                        </a>
                        <a href="/?page=<?= urlencode($current_page) ?>&modal=detail_kamar&nomor=<?= urlencode($notif['no_kamar']) ?>"
                           style="background: #ffffff; color: #1a4d55; font-size: 12px; font-weight: 800; padding: 7px 14px; border-radius: 999px; text-decoration: none; white-space: nowrap;">
                            <i class="fa-solid fa-sliders"></i> Kelola
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h3 class="desktop-kamar-section-label">Data terbaru</h3>

            <div class="desktop-kamar-top-actions">
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_kamar" onclick="bukaModalInstan(event, 'modalTambahKamar', 'tambah_kamar')" class="kamar-btn-pill-action">
                    <i class="fa-solid fa-plus"></i> Tambah Kamar
                </a>
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_penghuni" onclick="bukaModalInstan(event, 'modalTambahPenghuni', 'tambah_penghuni')" class="kamar-btn-pill-action">
                    <i class="fa-solid fa-plus"></i> Tambah Penghuni
                </a>
                <a href="/?page=<?= urlencode($current_page) ?>&modal=kelola_skema" onclick="bukaModalInstan(event, 'modalKelolaSkema', 'kelola_skema')" class="kamar-btn-pill-action kamar-btn-skema-action">
                    <i class="fa-solid fa-sliders"></i> Skema Sewa
                </a>
            </div>

            <div class="kamar-desktop-panel">
                <div class="kamar-desktop-grid-white" id="gridKamarDesktop">
                    <?php if (empty($daftar_kamar)): ?>
                    <p class="kamar-empty-state-desktop">Belum ada kamar tersimpan.</p>
                    <?php else: ?>
                    <?php foreach ($daftar_kamar as $idx => $kamar): ?>
                    <?php
                        $nama_penghuni      = !empty($kamar['nama_penghuni']) ? $kamar['nama_penghuni'] : 'Kosong';
                        $is_kosong          = ($kamar['status_kamar'] === 'kosong' || empty($kamar['nama_penghuni']));
                        $status_bayar_lower = strtolower($kamar['status_bayar'] ?? 'lunas');
                        $is_freeze_card     = (!$is_kosong && ($kamar['status_akun'] ?? 'aktif') === 'freeze');
                        $page_d             = (int) floor($idx / $limit_kamar) + 1;
                    ?>
                    <a href="/?page=<?= urlencode($current_page) ?>&halaman=<?= $page_d; ?>&modal=detail_kamar&nomor=<?= urlencode($kamar['no_kamar']) ?>"
                        class="<?= $is_kosong ? 'kamar-card-gray' : 'kamar-card-white'; ?> kamar-item-desktop"
                        data-page-d="<?= $page_d; ?>"
                        style="<?= $page_d !== $halaman_aktif ? 'display: none;' : ''; ?>">

                        <?php if ($is_kosong): ?>
                        <span class="badge-kamar-status badge-kamar-kosong">Kosong</span>
                        <?php elseif ($is_freeze_card): ?>
                        <span class="badge-kamar-status" style="background-color: #fee2e2; color: #dc2626;">Freeze</span>
                        <?php elseif ($status_bayar_lower === 'lunas'): ?>
                        <span class="badge-kamar-status badge-kamar-lunas">Lunas</span>
                        <?php else: ?>
                        <span class="badge-kamar-status badge-kamar-belum">Belum</span>
                        <?php endif; ?>

                        <div class="kamar-card-number"><?= htmlspecialchars(sprintf('%02d', intval($kamar['no_kamar']))) ?></div>
                        <div class="kamar-card-resident"><?= htmlspecialchars($nama_penghuni) ?></div>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="kamar-desktop-bottom-bar">
                    <div class="kamar-legend-row">
                        <span class="legend-item"><span class="legend-color-box color-lunas"></span> Lunas</span>
                        <span class="legend-item"><span class="legend-color-box color-belum"></span> Belum bayar</span>
                        <span class="legend-item"><span class="legend-color-box" style="background-color: #fee2e2;"></span> Freeze</span>
                        <span class="legend-item"><span class="legend-color-box color-kosong"></span> Kosong</span>
                    </div>

                    <!-- PAGINATION DESKTOP -->
                    <div class="kamar-pagination-row">
                        <button type="button" class="kamar-page-btn" id="btnPrevDesktop" onclick="gantiHalamanDesktop(-1)" title="Halaman Sebelumnya" <?= $halaman_aktif <= 1 ? 'disabled style="opacity: 0.45; cursor: not-allowed;"' : ''; ?>>
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <span class="kamar-page-info" id="infoPageDesktop"><?= $halaman_aktif; ?>/<?= $total_halaman; ?></span>

                        <button type="button" class="kamar-page-btn" id="btnNextDesktop" onclick="gantiHalamanDesktop(1)" title="Halaman Berikutnya" <?= $halaman_aktif >= $total_halaman ? 'disabled style="opacity: 0.45; cursor: not-allowed;"' : ''; ?>>
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
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

    <script>
        function closeToast() {
            const toast = document.getElementById('toastAlertBox');
            if (toast) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-15px) scale(0.95)';
                setTimeout(() => toast.remove(), 300);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const toast = document.getElementById('toastAlertBox');
            if (toast) setTimeout(closeToast, 5000);
        });

        function bukaModalInstan(e, modalId, modalParam) {
            if (e) e.preventDefault();
            const modal = document.getElementById(modalId);
            if (!modal) return;

            modal.classList.add('active');
            const firstInput = modal.querySelector('input:not([type="hidden"]):not([readonly])');
            if (firstInput) setTimeout(() => firstInput.focus(), 50);

            const url = new URL(window.location.href);
            url.searchParams.set('modal', modalParam);
            window.history.replaceState({}, '', url);
        }

        function tutupSemuaModalInstan(e) {
            if (e) e.preventDefault();
            document.querySelectorAll('.modal-overlay').forEach(function (m) {
                m.classList.remove('active');
            });

            const url = new URL(window.location.href);
            url.searchParams.delete('modal');
            url.searchParams.delete('nomor');
            url.searchParams.delete('kamar');
            window.history.replaceState({}, '', url);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                tutupSemuaModalInstan();
            }
        });

        let currentPageDesktop = <?= (int)$halaman_aktif; ?>;
        const totalPageDesktop = <?= (int)$total_halaman; ?>;

        function gantiHalamanDesktop(delta) {
            const target = currentPageDesktop + delta;
            if (target < 1 || target > totalPageDesktop) return;
            currentPageDesktop = target;

            document.querySelectorAll('.kamar-item-desktop').forEach(function (card) {
                card.style.display = (parseInt(card.getAttribute('data-page-d')) === currentPageDesktop) ? '' : 'none';
            });

            const info = document.getElementById('infoPageDesktop');
            if (info) info.textContent = `${currentPageDesktop}/${totalPageDesktop}`;

            const btnPrev = document.getElementById('btnPrevDesktop');
            const btnNext = document.getElementById('btnNextDesktop');
            if (btnPrev) {
                btnPrev.disabled = currentPageDesktop <= 1;
                btnPrev.style.opacity = currentPageDesktop <= 1 ? '0.45' : '1';
                btnPrev.style.cursor = currentPageDesktop <= 1 ? 'not-allowed' : 'pointer';
            }
            if (btnNext) {
                btnNext.disabled = currentPageDesktop >= totalPageDesktop;
                btnNext.style.opacity = currentPageDesktop >= totalPageDesktop ? '0.45' : '1';
                btnNext.style.cursor = currentPageDesktop >= totalPageDesktop ? 'not-allowed' : 'pointer';
            }

            const url = new URL(window.location.href);
            if (currentPageDesktop === 1) url.searchParams.delete('halaman');
            else url.searchParams.set('halaman', currentPageDesktop);
            window.history.replaceState({}, '', url);
        }

        let currentPageMobile = <?= (int)$halaman_aktif_mobile; ?>;
        const totalPageMobile = <?= (int)$total_halaman_mobile; ?>;

        function gantiHalamanMobile(delta) {
            const target = currentPageMobile + delta;
            if (target < 1 || target > totalPageMobile) return;
            currentPageMobile = target;

            document.querySelectorAll('.kamar-item-mobile').forEach(function (card) {
                card.style.display = (parseInt(card.getAttribute('data-page-m')) === currentPageMobile) ? '' : 'none';
            });

            const info = document.getElementById('infoPageMobile');
            if (info) info.textContent = `Halaman ${currentPageMobile} / ${totalPageMobile}`;

            const btnPrev = document.getElementById('btnPrevMobile');
            const btnNext = document.getElementById('btnNextMobile');
            if (btnPrev) btnPrev.disabled = currentPageMobile <= 1;
            if (btnNext) btnNext.disabled = currentPageMobile >= totalPageMobile;

            const url = new URL(window.location.href);
            if (currentPageMobile === 1) url.searchParams.delete('halaman_m');
            else url.searchParams.set('halaman_m', currentPageMobile);
            window.history.replaceState({}, '', url);
        }
    </script>

</body>

</html>