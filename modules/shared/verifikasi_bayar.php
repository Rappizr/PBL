<?php
/**
 * VIEW VERIFIKASI PEMBAYARAN
 * Lokasi: modules/shared/verifikasi_bayar.php
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Panggil Handler langsung dari folder handler di subfolder yang sama
require_once __DIR__ . '/handler/verifikasi_handler.php';

// Proteksi Variabel Default untuk Intelephense
$currentUser       = $_SESSION['user'] ?? [];
$is_pemilik        = ($currentUser['role'] ?? '') === 'pemilik';
$is_ketua          = !empty($currentUser['is_pj']) || !empty($currentUser['is_ketua_kos']) || ($currentUser['role'] ?? '') === 'pj';

$current_page      = $current_page ?? ($_GET['page'] ?? 'verifikasi_bayar');
$link_kembali      = $link_kembali ?? ($is_pemilik ? '/?page=dashboard_pemilik' : '/?page=dashboard_penghuni');
$date_range        = $date_range ?? trim($_GET['date_range'] ?? '');
$is_filtered       = $is_filtered ?? (!empty($date_range));
$daftar_verifikasi = $daftar_verifikasi ?? [];
$pesan_sukses      = $pesan_sukses ?? ($_SESSION['pesan_sukses'] ?? '');
$error_verif       = $error_verif ?? '';
unset($_SESSION['pesan_sukses']);
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
                    <a href="<?= $link_kembali; ?>" class="btn-kembali" style="margin-top: 12px;">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <?php if (!empty($pesan_sukses)): ?>
                    <div class="alert-success verifikasi-alert-center">
                        <?= htmlspecialchars($pesan_sukses); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_verif)): ?>
                    <div class="alert-error verifikasi-alert-center">
                        <?= htmlspecialchars($error_verif); ?>
                    </div>
                <?php endif; ?>

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
                        <?php foreach ($daftar_verifikasi as $v): ?>
                            <div class="verifikasi-card">
                                <div class="verifikasi-card-body">
                                    <div class="bukti-wrapper">
                                        <img src="<?= $v['src_foto']; ?>" alt="Bukti Transfer" class="bukti-img"
                                             onclick="bukaPopupBukti('<?= $v['src_foto']; ?>', '<?= htmlspecialchars(addslashes($v['nama'])); ?>', '<?= htmlspecialchars(addslashes($v['kamar'])); ?>')">
                                        <button type="button" class="btn-zoom" title="Perbesar Bukti"
                                                onclick="bukaPopupBukti('<?= $v['src_foto']; ?>', '<?= htmlspecialchars(addslashes($v['nama'])); ?>', '<?= htmlspecialchars(addslashes($v['kamar'])); ?>')">
                                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                                        </button>
                                    </div>
                                    <div class="detail-wrapper">
                                        <div class="user-info-row">
                                            <span class="nama-penghuni"><?= htmlspecialchars($v['nama']); ?></span>
                                            <span class="badge-kamar">Kamar <?= htmlspecialchars($v['kamar']); ?></span>
                                            <span class="badge-tagihan-tipe"><?= htmlspecialchars($v['tipe_tagihan']); ?></span>
                                        </div>
                                        <div class="nominal-text">Rp. <?= number_format($v['jumlah_bayar'], 0, ',', '.'); ?></div>
                                        <div class="metode-tanggal"><?= ucfirst($v['metode_bayar']); ?> ~ <?= htmlspecialchars(date('d-m-Y H:i', strtotime($v['tanggal_bayar']))); ?></div>
                                    </div>
                                </div>
                                <div class="verifikasi-card-footer">
                                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $v['no_wa'])); ?>" target="_blank" class="btn-action btn-chat">
                                        <i class="fa-brands fa-whatsapp"></i> Chat
                                    </a>
                                    <button type="button" class="btn-action btn-decline btn-action-full"
                                            onclick="konfirmasiAksi(<?= $v['pembayaran_id']; ?>, 'tolak', '<?= htmlspecialchars(addslashes($v['nama'])); ?>', '<?= number_format($v['jumlah_bayar'], 0, ',', '.'); ?>', '<?= $v['tipe_tagihan']; ?>')">
                                        <i class="fa-solid fa-circle-xmark"></i> Tolak
                                    </button>
                                    <button type="button" class="btn-action btn-approve btn-action-full"
                                            onclick="konfirmasiAksi(<?= $v['pembayaran_id']; ?>, 'terima', '<?= htmlspecialchars(addslashes($v['nama'])); ?>', '<?= number_format($v['jumlah_bayar'], 0, ',', '.'); ?>', '<?= $v['tipe_tagihan']; ?>')">
                                        <i class="fa-solid fa-circle-check"></i> Terima
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
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

            <?php if (!empty($pesan_sukses)): ?>
                <div class="alert-success verifikasi-alert-desktop">
                    <?= htmlspecialchars($pesan_sukses); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_verif)): ?>
                <div class="alert-error verifikasi-alert-desktop">
                    <?= htmlspecialchars($error_verif); ?>
                </div>
            <?php endif; ?>

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
                    <?php foreach ($daftar_verifikasi as $v): ?>
                        <div class="verifikasi-card">
                            <div class="verifikasi-card-body">
                                <div class="bukti-wrapper">
                                    <img src="<?= $v['src_foto']; ?>" alt="Bukti Transfer" class="bukti-img"
                                         onclick="bukaPopupBukti('<?= $v['src_foto']; ?>', '<?= htmlspecialchars(addslashes($v['nama'])); ?>', '<?= htmlspecialchars(addslashes($v['kamar'])); ?>')">
                                    <button type="button" class="btn-zoom" title="Perbesar Bukti"
                                            onclick="bukaPopupBukti('<?= $v['src_foto']; ?>', '<?= htmlspecialchars(addslashes($v['nama'])); ?>', '<?= htmlspecialchars(addslashes($v['kamar'])); ?>')">
                                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                                    </button>
                                </div>
                                <div class="detail-wrapper">
                                    <div class="user-info-row">
                                        <span class="nama-penghuni"><?= htmlspecialchars($v['nama']); ?></span>
                                        <span class="badge-kamar">Kamar <?= htmlspecialchars($v['kamar']); ?></span>
                                        <span class="badge-tagihan-tipe"><?= htmlspecialchars($v['tipe_tagihan']); ?></span>
                                    </div>
                                    <div class="nominal-text">Rp. <?= number_format($v['jumlah_bayar'], 0, ',', '.'); ?></div>
                                    <div class="metode-tanggal"><?= ucfirst($v['metode_bayar']); ?> ~ <?= htmlspecialchars(date('d-m-Y H:i', strtotime($v['tanggal_bayar']))); ?></div>
                                </div>
                            </div>
                            <div class="verifikasi-card-footer">
                                <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $v['no_wa'])); ?>" target="_blank" class="btn-action btn-chat">
                                    <i class="fa-brands fa-whatsapp"></i> Chat
                                </a>
                                <button type="button" class="btn-action btn-decline btn-action-full"
                                        onclick="konfirmasiAksi(<?= $v['pembayaran_id']; ?>, 'tolak', '<?= htmlspecialchars(addslashes($v['nama'])); ?>', '<?= number_format($v['jumlah_bayar'], 0, ',', '.'); ?>', '<?= $v['tipe_tagihan']; ?>')">
                                    <i class="fa-solid fa-circle-xmark"></i> Tolak
                                </button>
                                <button type="button" class="btn-action btn-approve btn-action-full"
                                        onclick="konfirmasiAksi(<?= $v['pembayaran_id']; ?>, 'terima', '<?= htmlspecialchars(addslashes($v['nama'])); ?>', '<?= number_format($v['jumlah_bayar'], 0, ',', '.'); ?>', '<?= $v['tipe_tagihan']; ?>')">
                                    <i class="fa-solid fa-circle-check"></i> Terima
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- MODAL CUSTOM KONFIRMASI (TERIMA / TOLAK) -->
    <div id="modalKonfirmasiVerif" class="modal-overlay" onclick="tutupKonfirmasiAksi()">
        <div class="modal-konfirmasi-verif-card" onclick="event.stopPropagation()">
            <div id="konfirIconBox" class="konfirmasi-verif-icon icon-terima">
                <i id="konfirIcon" class="fa-solid fa-circle-check"></i>
            </div>
            <h3 id="konfirTitle" class="konfirmasi-verif-title">Konfirmasi Pembayaran</h3>
            <p id="konfirDesc" class="konfirmasi-verif-desc">
                Apakah Anda yakin ingin memproses pembayaran ini?
            </p>
            <form id="formEksekusiVerif" method="POST" action="">
                <input type="hidden" name="pembayaran_id" id="modalInputPemId" value="">
                <input type="hidden" name="aksi_verifikasi" id="modalInputAksi" value="">
                <div class="konfirmasi-verif-actions">
                    <button type="button" class="btn-konfir-batal" onclick="tutupKonfirmasiAksi()">Batal</button>
                    <button type="submit" id="btnKonfirSubmit" class="btn-konfir-submit btn-terima">Ya, Lanjutkan</button>
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

        function konfirmasiAksi(id, aksi, nama, nominal, tipe) {
            const modal = document.getElementById('modalKonfirmasiVerif');
            const inputId = document.getElementById('modalInputPemId');
            const inputAksi = document.getElementById('modalInputAksi');
            const iconBox = document.getElementById('konfirIconBox');
            const icon = document.getElementById('konfirIcon');
            const title = document.getElementById('konfirTitle');
            const desc = document.getElementById('konfirDesc');
            const btnSubmit = document.getElementById('btnKonfirSubmit');

            inputId.value = id;
            inputAksi.value = aksi;

            if (aksi === 'terima') {
                iconBox.className = 'konfirmasi-verif-icon icon-terima';
                icon.className = 'fa-solid fa-circle-check';
                title.innerText = 'Terima Pembayaran?';
                desc.innerHTML = 'Pembayaran <strong>' + tipe + '</strong> dari <strong>' + nama + '</strong> sebesar <strong>Rp. ' + nominal + '</strong> akan disetujui dan dicatat otomatis ke kas.';
                btnSubmit.className = 'btn-konfir-submit btn-terima';
                btnSubmit.innerText = 'Ya, Terima';
            } else {
                iconBox.className = 'konfirmasi-verif-icon icon-tolak';
                icon.className = 'fa-solid fa-circle-xmark';
                title.innerText = 'Tolak Pembayaran?';
                desc.innerHTML = 'Bukti pembayaran <strong>' + tipe + '</strong> dari <strong>' + nama + '</strong> akan ditolak dan penghuni harus mengunggah bukti baru.';
                btnSubmit.className = 'btn-konfir-submit btn-tolak';
                btnSubmit.innerText = 'Ya, Tolak';
            }

            modal.classList.add('active');
        }

        function tutupKonfirmasiAksi() {
            const modal = document.getElementById('modalKonfirmasiVerif');
            if (modal) modal.classList.remove('active');
        }

        function bukaPopupBukti(src, nama, kamar) {
            const modal = document.getElementById('modalLightboxBukti');
            const img   = document.getElementById('imgBuktiFull');
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
            if (modal) {
                modal.classList.remove('active');
            }
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