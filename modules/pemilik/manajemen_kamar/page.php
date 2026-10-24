<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Deteksi nama halaman saat ini untuk routing kembali
$current_page = $_GET['page'] ?? 'manajemen_kamar';

// Deteksi modal mana yang dibuka via PHP GET URL
$modal_aktif = $_GET['modal'] ?? '';

// Data dummy Kamar
$daftar_kamar = [
    ['nomor' => '01', 'penghuni' => 'Ridwan', 'status' => 'lunas'],
    ['nomor' => '02', 'penghuni' => 'Rafi', 'status' => 'lunas'],
    ['nomor' => '03', 'penghuni' => 'Bahlil', 'status' => 'belum'],
    ['nomor' => '04', 'penghuni' => 'Belum terisi', 'status' => 'kosong'],
    ['nomor' => '05', 'penghuni' => 'Joko', 'status' => 'lunas'],
    ['nomor' => '06', 'penghuni' => 'Bowo', 'status' => 'lunas'],
];

$nama_pemilik = 'Joko Budi';
$label_status = ['lunas' => 'Lunas', 'belum' => 'Belum', 'kosong' => 'Kosong'];

$per_halaman   = 8;
$total_kamar   = count($daftar_kamar);
$total_halaman = max(1, (int) ceil($total_kamar / $per_halaman));
$halaman       = min(max(1, (int) ($_GET['hal'] ?? 1)), $total_halaman);
$kamar_halaman = array_slice($daftar_kamar, ($halaman - 1) * $per_halaman, $per_halaman);

function urlHalaman(string $page, int $hal): string
{
    return '/?page=' . urlencode($page) . '&hal=' . $hal;
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Manajemen Kamar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>

<body class="dashboard-body kamar-body">
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../../components/header.php'; ?>

            <div class="kamar-title-card">
                <h2>Manajemen Kamar &amp; Penghuni</h2>
                <a href="/?page=dashboard_pemilik" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>

            <!-- Tombol Navigasi Modal via PHP URL -->
            <div class="kamar-actions">
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_kamar" class="btn-add-outline" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
                    <span class="plus-icon">+</span> Tambah Kamar
                </a>
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_penghuni" class="btn-add-outline" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
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

        <?php require_once __DIR__ . '/../../components/footer.php'; ?>
    </div>

    <!-- ==================== MODAL TAMBAH KAMAR ==================== -->
    <?php if ($modal_aktif === 'tambah_kamar'): ?>
        <div class="modal-overlay active">
            <div class="modal-box">
                <p class="modal-title">Tambah Kamar</p>

                <form action="?page=<?= htmlspecialchars($_GET['page'] ?? 'manajemen_kamar') ?>" method="POST" style="width: 100%; display: flex; flex-direction: column; gap: 1rem;">
                    <div class="modal-form-group">
                        <label class="modal-label" for="nomorKamar">Nomor Kamar</label>
                        <input type="text" id="nomorKamar" name="no_kamar" class="modal-input" placeholder="Contoh: 07" maxlength="5" required>
                    </div>

                    <div class="modal-form-group">
                        <label class="modal-label" for="hargaSewa">Harga Sewa (Rp)</label>
                        <input type="number" id="hargaSewa" name="harga_sewa" class="modal-input" placeholder="Contoh: 450000" min="0" required>
                    </div>

                    <div class="modal-actions">
                        <a href="?page=<?= htmlspecialchars($_GET['page'] ?? 'manajemen_kamar') ?>" class="btn-modal btn-modal-batal">Batal</a>
                        <button type="submit" class="btn-modal btn-modal-simpan">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ==================== MODAL TAMBAH PENGHUNI ==================== -->
    <?php if ($modal_aktif === 'tambah_penghuni'): ?>
        <div class="modal-overlay active">
            <div class="modal-box">
                <p class="modal-title">Tambah Penghuni</p>

                <form action="?page=<?= htmlspecialchars($_GET['page'] ?? 'manajemen_kamar') ?>" method="POST" enctype="multipart/form-data" style="width: 100%; display: flex; flex-direction: column; gap: 0.85rem;">
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
                                    <option value="<?= htmlspecialchars($kamar['nomor']) ?>">
                                        Kamar <?= htmlspecialchars($kamar['nomor']) ?>
                                    </option>
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
                                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#4ca5b0" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"></path>
                                    <polyline points="7 9 12 4 17 9"></polyline>
                                    <line x1="12" y1="4" x2="12" y2="16"></line>
                                </svg>
                            </div>
                            <p class="upload-text">Ketuk untuk pilih foto</p>
                        </label>
                    </div>

                    <div class="modal-actions">
                        <a href="?page=<?= htmlspecialchars($_GET['page'] ?? 'manajemen_kamar') ?>" class="btn-modal btn-modal-batal">Batal</a>
                        <button type="submit" class="btn-modal btn-modal-simpan">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ==================== LAYOUT DESKTOP ==================== -->
    <div class="desktop-dashboard-wrapper">
        <aside class="desktop-sidebar">
            <div>
                <h1 class="sidebar-brand">SITEMAN - KOS</h1>

                <div class="sidebar-profile">
                    <div class="sidebar-avatar"><i class="fa-solid fa-user"></i></div>
                    <span class="sidebar-name"><?= htmlspecialchars($nama_pemilik); ?></span>
                </div>

                <nav class="sidebar-nav">
                    <a href="/?page=dashboard_pemilik" class="nav-item">
                        <i class="fa-solid fa-table-cells-large"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="/?page=manajemen_kamar" class="nav-item active ">
                        <i class="fa-solid fa-warehouse"></i>
                        <span>Manajemen Kamar &amp; Penghuni</span>
                    </a>
                    <a href="/?page=kelola_iuran" class="nav-item">
                        <i class="fa-solid fa-chart-pie"></i>
                        <span>Monitoring &amp; Tagihan Iuran</span>
                    </a>
                    <a href="/?page=verifikasi_pembayaran" class="nav-item">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Verifikasi Pembayaran</span>
                    </a>
                    <a href="/?page=chat_penghuni" class="nav-item">
                        <i class="fa-solid fa-comments"></i>
                        <span>Chat Penghuni</span>
                    </a>
                    <a href="/?page=arus_kas" class="nav-item">
                        <i class="fa-solid fa-wallet"></i>
                        <span>Analisis Kas</span>
                    </a>
                </nav>
            </div>

            <div class="sidebar-footer">
                <p class="sidebar-footer-title">SITEMAN - KOS &copy; 2g2b - 2026</p>
                <p class="sidebar-footer-desc">Sistem Manajemen Operasion Kos</p>
            </div>
        </aside>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Manajemen Kamar &amp; Penghuni</h2>
            <p class="desktop-header-subtitle">Manajemen kamar dan penghuni secara realtime</p>

            <h3 class="kamar-desktop-label">Data terbaru</h3>

            <div class="kamar-desktop-actions">
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_kamar" class="kamar-desktop-btn">
                    <span class="plus">+</span> Tambah Kamar
                </a>
                <a href="/?page=<?= urlencode($current_page) ?>&modal=tambah_penghuni" class="kamar-desktop-btn">
                    <span class="plus">+</span> Tambah Penghuni
                </a>
            </div>

            <section class="kamar-desktop-panel">
                <div class="kamar-desktop-grid">
                    <?php foreach ($kamar_halaman as $kamar): ?>
                        <div class="kamar-desktop-card">
                            <span class="kamar-desktop-badge <?= htmlspecialchars($kamar['status']) ?>">
                                <?= $label_status[$kamar['status']] ?? 'Kosong' ?>
                            </span>
                            <div class="kamar-desktop-nomor"><?= htmlspecialchars($kamar['nomor']) ?></div>
                            <div class="kamar-desktop-nama"><?= htmlspecialchars($kamar['penghuni']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="kamar-desktop-legend">
                    <span><i class="legend-dot lunas"></i> Lunas</span>
                    <span><i class="legend-dot belum"></i> Belum Iuran</span>
                    <span><i class="legend-dot kosong"></i> Kosong</span>
                </div>

                <div class="kamar-desktop-pagination">
                    <a href="<?= urlHalaman($current_page, $halaman - 1) ?>"
                        class="kamar-page-btn <?= $halaman <= 1 ? 'disabled' : '' ?>">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                    <span><?= $halaman ?>/<?= $total_halaman ?></span>
                    <a href="<?= urlHalaman($current_page, $halaman + 1) ?>"
                        class="kamar-page-btn <?= $halaman >= $total_halaman ? 'disabled' : '' ?>">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </section>
        </main>
    </div>
</body>

</html>