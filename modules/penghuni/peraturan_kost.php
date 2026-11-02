<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sertakan logika dan data dari shared/tatibkos.php
require_once __DIR__ . '/../shared/tatibkos.php';

$current_page = $_GET['page'] ?? 'peraturan_kost';
$modal_type   = $_GET['modal'] ?? null; // 'tambah', 'edit', 'hapus'
$edit_id      = intval($_GET['id'] ?? 0);

$edit_data = null;
if ($modal_type === 'edit' || $modal_type === 'hapus') {
    foreach ($_SESSION['daftar_peraturan'] as $p) {
        if ($p['id'] === $edit_id) {
            $edit_data = $p;
            break;
        }
    }
}

// Daftar opsi ikon dummy yang bisa dipilih pemilik kos
$icon_options = [
    'fa-solid fa-clock'          => 'Jam / Waktu',
    'fa-solid fa-wallet'         => 'Dompet / Iuran',
    'fa-solid fa-broom'          => 'Kebersihan',
    'fa-solid fa-smoking'        => 'Merokok',
    'fa-solid fa-utensils'       => 'Dapur / Makan',
    'fa-solid fa-car-side'       => 'Parkir / Kendaraan',
    'fa-solid fa-volume-xmark'   => 'Kebisingan / Suara',
    'fa-solid fa-shirt'          => 'Jemuran Pakaian',
    'fa-solid fa-shield-halved'  => 'Keamanan',
    'fa-solid fa-circle-info'    => 'Informasi Umum'
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Peraturan Kos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= time(); ?>">
</head>
<body class="dashboard-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <main class="page-content">
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title">Peraturan Kos</h2>
                    <a href="/?page=dashboard_penghuni" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="peraturan-card-wrapper">
                    <?php foreach ($_SESSION['daftar_peraturan'] as $rule): ?>
                        <div class="peraturan-item">
                            <div class="peraturan-icon-col">
                                <i class="<?= htmlspecialchars($rule['icon']) ?>"></i>
                            </div>
                            <div class="peraturan-content">
                                <h3 class="peraturan-title"><?= htmlspecialchars($rule['judul']) ?></h3>
                                <p class="peraturan-desc"><?= htmlspecialchars($rule['deskripsi']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>

        <?php require_once __DIR__ . '/../components/footer.php'; ?>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="desktop-dashboard-wrapper">
        <?php require_once __DIR__ . '/../components/sidebar.php'; ?>

        <main class="desktop-content">
            <h2 class="desktop-header-title">Peraturan Kos</h2>
            <p class="peraturan-desktop-subtitle" style="margin-bottom: 20px;">Tata tertib yang berlaku untuk seluruh penghuni kos</p>

            <!-- Tombol Tambah Peraturan -->
            <a href="/?page=peraturan_kost&modal=tambah" class="iuran-btn-pill-action" style="display: inline-flex; align-items: center; gap: 8px; margin-bottom: 24px; text-decoration: none;">
                <i class="fa-solid fa-plus"></i> Tambah Peraturan
            </a>

            <!-- List Peraturan Desktop dengan Tombol Aksi -->
            <div class="desktop-rules-container" style="max-width: 824px; display: flex; flex-direction: column; gap: 16px;">
                <?php if (empty($_SESSION['daftar_peraturan'])): ?>
                    <div class="empty-state-card" style="width: 100%;">
                        <i class="fa-solid fa-folder-open"></i>
                        <span>Belum ada peraturan kos yang ditambahkan.</span>
                    </div>
                <?php else: ?>
                    <?php foreach ($_SESSION['daftar_peraturan'] as $p): ?>
                        <div class="rule-item" style="display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                            <div style="display: flex; align-items: center; gap: 16px; width: 80%;">
                                <div class="rule-icon-box" style="font-size: 24px; color: #0056B3; background: #e0f2fe; padding: 12px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                    <i class="<?= htmlspecialchars($p['icon']) ?>"></i>
                                </div>
                                <div class="rule-text-box">
                                    <h4 class="rule-title" style="margin: 0 0 4px 0; font-size: 16px; color: #1e293b;"><?= htmlspecialchars($p['judul']) ?></h4>
                                    <p class="rule-desc" style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.4;"><?= htmlspecialchars($p['deskripsi']) ?></p>
                                </div>
                            </div>
                            <!-- Ikon Aksi Edit (Pena) & Hapus (Tempat Sampah) -->
                            <div style="display: flex; gap: 16px;">
                                <a href="/?page=peraturan_kost&modal=edit&id=<?= $p['id']; ?>" title="Edit" style="color: #0056B3; font-size: 16px;"><i class="fa-solid fa-pen-to-square"></i></a>
                                <a href="/?page=peraturan_kost&modal=hapus&id=<?= $p['id']; ?>" title="Hapus" style="color: #dc2626; font-size: 16px;"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- MODAL POP-UP TAMBAH PERATURAN -->
    <?php if ($modal_type === 'tambah'): ?>
        <div class="modal-overlay active" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;">
            <div class="modal-card" style="background: #fef08a; padding: 24px; border-radius: 16px; width: 100%; max-width: 450px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                <h3 class="modal-title" style="margin-top: 0; color: #1e293b; font-size: 18px; font-weight: bold; margin-bottom: 16px;">Tambah Peraturan Kos</h3>
                <form action="/?page=peraturan_kost" method="POST" class="modal-form">
                    <input type="hidden" name="action" value="tambah">
                    <div class="modal-field" style="margin-bottom: 14px;">
                        <label class="modal-label" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Judul</label>
                        <input type="text" name="judul" class="modal-input" placeholder="Contoh: Jam Malam" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff;">
                    </div>
                    <div class="modal-field" style="margin-bottom: 14px;">
                        <label class="modal-label" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Deskripsi</label>
                        <textarea name="deskripsi" class="modal-input" placeholder="Deskripsi peraturan..." rows="3" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff;"></textarea>
                    </div>
                    <div class="modal-field" style="margin-bottom: 20px;">
                        <label class="modal-label" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Icon (FontAwesome Class)</label>
                        <input type="text" name="icon" class="modal-input" placeholder="fa-solid fa-clock" value="fa-solid fa-circle-info" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff;">
                    </div>
                    <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 10px;">
                        <a href="/?page=peraturan_kost" class="btn-modal-cancel" style="padding: 8px 16px; background: #fff; border-radius: 20px; text-decoration: none; color: #0056B3; font-weight: 600;">Batal</a>
                        <button type="submit" class="btn-modal-submit" style="padding: 8px 20px; background: #0ea5e9; color: #fff; border: none; border-radius: 20px; font-weight: 600; cursor: pointer;">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL POP-UP EDIT PERATURAN -->
    <?php if ($modal_type === 'edit' && $edit_data): ?>
        <div class="modal-overlay active" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;">
            <div class="modal-card" style="background: #fef08a; padding: 24px; border-radius: 16px; width: 100%; max-width: 450px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                <h3 class="modal-title" style="margin-top: 0; color: #1e293b; font-size: 18px; font-weight: bold; margin-bottom: 16px;">Edit Peraturan Kos</h3>
                <form action="/?page=peraturan_kost" method="POST" class="modal-form">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $edit_data['id']; ?>">
                    <div class="modal-field" style="margin-bottom: 14px;">
                        <label class="modal-label" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Judul</label>
                        <input type="text" name="judul" class="modal-input" value="<?= htmlspecialchars($edit_data['judul']); ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff;">
                    </div>
                    <div class="modal-field" style="margin-bottom: 14px;">
                        <label class="modal-label" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Deskripsi</label>
                        <textarea name="deskripsi" class="modal-input" rows="3" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff;"><?= htmlspecialchars($edit_data['deskripsi']); ?></textarea>
                    </div>
                    <div class="modal-field" style="margin-bottom: 20px;">
                        <label class="modal-label" style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Icon</label>
                        <input type="text" name="icon" class="modal-input" value="<?= htmlspecialchars($edit_data['icon']); ?>" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #fff;">
                    </div>
                    <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 10px;">
                        <a href="/?page=peraturan_kost" class="btn-modal-cancel" style="padding: 8px 16px; background: #fff; border-radius: 20px; text-decoration: none; color: #0056B3; font-weight: 600;">Batal</a>
                        <button type="submit" class="btn-modal-submit" style="padding: 8px 20px; background: #0ea5e9; color: #fff; border: none; border-radius: 20px; font-weight: 600; cursor: pointer;">Update</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- MODAL POP-UP KONFIRMASI HAPUS -->
    <?php if ($modal_type === 'hapus' && $edit_data): ?>
        <div class="modal-overlay active" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;">
            <div class="modal-card" style="background: #ffffff; padding: 24px; border-radius: 16px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); text-align: center;">
                <h3 class="modal-title" style="margin-top: 0; color: #dc2626; font-size: 18px; font-weight: bold; margin-bottom: 12px;">Hapus Peraturan?</h3>
                <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">Apakah kamu yakin ingin menghapus peraturan "<strong><?= htmlspecialchars($edit_data['judul']); ?></strong>"?</p>
                <form action="/?page=peraturan_kost" method="POST">
                    <input type="hidden" name="action" value="hapus">
                    <input type="hidden" name="id" value="<?= $edit_data['id']; ?>">
                    <div class="modal-actions" style="display: flex; justify-content: center; gap: 12px;">
                        <a href="/?page=peraturan_kost" class="btn-modal-cancel" style="padding: 8px 20px; background: #e2e8f0; border-radius: 20px; text-decoration: none; color: #334155; font-weight: 600;">Batal</a>
                        <button type="submit" class="btn-modal-submit" style="padding: 8px 20px; background: #dc2626; color: #fff; border: none; border-radius: 20px; font-weight: 600; cursor: pointer;">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>