<?php
/**
 * VIEW VERIFIKASI PEMBAYARAN
 * Lokasi: modules/shared/verifikasi_bayar.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/handler/verifikasi_handler.php';

$currentUser       = $_SESSION['user'] ?? [];
$is_pemilik        = ($currentUser['role'] ?? '') === 'pemilik';
$is_ketua          = !empty($currentUser['is_pj']) || !empty($currentUser['is_ketua_kos']) || ($currentUser['role'] ?? '') === 'pj';

$current_page      = $current_page ?? ($_GET['page'] ?? 'verifikasi_bayar');
$link_kembali      = $link_kembali ?? ($is_pemilik ? '/?page=dashboard_pemilik' : '/?page=dashboard_penghuni');
$date_range        = $date_range ?? trim($_GET['date_range'] ?? '');
$is_filtered       = $is_filtered ?? (!empty($date_range));
$daftar_verifikasi = $daftar_verifikasi ?? [];
$pesan_sukses      = $pesan_sukses ?? '';
$error_verif       = $error_verif ?? '';
$wa_redirect_tolak = $_SESSION['wa_redirect_tolak'] ?? '';
unset($_SESSION['wa_redirect_tolak']);

$total_verif = count($daftar_verifikasi);

// 1. Pagination Desktop (4 per halaman)
$limit_desktop  = 4;
$page_desktop   = isset($_GET['hal']) ? max(1, (int)$_GET['hal']) : 1;
$total_hal_desk = max(1, (int)ceil($total_verif / $limit_desktop));
if ($page_desktop > $total_hal_desk) $page_desktop = $total_hal_desk;

// 2. Pagination Mobile (3 per halaman)
$limit_mobile   = 3;
$page_mobile    = isset($_GET['hal_m']) ? max(1, (int)$_GET['hal_m']) : 1;
$total_hal_mob  = max(1, (int)ceil($total_verif / $limit_mobile));
if ($page_mobile > $total_hal_mob) $page_mobile = $total_hal_mob;
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Verifikasi Pembayaran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= filemtime(__DIR__ . '/../../public/css/global.css'); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= filemtime(__DIR__ . '/../../public/css/modules.css'); ?>">
</head>

<body class="dashboard-body">

    <!-- FLOATING TOAST NOTIFIKASI BERHASIL / GAGAL (UNTUK AKSI TERIMA / ERROR) -->
    <div class="toast-floating-container">
        <?php if (!empty($pesan_sukses)): ?>
        <div class="toast-alert toast-success" id="toastAlertBox">
            <div class="toast-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            <div class="toast-content">
                <h5 class="toast-title">Verifikasi Berhasil</h5>
                <p class="toast-message"><?= htmlspecialchars($pesan_sukses); ?></p>
            </div>
            <button type="button" class="toast-close" onclick="closeToast()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <?php endif; ?>

        <?php if (!empty($error_verif)): ?>
        <div class="toast-alert toast-error" id="toastAlertBox">
            <div class="toast-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="toast-content">
                <h5 class="toast-title">Verifikasi Gagal</h5>
                <p class="toast-message"><?= htmlspecialchars($error_verif); ?></p>
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
            <?php
            if (file_exists(__DIR__ . '/../components/header.php')) {
                require_once __DIR__ . '/../components/header.php';
            }
            ?>

            <div class="verifikasi-container">
                <div class="banner-card">
                    <h2 class="verifikasi-subpage-title">Verifikasi Pembayaran</h2>
                    <p class="verifikasi-subpage-desc">
                        <?= $is_pemilik ? 'Daftar bukti transfer sewa dan iuran kos' : 'Daftar bukti transfer iuran bersama penghuni kos'; ?>
                    </p>
                    <a href="<?= $link_kembali; ?>" class="btn-kembali verif-btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <form method="GET" action="" class="filter-range-form">
                    <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                    <div class="flatpickr-box-wrapper verifikasi-filter-box-full">
                        <i class="fa-solid fa-filter date-icon"></i>
                        <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                            placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                        <?php if ($is_filtered): ?>
                        <a href="/?page=<?= urlencode($current_page); ?>" class="btn-reset-range" title="Reset Filter">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="card-list">
                    <?php if (empty($daftar_verifikasi)): ?>
                    <div class="empty-state-card verifikasi-empty-card-full">
                        <i class="fa-solid fa-folder-open empty-icon"></i>
                        <span>Tidak ada verifikasi pending</span>
                    </div>
                    <?php else: ?>

                    <?php foreach ($daftar_verifikasi as $idx => $item): ?>
                    <?php
                        $metode_raw = strtolower($item['metode_bayar'] ?? 'transfer');
                        $is_tunai   = ($metode_raw === 'tunai' || empty($item['foto']));
                        $page_m     = (int)floor($idx / $limit_mobile) + 1;
                    ?>
                    <div class="verifikasi-card verif-item-mobile <?= $page_m !== $page_mobile ? 'verif-hidden-item' : ''; ?>"
                         data-page-m="<?= $page_m; ?>">
                        <div class="verifikasi-card-body">
                            <?php if ($is_tunai): ?>
                            <div class="bukti-wrapper verif-tunai-box">
                                <i class="fa-solid fa-hand-holding-dollar verif-tunai-icon-mob"></i>
                                <span class="verif-tunai-label">Tunai</span>
                            </div>
                            <?php else: ?>
                            <div class="bukti-wrapper">
                                <img src="<?= $item['src_foto']; ?>" alt="Bukti Transfer" class="bukti-img" loading="lazy"
                                    onclick="bukaPopupBukti('<?= $item['src_foto']; ?>', '<?= htmlspecialchars(addslashes($item['nama'])); ?>', '<?= htmlspecialchars(addslashes($item['kamar'])); ?>')">
                                <button type="button" class="btn-zoom" title="Perbesar Bukti"
                                    onclick="bukaPopupBukti('<?= $item['src_foto']; ?>', '<?= htmlspecialchars(addslashes($item['nama'])); ?>', '<?= htmlspecialchars(addslashes($item['kamar'])); ?>')">
                                    <i class="fa-solid fa-magnifying-glass-plus"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                            <div class="detail-wrapper">
                                <div class="user-info-row">
                                    <span class="nama-penghuni"><?= htmlspecialchars($item['nama']); ?></span>
                                    <span class="badge-kamar">Kamar <?= htmlspecialchars($item['kamar']); ?></span>
                                    <span class="badge-tagihan-tipe"><?= htmlspecialchars($item['tipe_tagihan']); ?></span>
                                </div>
                                <div class="nominal-text">Rp. <?= number_format($item['jumlah_bayar'], 0, ',', '.'); ?></div>
                                <div class="metode-tanggal"><?= ucfirst($item['metode_bayar']); ?> ~
                                    <?= htmlspecialchars(date('d-m-Y H:i', strtotime($item['tanggal_bayar']))); ?></div>
                            </div>
                        </div>
                        <div class="verifikasi-card-footer">
                            <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $item['no_wa'])); ?>"
                                target="_blank" class="btn-action btn-chat">
                                <i class="fa-brands fa-whatsapp"></i> Chat
                            </a>
                            <button type="button" class="btn-action btn-decline btn-action-full"
                                onclick="konfirmasiAksi(<?= $item['pembayaran_id']; ?>, 'tolak', '<?= htmlspecialchars(addslashes($item['nama'])); ?>', '<?= number_format($item['jumlah_bayar'], 0, ',', '.'); ?>', '<?= $item['tipe_tagihan']; ?>', '<?= $is_tunai ? 'tunai' : 'transfer'; ?>')">
                                <i class="fa-solid fa-circle-xmark"></i> Tolak
                            </button>
                            <button type="button" class="btn-action btn-approve btn-action-full"
                                onclick="konfirmasiAksi(<?= $item['pembayaran_id']; ?>, 'terima', '<?= htmlspecialchars(addslashes($item['nama'])); ?>', '<?= number_format($item['jumlah_bayar'], 0, ',', '.'); ?>', '<?= $item['tipe_tagihan']; ?>', '<?= $is_tunai ? 'tunai' : 'transfer'; ?>')">
                                <i class="fa-solid fa-circle-check"></i> Terima
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if ($total_hal_mob > 1): ?>
                    <div class="verif-pagination-wrapper verif-pagination-mobile">
                        <div class="verif-page-controls">
                            <button type="button" class="verif-page-btn" id="btnPrevVerifMob" onclick="gantiHalVerifMobile(-1)" title="Sebelumnya" <?= $page_mobile <= 1 ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>

                            <span class="verif-page-badge" id="badgeVerifMob"> <?= $page_mobile; ?> / <?= $total_hal_mob; ?></span>

                            <button type="button" class="verif-page-btn" id="btnNextVerifMob" onclick="gantiHalVerifMobile(1)" title="Berikutnya" <?= $page_mobile >= $total_hal_mob ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php
        if (file_exists(__DIR__ . '/../components/footer.php')) {
            require_once __DIR__ . '/../components/footer.php';
        }
        ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Verifikasi Pembayaran</h2>
            <p class="desktop-header-subtitle">
                <?= $is_pemilik ? 'Daftar konfirmasi bukti transfer sewa dan iuran dari penghuni kos' : 'Daftar konfirmasi bukti transfer iuran bersama penghuni kos'; ?>
            </p>

            <form method="GET" action="" class="filter-range-form-desktop verifikasi-filter-desktop-wrap">
                <input type="hidden" name="page" value="<?= htmlspecialchars($current_page); ?>">
                <div class="flatpickr-box-wrapper verifikasi-filter-box-full">
                    <i class="fa-solid fa-filter date-icon"></i>
                    <input type="text" name="date_range" value="<?= htmlspecialchars($date_range); ?>"
                        placeholder="Pilih Rentang Tanggal..." class="flatpickr-range-input" readonly>
                    <?php if ($is_filtered): ?>
                    <a href="/?page=<?= urlencode($current_page); ?>" class="btn-reset-range" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="card-list verifikasi-card-list-desktop">
                <?php if (empty($daftar_verifikasi)): ?>
                <div class="empty-state-card verifikasi-empty-card-full">
                    <i class="fa-solid fa-folder-open"></i>
                    <span>Tidak ada data verifikasi pembayaran yang pending</span>
                </div>
                <?php else: ?>
                <?php foreach ($daftar_verifikasi as $idx => $item): ?>
                <?php
                    $metode_raw = strtolower($item['metode_bayar'] ?? 'transfer');
                    $is_tunai   = ($metode_raw === 'tunai' || empty($item['foto']));
                    $page_d     = (int)floor($idx / $limit_desktop) + 1;
                ?>
                <div class="verifikasi-card verif-item-desktop <?= $page_d !== $page_desktop ? 'verif-hidden-item' : ''; ?>"
                     data-page-d="<?= $page_d; ?>">
                    <div class="verifikasi-card-body">
                        <?php if ($is_tunai): ?>
                        <div class="bukti-wrapper verif-tunai-box">
                            <i class="fa-solid fa-hand-holding-dollar verif-tunai-icon-desk"></i>
                            <span class="verif-tunai-label">Tunai</span>
                        </div>
                        <?php else: ?>
                        <div class="bukti-wrapper">
                            <img src="<?= $item['src_foto']; ?>" alt="Bukti Transfer" class="bukti-img" loading="lazy"
                                onclick="bukaPopupBukti('<?= $item['src_foto']; ?>', '<?= htmlspecialchars(addslashes($item['nama'])); ?>', '<?= htmlspecialchars(addslashes($item['kamar'])); ?>')">
                            <button type="button" class="btn-zoom" title="Perbesar Bukti"
                                onclick="bukaPopupBukti('<?= $item['src_foto']; ?>', '<?= htmlspecialchars(addslashes($item['nama'])); ?>', '<?= htmlspecialchars(addslashes($item['kamar'])); ?>')">
                                <i class="fa-solid fa-magnifying-glass-plus"></i>
                            </button>
                        </div>
                        <?php endif; ?>

                        <div class="detail-wrapper">
                            <div class="user-info-row">
                                <span class="nama-penghuni"><?= htmlspecialchars($item['nama']); ?></span>
                                <span class="badge-kamar">Kamar <?= htmlspecialchars($item['kamar']); ?></span>
                                <span class="badge-tagihan-tipe"><?= htmlspecialchars($item['tipe_tagihan']); ?></span>
                            </div>
                            <div class="nominal-text">Rp. <?= number_format($item['jumlah_bayar'], 0, ',', '.'); ?></div>
                            <div class="metode-tanggal"><?= ucfirst($item['metode_bayar']); ?> ~
                                <?= htmlspecialchars(date('d-m-Y H:i', strtotime($item['tanggal_bayar']))); ?></div>
                        </div>
                    </div>
                    <div class="verifikasi-card-footer">
                        <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $item['no_wa'])); ?>"
                            target="_blank" class="btn-action btn-chat">
                            <i class="fa-brands fa-whatsapp"></i> Chat
                        </a>
                        <button type="button" class="btn-action btn-decline btn-action-full"
                            onclick="konfirmasiAksi(<?= $item['pembayaran_id']; ?>, 'tolak', '<?= htmlspecialchars(addslashes($item['nama'])); ?>', '<?= number_format($item['jumlah_bayar'], 0, ',', '.'); ?>', '<?= $item['tipe_tagihan']; ?>', '<?= $is_tunai ? 'tunai' : 'transfer'; ?>')">
                            <i class="fa-solid fa-circle-xmark"></i> Tolak
                        </button>
                        <button type="button" class="btn-action btn-approve btn-action-full"
                            onclick="konfirmasiAksi(<?= $item['pembayaran_id']; ?>, 'terima', '<?= htmlspecialchars(addslashes($item['nama'])); ?>', '<?= number_format($item['jumlah_bayar'], 0, ',', '.'); ?>', '<?= $item['tipe_tagihan']; ?>', '<?= $is_tunai ? 'tunai' : 'transfer'; ?>')">
                            <i class="fa-solid fa-circle-check"></i> Terima
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($total_hal_desk > 1): ?>
            <div class="verif-pagination-wrapper verif-pagination-desktop">
                <div class="verif-page-controls">
                    <button type="button" class="verif-page-btn" id="btnPrevVerifDesk" onclick="gantiHalVerifDesktop(currentHalVerifDesk - 1)" title="Sebelumnya" <?= $page_desktop <= 1 ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <?php for ($i = 1; $i <= $total_hal_desk; $i++): ?>
                    <button type="button"
                        data-verif-page-btn="<?= $i; ?>"
                        onclick="gantiHalVerifDesktop(<?= $i; ?>)"
                        class="verif-page-num <?= ($i === $page_desktop) ? 'active' : ''; ?>">
                        <?= $i; ?>
                    </button>
                    <?php endfor; ?>

                    <button type="button" class="verif-page-btn" id="btnNextVerifDesk" onclick="gantiHalVerifDesktop(currentHalVerifDesk + 1)" title="Berikutnya" <?= $page_desktop >= $total_hal_desk ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <!-- MODAL CUSTOM KONFIRMASI (TERIMA / TOLAK DENGAN ALASAN) -->
    <div id="modalKonfirmasiVerif" class="modal-overlay" onclick="tutupKonfirmasiAksi()">
        <div class="modal-konfirmasi-verif-card" onclick="event.stopPropagation()">
            <div id="konfirIconBox" class="konfirmasi-verif-icon icon-terima">
                <i id="konfirIcon" class="fa-solid fa-circle-check"></i>
            </div>
            <h3 id="konfirTitle" class="konfirmasi-verif-title">Konfirmasi Pembayaran</h3>
            <p id="konfirDesc" class="konfirmasi-verif-desc">
                Apakah Anda yakin ingin memproses pembayaran ini?
            </p>

            <form id="formEksekusiVerif" method="POST" action="" onsubmit="handleVerifSubmit(event)">
                <input type="hidden" name="pembayaran_id" id="modalInputPemId" value="">
                <input type="hidden" name="aksi_verifikasi" id="modalInputAksi" value="">

                <!-- LIST ALASAN PENOLAKAN (DINAMIS SESUAI TRANSFER / TUNAI) -->
                <div id="boxAlasanTolak" class="alasan-tolak-container verif-hidden-item">
                    <div id="listOpsiAlasan" class="verif-opsi-alasan-list"></div>

                    <input type="text" name="alasan_lainnya" id="inputAlasanLainnya" class="input-alasan-lainnya verif-hidden-item"
                        placeholder="Tuliskan alasan spesifik...">
                </div>

                <div class="konfirmasi-verif-actions">
                    <button type="button" class="btn-konfir-batal" onclick="tutupKonfirmasiAksi()">Batal</button>
                    <button type="submit" id="btnKonfirSubmit" class="btn-konfir-submit btn-terima">
                        <span class="btn-text-content" id="btnSubmitText">Ya, Lanjutkan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL POPUP LIGHTBOX BUKTI TRANSFER FULLSCREEN -->
    <div id="modalLightboxBukti" class="modal-overlay modal-lightbox-overlay" onclick="tutupPopupBukti()">
        <div class="modal-card-lightbox" onclick="event.stopPropagation()">
            <div class="lightbox-header">
                <span class="lightbox-title" id="lightboxTitle">
                    <i class="fa-solid fa-receipt"></i> Bukti Transfer
                </span>
                <button type="button" class="btn-close-lightbox" onclick="tutupPopupBukti()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="lightbox-body">
                <img id="imgBuktiFull" src="" alt="Bukti Transfer Full" class="lightbox-img">
            </div>
        </div>
    </div>

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

    function closeToast() {
        const toast = document.getElementById('toastAlertBox');
        if (toast) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-15px) scale(0.95)';
            setTimeout(() => toast.remove(), 300);
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const toast = document.getElementById('toastAlertBox');
        if (toast) {
            setTimeout(closeToast, 5000);
        }

        // Otomatis buka WhatsApp setelah halaman verifikasi segar ter-load
        const waUrlTolak = <?= json_encode($wa_redirect_tolak); ?>;
        if (waUrlTolak) {
            window.location.href = waUrlTolak;
        }
    });

    // Reset modal & spinner loading jika user kembali dari aplikasi WhatsApp (bfcache)
    window.addEventListener('pageshow', function() {
        const btn = document.getElementById('btnKonfirSubmit');
        if (btn) btn.classList.remove('btn-submit-loading');
        tutupKonfirmasiAksi();
    });

    // Pagination Verifikasi Desktop Instan (0 ms)
    let currentHalVerifDesk = <?= (int)$page_desktop; ?>;
    const totalHalVerifDesk = <?= (int)$total_hal_desk; ?>;

    function gantiHalVerifDesktop(target) {
        if (target < 1 || target > totalHalVerifDesk) return;
        currentHalVerifDesk = target;

        document.querySelectorAll('.verif-item-desktop').forEach(card => {
            card.classList.toggle('verif-hidden-item', parseInt(card.getAttribute('data-page-d')) !== currentHalVerifDesk);
        });

        document.querySelectorAll('[data-verif-page-btn]').forEach(btn => {
            btn.classList.toggle('active', parseInt(btn.getAttribute('data-verif-page-btn')) === currentHalVerifDesk);
        });

        const btnPrev = document.getElementById('btnPrevVerifDesk');
        const btnNext = document.getElementById('btnNextVerifDesk');
        if (btnPrev) btnPrev.disabled = currentHalVerifDesk <= 1;
        if (btnNext) btnNext.disabled = currentHalVerifDesk >= totalHalVerifDesk;

        const url = new URL(window.location.href);
        if (currentHalVerifDesk === 1) url.searchParams.delete('hal');
        else url.searchParams.set('hal', currentHalVerifDesk);
        window.history.replaceState({}, '', url);
    }

    // Pagination Verifikasi Mobile Instan (0 ms)
    let currentHalVerifMob = <?= (int)$page_mobile; ?>;
    const totalHalVerifMob = <?= (int)$total_hal_mob; ?>;

    function gantiHalVerifMobile(delta) {
        const target = currentHalVerifMob + delta;
        if (target < 1 || target > totalHalVerifMob) return;
        currentHalVerifMob = target;

        document.querySelectorAll('.verif-item-mobile').forEach(card => {
            card.classList.toggle('verif-hidden-item', parseInt(card.getAttribute('data-page-m')) !== currentHalVerifMob);
        });

        const badge = document.getElementById('badgeVerifMob');
        if (badge) badge.textContent = ` ${currentHalVerifMob} / ${totalHalVerifMob}`;

        const btnPrev = document.getElementById('btnPrevVerifMob');
        const btnNext = document.getElementById('btnNextVerifMob');
        if (btnPrev) btnPrev.disabled = currentHalVerifMob <= 1;
        if (btnNext) btnNext.disabled = currentHalVerifMob >= totalHalVerifMob;

        const url = new URL(window.location.href);
        if (currentHalVerifMob === 1) url.searchParams.delete('hal_m');
        else url.searchParams.set('hal_m', currentHalVerifMob);
        window.history.replaceState({}, '', url);
    }

    function konfirmasiAksi(id, aksi, nama, nominal, tipe, metode = 'transfer') {
        const modal = document.getElementById('modalKonfirmasiVerif');
        const inputId = document.getElementById('modalInputPemId');
        const inputAksi = document.getElementById('modalInputAksi');
        const iconBox = document.getElementById('konfirIconBox');
        const icon = document.getElementById('konfirIcon');
        const title = document.getElementById('konfirTitle');
        const desc = document.getElementById('konfirDesc');
        const btnSubmit = document.getElementById('btnKonfirSubmit');
        const btnText = document.getElementById('btnSubmitText');
        const boxAlasan = document.getElementById('boxAlasanTolak');
        const listOpsi = document.getElementById('listOpsiAlasan');
        const inputLain = document.getElementById('inputAlasanLainnya');

        btnSubmit.classList.remove('btn-submit-loading');

        inputId.value = id;
        inputAksi.value = aksi;

        if (aksi === 'terima') {
            boxAlasan.classList.add('verif-hidden-item');
            iconBox.className = 'konfirmasi-verif-icon icon-terima';
            icon.className = 'fa-solid fa-circle-check';
            title.innerText = 'Terima Pembayaran?';
            desc.innerHTML = 'Pembayaran <strong>' + tipe + '</strong> dari <strong>' + nama +
                '</strong> sebesar <strong>Rp. ' + nominal + '</strong> akan disetujui dan dicatat otomatis ke kas.';
            btnSubmit.className = 'btn-konfir-submit btn-terima';
            btnText.innerText = 'Ya, Terima';
        } else {
            boxAlasan.classList.remove('verif-hidden-item');
            inputLain.classList.add('verif-hidden-item');
            inputLain.value = '';
            inputLain.required = false;

            const opsiAlasan = (metode === 'tunai')
                ? [
                    { val: 'Uang tunai belum diterima secara langsung', label: 'Uang tunai belum diserahkan' },
                    { val: 'Nominal uang tunai kurang dari total tagihan', label: 'Nominal uang tunai kurang' },
                    { val: 'Konfirmasi pembayaran tunai belum sesuai', label: 'Konfirmasi pembayaran belum sesuai' },
                    { val: 'Lainnya', label: 'Alasan lainnya' }
                  ]
                : [
                    { val: 'Foto / Bukti transfer buram & tidak terbaca', label: 'Foto bukti buram / tidak terbaca' },
                    { val: 'Nominal transfer kurang dari total tagihan', label: 'Nominal transfer kurang' },
                    { val: 'Dana belum masuk ke rekening mutasi', label: 'Dana belum masuk ke mutasi rekening' },
                    { val: 'Lainnya', label: 'Alasan lainnya' }
                  ];

            listOpsi.innerHTML = opsiAlasan.map((opt, idx) => `
                <label class="alasan-option-label">
                    <input type="radio" name="alasan_penolakan" value="${opt.val}"
                        ${idx === 0 ? 'checked' : ''} onchange="toggleAlasanLain(${opt.val === 'Lainnya'})">
                    <span>${opt.label}</span>
                </label>
            `).join('');

            iconBox.className = 'konfirmasi-verif-icon icon-tolak';
            icon.className = 'fa-solid fa-circle-xmark';
            title.innerText = 'Tolak Pembayaran?';
            desc.innerHTML = 'Pilih alasan penolakan pembayaran <strong>' + tipe + '</strong> dari <strong>' +
                nama + '</strong>:';
            btnSubmit.className = 'btn-konfir-submit btn-tolak';
            btnText.innerText = 'Tolak & Kirim WA';
        }

        modal.classList.add('active');
    }

    function handleVerifSubmit(e) {
        const inputAksi = document.getElementById('modalInputAksi').value;
        const inputLain = document.getElementById('inputAlasanLainnya');

        if (inputAksi === 'tolak' && !inputLain.classList.contains('verif-hidden-item') && inputLain.value.trim() === '') {
            alert('Silakan tuliskan alasan penolakan secara spesifik.');
            inputLain.focus();
            e.preventDefault();
            return;
        }

        const btn = document.getElementById('btnKonfirSubmit');
        if (btn) {
            btn.classList.add('btn-submit-loading');
        }
    }

    function toggleAlasanLain(isLainnya) {
        const inputLain = document.getElementById('inputAlasanLainnya');
        if (inputLain) {
            inputLain.classList.toggle('verif-hidden-item', !isLainnya);
            if (isLainnya) {
                inputLain.focus();
                inputLain.required = true;
            } else {
                inputLain.required = false;
            }
        }
    }

    function tutupKonfirmasiAksi() {
        const modal = document.getElementById('modalKonfirmasiVerif');
        if (modal) modal.classList.remove('active');
    }

    function bukaPopupBukti(src, nama, kamar) {
        const modal = document.getElementById('modalLightboxBukti');
        const img = document.getElementById('imgBuktiFull');
        const title = document.getElementById('lightboxTitle');

        if (modal && img) {
            img.src = src;
            if (title && nama) {
                title.innerHTML = '<i class="fa-solid fa-receipt"></i> Bukti Transfer - ' + nama + ' (Kamar ' + kamar + ')';
            }
            modal.classList.add('active');
        }
    }

    function tutupPopupBukti() {
        const modal = document.getElementById('modalLightboxBukti');
        if (modal) modal.classList.remove('active');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            tutupKonfirmasiAksi();
            tutupPopupBukti();
        }
    });
    </script>
</body>

</html>