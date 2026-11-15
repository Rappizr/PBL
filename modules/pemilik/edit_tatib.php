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
$modal         = $_GET['modal'] ?? '';
$id_aktif      = (int) ($_GET['id'] ?? 0);

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
          && mb_strlen($judul) <= 255 && mb_strlen($deskripsi) <= 255;

    try {
        if ($aksi === 'tambah' && $valid) {
            $stmt = $pdo->prepare('INSERT INTO peraturan_kos (peraturan, deskripsi, icon, create_time) VALUES (:judul, :deskripsi, :ikon, CURRENT_DATE)');
            $stmt->execute([':judul' => $judul, ':deskripsi' => $deskripsi, ':ikon' => $ikon]);
            $_SESSION['flash_tatib'] = ['tipe' => 'sukses', 'teks' => 'Peraturan berhasil ditambahkan.'];

        } elseif ($aksi === 'edit' && $id_post > 0 && $valid) {
            $stmt = $pdo->prepare('UPDATE peraturan_kos SET peraturan = :judul, deskripsi = :deskripsi, icon = :ikon WHERE id = :id');
            $stmt->execute([':judul' => $judul, ':deskripsi' => $deskripsi, ':ikon' => $ikon, ':id' => $id_post]);
            $_SESSION['flash_tatib'] = ['tipe' => 'sukses', 'teks' => 'Peraturan berhasil diperbarui.'];

        } elseif ($aksi === 'hapus' && $id_post > 0) {
            $stmt = $pdo->prepare('DELETE FROM peraturan_kos WHERE id = :id');
            $stmt->execute([':id' => $id_post]);
            $_SESSION['flash_tatib'] = ['tipe' => 'sukses', 'teks' => 'Peraturan berhasil dihapus.'];

        } else {
            $_SESSION['flash_tatib'] = ['tipe' => 'gagal', 'teks' => 'Data tidak valid atau melebihi batas karakter.'];
        }
    } catch (PDOException $e) {
        $_SESSION['flash_tatib'] = ['tipe' => 'gagal', 'teks' => 'Gagal menyimpan ke database: ' . $e->getMessage()];
    }

    header('Location: /?page=edit_tatib');
    exit;
}

$peraturan_raw  = $pdo->query('SELECT id, peraturan AS judul, deskripsi, icon FROM peraturan_kos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$peraturan_list = [];
foreach ($peraturan_raw as $r) {
    if (empty($r['icon'])) {
        $r['icon'] = 'fa-solid fa-clock';
    }
    $peraturan_list[] = $r;
}

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
    <link rel="stylesheet" href="/css/global.css?v=<?= filemtime(__DIR__ . '/../../public/css/global.css'); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= filemtime(__DIR__ . '/../../public/css/modules.css'); ?>">
</head>

<body class="dashboard-body">

    <!-- LAYOUT MOBILE -->
    <div class="mobile-container mobile-dashboard-view">
        <div>
            <?php require_once __DIR__ . '/../components/header.php'; ?>

            <main class="page-content">
                <div class="subpage-banner-card">
                    <h2 class="subpage-banner-title">Manajemen Peraturan Kos</h2>
                    <a href="/?page=dashboard_pemilik" class="btn-kembali-banner">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>

                <?php if ($pesan): ?>
                <p class="peraturan-flash <?= $pesan['tipe'] === 'sukses' ? 'sukses' : 'gagal'; ?>">
                    <?= htmlspecialchars($pesan['teks']); ?>
                </p>
                <?php endif; ?>

                <div class="peraturan-toolbar">
                    <a href="/?page=edit_tatib&modal=tambah_peraturan" onclick="bukaModalTambahTatib(event)" class="btn-tambah-tatib-mobile">
                        <i class="fa-solid fa-plus"></i> Tambah Peraturan
                    </a>
                </div>

                <div class="peraturan-card-wrapper">
                    <?php if (empty($peraturan_list)): ?>
                    <p class="peraturan-kosong">Belum ada peraturan. Klik "Tambah Peraturan" untuk membuat yang pertama.</p>
                    <?php endif; ?>

                    <?php foreach ($peraturan_list as $rule): ?>
                    <div class="peraturan-item-mobile">
                        <div class="peraturan-mobile-left">
                            <div class="peraturan-icon-col">
                                <i class="<?= htmlspecialchars($rule['icon']) ?>"></i>
                            </div>
                            <div class="peraturan-content">
                                <h3 class="peraturan-title"><?= htmlspecialchars($rule['judul']) ?></h3>
                                <p class="peraturan-desc"><?= htmlspecialchars($rule['deskripsi']) ?></p>
                            </div>
                        </div>

                        <div class="peraturan-mobile-actions">
                            <a href="/?page=edit_tatib&modal=edit_peraturan&id=<?= (int)$rule['id']; ?>"
                                onclick="bukaModalEditTatib(event, <?= (int)$rule['id']; ?>, <?= htmlspecialchars(json_encode($rule['judul']), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode($rule['deskripsi']), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode($rule['icon']), ENT_QUOTES, 'UTF-8'); ?>)"
                                class="btn-pill-edit-mobile">
                                <i class="fa-solid fa-pen"></i> Edit
                            </a>
                            <a href="/?page=edit_tatib&modal=hapus_peraturan&id=<?= (int)$rule['id']; ?>"
                                onclick="bukaModalHapusTatib(event, <?= (int)$rule['id']; ?>, <?= htmlspecialchars(json_encode($rule['judul']), ENT_QUOTES, 'UTF-8'); ?>)"
                                class="btn-pill-hapus-mobile">
                                <i class="fa-solid fa-trash-can"></i> Hapus
                            </a>
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
            <p class="peraturan-desktop-subtitle">Mengatur Peraturan Kos</p>

            <?php if ($pesan): ?>
            <p class="peraturan-flash <?= $pesan['tipe'] === 'sukses' ? 'sukses' : 'gagal'; ?>">
                <?= htmlspecialchars($pesan['teks']); ?>
            </p>
            <?php endif; ?>

            <div class="peraturan-toolbar">
                <a href="/?page=edit_tatib&modal=tambah_peraturan" onclick="bukaModalTambahTatib(event)" class="btn-tambah-tatib-mobile">
                    <i class="fa-solid fa-plus"></i> Tambah Peraturan
                </a>
            </div>

            <!-- TABEL DESKTOP -->
            <div class="tatib-table-card">
                <div class="tatib-table-header">
                    <span>Peraturan</span>
                    <span class="col-action">Aksi</span>
                </div>

                <div class="tatib-table-body">
                    <?php if (empty($peraturan_list)): ?>
                    <p class="peraturan-kosong">Belum ada peraturan. Klik "Tambah Peraturan" untuk membuat yang pertama.</p>
                    <?php endif; ?>

                    <?php foreach ($peraturan_list as $p): ?>
                    <div class="tatib-table-row">
                        <div class="tatib-row-left">
                            <div class="tatib-icon-circle">
                                <i class="<?= htmlspecialchars($p['icon']) ?>"></i>
                            </div>
                            <div class="tatib-row-text">
                                <h4><?= htmlspecialchars($p['judul']) ?></h4>
                                <p><?= htmlspecialchars($p['deskripsi']) ?></p>
                            </div>
                        </div>

                        <div class="tatib-row-actions">
                            <a href="/?page=edit_tatib&modal=edit_peraturan&id=<?= (int) $p['id']; ?>"
                                onclick="bukaModalEditTatib(event, <?= (int)$p['id']; ?>, <?= htmlspecialchars(json_encode($p['judul']), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode($p['deskripsi']), ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars(json_encode($p['icon']), ENT_QUOTES, 'UTF-8'); ?>)"
                                class="btn-pill-edit-desktop">
                                <i class="fa-solid fa-pen"></i> Edit
                            </a>
                            <a href="/?page=edit_tatib&modal=hapus_peraturan&id=<?= (int) $p['id']; ?>"
                                onclick="bukaModalHapusTatib(event, <?= (int)$p['id']; ?>, <?= htmlspecialchars(json_encode($p['judul']), ENT_QUOTES, 'UTF-8'); ?>)"
                                class="btn-pill-hapus-desktop">
                                <i class="fa-solid fa-trash-can"></i> Hapus
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH / EDIT (INSTAN TANPA RELOAD) -->
    <div id="modalFormTatib" class="modal-overlay <?= $mode_form ? 'active' : ''; ?>" onclick="tutupModalTatib(event)">
        <div class="modal-card" onclick="event.stopPropagation()">
            <h3 class="modal-title" id="modalTatibTitle"><?= $mode_form === 'edit' ? 'Edit Peraturan' : 'Tambah Peraturan'; ?></h3>

            <form action="/?page=edit_tatib" method="POST" class="modal-form">
                <input type="hidden" name="aksi" id="tatibAksi" value="<?= $mode_form ?? 'tambah'; ?>">
                <input type="hidden" name="id" id="tatibId" value="<?= $mode_form === 'edit' ? (int) $peraturan_aktif['id'] : 0; ?>">

                <div class="modal-field">
                    <label class="modal-label" for="judul">Judul</label>
                    <input type="text" id="judul" name="judul" class="modal-input" maxlength="255" required
                        value="<?= $mode_form === 'edit' ? htmlspecialchars($peraturan_aktif['judul']) : ''; ?>">
                </div>

                <div class="modal-field">
                    <label class="modal-label" for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" class="modal-input" rows="4" maxlength="255"
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
                    <button type="button" onclick="tutupModalTatib(event)" class="btn-modal-cancel">Batal</button>
                    <button type="submit" class="btn-modal-submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS (INSTAN TANPA RELOAD) -->
    <div id="modalHapusTatib" class="modal-overlay <?= ($modal === 'hapus_peraturan' && $peraturan_aktif) ? 'active' : ''; ?>" onclick="tutupModalTatib(event)">
        <div class="modal-card" onclick="event.stopPropagation()">
            <h3 class="modal-title">Hapus Peraturan</h3>
            <p class="modal-konfirmasi">
                Yakin ingin menghapus <strong id="tatibHapusNama"><?= htmlspecialchars($peraturan_aktif['judul'] ?? ''); ?></strong>?
                Tindakan ini tidak bisa dibatalkan.
            </p>

            <form action="/?page=edit_tatib" method="POST" class="modal-form">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="id" id="tatibHapusId" value="<?= (int) ($peraturan_aktif['id'] ?? 0); ?>">
                <div class="modal-actions">
                    <button type="button" onclick="tutupModalTatib(event)" class="btn-modal-cancel">Batal</button>
                    <button type="submit" class="btn-modal-submit btn-konfirmasi-hapus">Hapus</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function bukaModalTambahTatib(e) {
            if (e) e.preventDefault();
            document.getElementById('modalTatibTitle').textContent = 'Tambah Peraturan';
            document.getElementById('tatibAksi').value = 'tambah';
            document.getElementById('tatibId').value = '0';
            document.getElementById('judul').value = '';
            document.getElementById('deskripsi').value = '';
            document.getElementById('ikon').selectedIndex = 0;

            const modal = document.getElementById('modalFormTatib');
            modal.classList.add('active');
            setTimeout(() => document.getElementById('judul').focus(), 50);

            const url = new URL(window.location.href);
            url.searchParams.set('modal', 'tambah_peraturan');
            url.searchParams.delete('id');
            window.history.replaceState({}, '', url);
        }

        function bukaModalEditTatib(e, id, judul, deskripsi, ikon) {
            if (e) e.preventDefault();
            document.getElementById('modalTatibTitle').textContent = 'Edit Peraturan';
            document.getElementById('tatibAksi').value = 'edit';
            document.getElementById('tatibId').value = id;
            document.getElementById('judul').value = judul;
            document.getElementById('deskripsi').value = deskripsi;
            document.getElementById('ikon').value = ikon;

            const modal = document.getElementById('modalFormTatib');
            modal.classList.add('active');
            setTimeout(() => document.getElementById('judul').focus(), 50);

            const url = new URL(window.location.href);
            url.searchParams.set('modal', 'edit_peraturan');
            url.searchParams.set('id', id);
            window.history.replaceState({}, '', url);
        }

        function bukaModalHapusTatib(e, id, judul) {
            if (e) e.preventDefault();
            document.getElementById('tatibHapusId').value = id;
            document.getElementById('tatibHapusNama').textContent = judul;

            document.getElementById('modalHapusTatib').classList.add('active');

            const url = new URL(window.location.href);
            url.searchParams.set('modal', 'hapus_peraturan');
            url.searchParams.set('id', id);
            window.history.replaceState({}, '', url);
        }

        function tutupModalTatib(e) {
            if (e) e.preventDefault();
            document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));

            const url = new URL(window.location.href);
            url.searchParams.delete('modal');
            url.searchParams.delete('id');
            window.history.replaceState({}, '', url);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') tutupModalTatib();
        });
    </script>

</body>

</html>