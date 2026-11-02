<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

$role_user = $_SESSION['user']['role'] ?? 'penghuni';
if ($role_user !== 'pemilik') {
    header('Location: /?page=dashboard_penghuni');
    exit;
}

$nama_user     = $_SESSION['user']['nama'] ?? 'Bapak Kos';
$halaman_aktif = 'edit_tatib';
$modal    = $_GET['modal'] ?? '';
$id_aktif = (int) ($_GET['id'] ?? 0);

$pilihan_ikon = [
    'fa-solid fa-clock'        => 'Jam / waktu',
    'fa-solid fa-user-group'   => 'Tamu',
    'fa-solid fa-receipt'      => 'Pembayaran',
    'fa-solid fa-broom'        => 'Kebersihan',
    'fa-solid fa-utensils'     => 'Dapur',
    'fa-solid fa-car-side'     => 'Parkir',
    'fa-solid fa-volume-xmark' => 'Ketenangan',
    'fa-solid fa-shirt'        => 'Jemuran',
    'fa-solid fa-smoking'      => 'Merokok',
];

$pesan = $_SESSION['flash_tatib'] ?? null;
unset($_SESSION['flash_tatib']);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi      = $_POST['aksi'] ?? '';
    $id_post   = (int) ($_POST['id'] ?? 0);
    $judul     = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $ikon      = $_POST['ikon'] ?? '';

    if (!isset($pilihan_ikon[$ikon])) {
        $ikon = 'fa-solid fa-circle-info';
    }

    $valid = $judul !== '' && $deskripsi !== ''
          && mb_strlen($judul) <= 80 && mb_strlen($deskripsi) <= 400;

    try {
        if ($aksi === 'tambah' && $valid) {
            // CREATE
            $stmt = $pdo->prepare('INSERT INTO tatib (judul, deskripsi, ikon) VALUES (:judul, :deskripsi, :ikon)');
            $stmt->execute([':judul' => $judul, ':deskripsi' => $deskripsi, ':ikon' => $ikon]);
            $_SESSION['flash_tatib'] = ['tipe' => 'sukses', 'teks' => 'Peraturan berhasil ditambahkan.'];

        } elseif ($aksi === 'edit' && $id_post > 0 && $valid) {
            // UPDATE
            $stmt = $pdo->prepare('UPDATE tatib SET judul = :judul, deskripsi = :deskripsi, ikon = :ikon WHERE id = :id');
            $stmt->execute([':judul' => $judul, ':deskripsi' => $deskripsi, ':ikon' => $ikon, ':id' => $id_post]);
            $_SESSION['flash_tatib'] = ['tipe' => 'sukses', 'teks' => 'Peraturan berhasil diperbarui.'];

        } elseif ($aksi === 'hapus' && $id_post > 0) {
            // DELETE
            $stmt = $pdo->prepare('DELETE FROM tatib WHERE id = :id');
            $stmt->execute([':id' => $id_post]);
            $_SESSION['flash_tatib'] = ['tipe' => 'sukses', 'teks' => 'Peraturan berhasil dihapus.'];

        } else {
            $_SESSION['flash_tatib'] = ['tipe' => 'gagal', 'teks' => 'Data tidak valid. Judul maks. 80 dan deskripsi maks. 400 karakter.'];
        }
    } catch (PDOException $e) {
        $_SESSION['flash_tatib'] = ['tipe' => 'gagal', 'teks' => 'Gagal menyimpan ke database.'];
    }

    header('Location: /?page=edit_tatib');
    exit;
}

$peraturan_list = $pdo->query('SELECT id, peraturan AS judul, deskripsi FROM peraturan_kos ORDER BY id')->fetchAll();

$peraturan_aktif = null;
foreach ($peraturan_list as $r) {
    if ((int) $r['id'] === $id_aktif) {
        $peraturan_aktif = $r;
        break;
    }
}

$mode_form = null;
if ($modal === 'tambah_peraturan') {
    $mode_form = 'tambah';
} elseif ($modal === 'edit_peraturan' && $peraturan_aktif) {
    $mode_form = 'edit';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Manajemen Peraturan Kos</title>
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
                    <h2 class="subpage-banner-title">Manajemen Peraturan Kos</h2>
                    <!-- [DIUBAH] tombol Kembali ke dashboard pemilik (sebelumnya dashboard_penghuni) -->
                    <a href="/?page=dashboard_pemilik" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="peraturan-card-wrapper">
                    <?php foreach ($peraturan_list as $rule): ?>
                    <div class="peraturan-item">
                        <div class="peraturan-icon-col">
                            <!-- [DIUBAH] ikon kini di-escape dengan htmlspecialchars -->
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
            <h2 class="desktop-header-title">Manajemen Peraturan Kos</h2>
            <p class="peraturan-desktop-subtitle">Peraturan kos wajib ditaati oleh seluruh penghuni</p>

            <!-- [BARU] Pesan hasil aksi (berhasil/gagal) -->
            <?php if ($pesan): ?>
            <p class="peraturan-flash <?= $pesan['tipe'] === 'sukses' ? 'sukses' : 'gagal'; ?>">
                <?= htmlspecialchars($pesan['teks']); ?>
            </p>
            <?php endif; ?>

            <!-- [BARU] Tombol Tambah Peraturan -->
            <div class="peraturan-toolbar">
                <a href="/?page=edit_tatib&modal=tambah_peraturan" class="peraturan-tambah-btn">
                    <span class="plus"><i class="fa-solid fa-plus"></i></span> Tambah Peraturan
                </a>
            </div>

            <div class="desktop-rules-container">
                <!-- [BARU] Tampilan kalau belum ada peraturan sama sekali -->
                <?php if (empty($peraturan_list)): ?>
                <p class="peraturan-kosong">Belum ada peraturan. Klik "Tambah Peraturan" untuk membuat yang pertama.</p>
                <?php endif; ?>

                <?php foreach ($peraturan_list as $p): ?>
                <div class="rule-item">
                    <div class="rule-icon-box">
                        <i class="<?= htmlspecialchars($p['icon']) ?>"></i>
                    </div>
                    <div class="rule-text-box">
                        <h4 class="rule-title"><?= htmlspecialchars($p['judul']) ?></h4>
                        <p class="rule-desc"><?= htmlspecialchars($p['deskripsi']) ?></p>
                    </div>

                    <!-- [BARU] Ikon edit dan hapus (kanan atas tiap peraturan) -->
                    <div class="rule-actions">
                        <a href="/?page=edit_tatib&modal=edit_peraturan&id=<?= (int) $p['id']; ?>"
                            class="rule-action-btn" title="Edit"
                            aria-label="Edit peraturan <?= htmlspecialchars($p['judul']); ?>">
                            <i class="fa-solid fa-pen"></i>
                        </a>
                        <a href="/?page=edit_tatib&modal=hapus_peraturan&id=<?= (int) $p['id']; ?>"
                            class="rule-action-btn hapus" title="Hapus"
                            aria-label="Hapus peraturan <?= htmlspecialchars($p['judul']); ?>">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- [BARU] MODAL TAMBAH / EDIT (satu form untuk dua fungsi) -->
    <?php if ($mode_form): ?>
    <div class="modal-overlay active">
        <div class="modal-card">
            <h3 class="modal-title"><?= $mode_form === 'tambah' ? 'Tambah Peraturan' : 'Edit Peraturan'; ?></h3>

            <form action="/?page=edit_tatib" method="POST" class="modal-form">
                <input type="hidden" name="aksi" value="<?= $mode_form; ?>">
                <?php if ($mode_form === 'edit'): ?>
                <input type="hidden" name="id" value="<?= (int) $peraturan_aktif['id']; ?>">
                <?php endif; ?>

                <div class="modal-field">
                    <label class="modal-label" for="judul">Judul</label>
                    <input type="text" id="judul" name="judul" class="modal-input" maxlength="80" required
                        value="<?= $mode_form === 'edit' ? htmlspecialchars($peraturan_aktif['judul']) : ''; ?>">
                </div>

                <div class="modal-field">
                    <label class="modal-label" for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" class="modal-input" rows="4" maxlength="400"
                        required><?= $mode_form === 'edit' ? htmlspecialchars($peraturan_aktif['deskripsi']) : ''; ?></textarea>
                </div>

                <div class="modal-field">
                    <label class="modal-label" for="ikon">Ikon</label>
                    <select id="ikon" name="ikon" class="modal-input">
                        <?php foreach ($pilihan_ikon as $kelas => $nama): ?>
                        <option value="<?= $kelas; ?>"
                            <?= ($mode_form === 'edit' && $peraturan_aktif['icon'] === $kelas) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($nama); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-actions">
                    <a href="/?page=edit_tatib" class="btn-modal-cancel">Batal</a>
                    <button type="submit" class="btn-modal-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- [BARU] MODAL KONFIRMASI HAPUS (dikirim lewat POST, bukan link biasa) -->
    <?php if ($modal === 'hapus_peraturan' && $peraturan_aktif): ?>
    <div class="modal-overlay active">
        <div class="modal-card">
            <h3 class="modal-title">Hapus Peraturan</h3>
            <p class="modal-konfirmasi">
                Yakin ingin menghapus <strong><?= htmlspecialchars($peraturan_aktif['judul']); ?></strong>?
                Tindakan ini tidak bisa dibatalkan.
            </p>

            <form action="/?page=edit_tatib" method="POST" class="modal-form">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" value="<?= (int) $peraturan_aktif['id']; ?>">
                <div class="modal-actions">
                    <a href="/?page=edit_tatib" class="btn-modal-cancel">Batal</a>
                    <button type="submit" class="btn-modal-submit">Hapus</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</body>

</html>