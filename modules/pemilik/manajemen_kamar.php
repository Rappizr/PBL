<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = $_GET['page'] ?? 'manajemen_kamar';
$modal_aktif  = $_GET['modal'] ?? '';

$daftar_kamar = [
    ['nomor' => '01', 'penghuni' => 'Ridwan', 'status' => 'lunas'],
    ['nomor' => '02', 'penghuni' => 'Nadia',  'status' => 'lunas'],
    ['nomor' => '03', 'penghuni' => 'Elena',  'status' => 'belum'],
    ['nomor' => '04', 'penghuni' => 'Jasper', 'status' => 'lunas'],
    ['nomor' => '05', 'penghuni' => 'Mira',   'status' => 'lunas'],
    ['nomor' => '06', 'penghuni' => 'Kiran',  'status' => 'lunas'],
    ['nomor' => '07', 'penghuni' => 'Lila',   'status' => 'lunas'],
    ['nomor' => '08', 'penghuni' => 'Kosong', 'status' => 'kosong'],
];
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

            <div class="kamar-actions">
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_kamar" class="btn-add-outline">
                    <span class="plus-icon">+</span> Tambah Kamar
                </a>
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_penghuni" class="btn-add-outline">
                    <span class="plus-icon">+</span> Tambah Penghuni
                </a>
            </div>

            <div class="kamar-grid">
                <?php foreach ($daftar_kamar as $kamar): ?>
                    <div class="kamar-card">
                        <?php if ($kamar['status'] === 'lunas'): ?>
                            <span class="badge-status badge-lunas">Lunas</span>
                        <?php elseif ($kamar['status'] === 'belum'): ?>
                            <span class="badge-status badge-belum">Belum</span>
                        <?php else: ?>
                            <span class="badge-status badge-kosong">Kosong</span>
                        <?php endif; ?>

                        <div class="kamar-nomor"><?= htmlspecialchars($kamar['nomor']) ?></div>
                        <div class="kamar-nama"><?= htmlspecialchars($kamar['penghuni']) ?></div>
                    </div>
                <?php endforeach; ?>
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

            <h3 class="desktop-kamar-section-label">Data terbaru</h3>

            <div class="desktop-kamar-top-actions">
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_kamar" class="kamar-btn-pill-action">
                    <i class="fa-solid fa-plus"></i> Tambah Kamar
                </a>
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_penghuni" class="kamar-btn-pill-action">
                    <i class="fa-solid fa-plus"></i> Tambah Penghuni
                </a>
            </div>

            <!-- Box Pembungkus Tosca Utama -->
            <div class="kamar-desktop-panel">
                <div class="kamar-desktop-grid-white">
                    <?php foreach ($daftar_kamar as $kamar): ?>
                        <div class="kamar-card-white">
                            <?php if ($kamar['status'] === 'lunas'): ?>
                                <span class="badge-kamar-status badge-kamar-lunas">Lunas</span>
                            <?php elseif ($kamar['status'] === 'belum'): ?>
                                <span class="badge-kamar-status badge-kamar-belum">Belum</span>
                            <?php else: ?>
                                <span class="badge-kamar-status badge-kamar-kosong">Kosong</span>
                            <?php endif; ?>

                            <div class="kamar-card-number"><?= htmlspecialchars($kamar['nomor']) ?></div>
                            <div class="kamar-card-resident"><?= htmlspecialchars($kamar['penghuni']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Bagian Bawah: Legenda Status & Pagination -->
                <div class="kamar-desktop-bottom-bar">
                    <div class="kamar-legend-row">
                        <span class="legend-item"><span class="legend-color-box color-lunas"></span> Lunas</span>
                        <span class="legend-item"><span class="legend-color-box color-belum"></span> Belum bayar</span>
                        <span class="legend-item"><span class="legend-color-box color-kosong"></span> Kosong</span>
                    </div>

                    <div class="kamar-pagination-row">
                        <button type="button" class="kamar-page-btn"><i class="fa-solid fa-chevron-left"></i></button>
                        <span class="kamar-page-info">1/2</span>
                        <button type="button" class="kamar-page-btn"><i class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH KAMAR -->
    <?php if ($modal_aktif === 'tambah_kamar'): ?>
        <div class="modal-overlay active">
            <div class="modal-box">
                <p class="modal-title">Tambah Kamar</p>
                <form action="?page=<?= htmlspecialchars($current_page) ?>" method="POST" style="width: 100%; display: flex; flex-direction: column; gap: 1rem;">
                    <div class="modal-form-group">
                        <label class="modal-label" for="nomorKamar">Nomor Kamar</label>
                        <input type="text" id="nomorKamar" name="no_kamar" class="modal-input" placeholder="Contoh: 07" maxlength="5" required>
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-label" for="hargaSewa">Harga Sewa (Rp)</label>
                        <input type="number" id="hargaSewa" name="harga_sewa" class="modal-input" placeholder="Contoh: 450000" min="0" required>
                    </div>
                    <div class="modal-actions">
                        <a href="?page=<?= htmlspecialchars($current_page) ?>" class="btn-modal btn-modal-batal">Batal</a>
                        <button type="submit" class="btn-modal btn-modal-simpan">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL TAMBAH PENGHUNI -->
    <?php if ($modal_aktif === 'tambah_penghuni'): ?>
        <div class="modal-overlay active">
            <div class="modal-box">
                <p class="modal-title">Tambah Penghuni</p>
                <form action="?page=<?= htmlspecialchars($current_page) ?>" method="POST" enctype="multipart/form-data" style="width: 100%; display: flex; flex-direction: column; gap: 0.85rem;">
                    <div class="modal-form-group">
                        <label class="modal-label" for="namaLengkap">Nama Lengkap</label>
                        <input type="text" id="namaLengkap" name="nama" class="modal-input" placeholder="Masukkan nama lengkap" required>
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-label" for="pilihKamar">Pilih Kamar</label>
                        <select id="pilihKamar" name="kamar_nomor" class="modal-select" required>
                            <option value="">-- Pilih Kamar --</option>
                            <?php foreach ($daftar_kamar as $kamar): ?>
                                <?php if ($kamar['status'] === 'kosong'): ?>
                                    <option value="<?= htmlspecialchars($kamar['nomor']) ?>">Kamar <?= htmlspecialchars($kamar['nomor']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-label" for="nomorHp">Nomor HP</label>
                        <input type="tel" id="nomorHp" name="no_wa" class="modal-input" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-label">Foto KTP</label>
                        <label class="upload-area">
                            <input type="file" name="foto_ktp" accept="image/*" required>
                            <div class="upload-icon-svg">
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#4ca5b0" stroke-width="2.2">
                                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"></path>
                                    <polyline points="7 9 12 4 17 9"></polyline>
                                    <line x1="12" y1="4" x2="12" y2="16"></line>
                                </svg>
                            </div>
                            <p class="upload-text">Ketuk untuk pilih foto</p>
                        </label>
                    </div>
                    <div class="modal-actions">
                        <a href="?page=<?= htmlspecialchars($current_page) ?>" class="btn-modal btn-modal-batal">Batal</a>
                        <button type="submit" class="btn-modal btn-modal-simpan">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>