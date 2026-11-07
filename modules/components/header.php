<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper untuk mengambil nama depan saja
function getNamaDepan(string $fullName): string {
    $parts = explode(' ', trim($fullName));
    return $parts[0] ?? $fullName;
}

// Sinkronisasi Real-Time Nama & Status dari Database
global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    $possible_db = [
        __DIR__ . '/../../config/database.php',
        __DIR__ . '/../../../config/database.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/database.php'
    ];
    foreach ($possible_db as $p) {
        if (file_exists($p)) {
            require_once $p;
            break;
        }
    }
}

if (isset($pdo) && ($pdo instanceof PDO) && !empty($_SESSION['user']['id'])) {
    $uid  = (int)$_SESSION['user']['id'];
    $role = $_SESSION['user']['role'] ?? 'penghuni';

    try {
        if ($role !== 'pemilik') {
            $stmtSync = $pdo->prepare("SELECT nama, is_ketua_kos FROM penghuni WHERE id = :id LIMIT 1");
            $stmtSync->execute([':id' => $uid]);
            $freshUser = $stmtSync->fetch(PDO::FETCH_ASSOC);

            if ($freshUser) {
                $_SESSION['user']['nama']         = $freshUser['nama'];
                $_SESSION['user']['is_ketua_kos'] = (bool)$freshUser['is_ketua_kos'];
                $_SESSION['user']['is_pj']        = (bool)$freshUser['is_ketua_kos'];
            }
        }
    } catch (Exception $e) {}
}

// Ambil session user yang sudah diperbarui
$currentUser = $_SESSION['user'] ?? [
    'role'  => 'penghuni',
    'nama'  => 'Anak Kos',
    'kamar' => null,
    'is_pj' => false
];

$is_pemilik = ($currentUser['role'] ?? '') === 'pemilik';
$is_pj      = !$is_pemilik && (!empty($currentUser['is_pj']) || !empty($currentUser['is_ketua_kos']) || ($currentUser['role'] ?? '') === 'pj');

// Ambil Nama Depan Penghuni
$namaDisplay = getNamaDepan($currentUser['nama'] ?? 'Penghuni');
?>

<header class="dashboard-header">
    <h1 class="brand-title-dashboard">SITEMAN&nbsp;-&nbsp;KOS</h1>

    <div class="header-user-actions">
        <?php if ($is_pemilik): ?>
            <a href="/?page=profile" class="kotak-kapsul-pemilik header-user-link" title="<?= htmlspecialchars($currentUser['nama'] ?? 'Bapak Kos'); ?>">
                <span class="lingkaran-user-abu">
                    <i class="fa-solid fa-user"></i>
                </span>
                <span class="tulisan-nama"><?= htmlspecialchars($namaDisplay); ?></span>
            </a>

        <?php elseif ($is_pj): ?>
            <a href="/?page=profile" class="kotak-kapsul-pemilik badge-pj-header header-user-link" title="<?= htmlspecialchars($currentUser['nama'] ?? 'Ketua Kos'); ?>">
                <span class="lingkaran-user-abu">
                    <i class="fa-solid fa-user-shield"></i>
                </span>
                <span class="tulisan-nama">
                    <?= htmlspecialchars($namaDisplay); ?>
                    <?= !empty($currentUser['kamar']) ? '(' . htmlspecialchars($currentUser['kamar']) . ')' : ''; ?>
                </span>
            </a>

        <?php else: ?>
            <a href="/?page=profile" class="kotak-kapsul-pemilik badge-penghuni-header header-user-link" title="<?= htmlspecialchars($currentUser['nama'] ?? 'Penghuni'); ?>">
                <span class="lingkaran-user-abu">
                    <i class="fa-solid fa-user"></i>
                </span>
                <span class="tulisan-nama">
                    <?= htmlspecialchars($namaDisplay); ?>
                </span>
            </a>
        <?php endif; ?>

        <!-- Tombol Ikon Keluar di Sebelah Kanan Profil Mobile -->
        <button type="button" class="btn-logout-mobile" onclick="bukaModalLogout()" title="Keluar">
            <i class="fa-solid fa-right-from-bracket"></i>
        </button>
    </div>
</header>

<!-- ELEMEN MODAL POP-UP KONFIRMASI LOGOUT MOBILE -->
<div id="modalKonfirmasiLogout" class="modal-overlay" onclick="tutupModalLogout()">
    <div class="modal-card-konfirmasi-kamar" onclick="event.stopPropagation()">
        <div class="konfirmasi-icon-wrapper" id="logoutIconBoxHeader">
            <i class="fa-solid fa-right-from-bracket"></i>
        </div>
        <h3 class="konfirmasi-title">Yakin ingin keluar?</h3>
        <p class="konfirmasi-desc">
            Sesi akun Anda akan diakhiri dan Anda harus masuk kembali untuk mengakses sistem.
        </p>
        <div class="konfirmasi-actions">
            <button type="button" class="btn-konfirmasi-batal" id="btnBatalLogoutHeader" onclick="tutupModalLogout()">Batal</button>
            <a href="/?page=logout" class="btn-konfirmasi-hapus btn-ya-keluar" onclick="prosesLogoutAnimasi(event, this, 'logoutIconBoxHeader', 'btnBatalLogoutHeader')">
                <span>Ya, Keluar</span>
            </a>
        </div>
    </div>
</div>

<script>
    if (typeof bukaModalLogout !== 'function') {
        function bukaModalLogout() {
            const modal = document.getElementById('modalKonfirmasiLogout');
            if (modal) modal.classList.add('active');
        }

        function tutupModalLogout() {
            const modal = document.getElementById('modalKonfirmasiLogout');
            if (modal) modal.classList.remove('active');
        }

        function prosesLogoutAnimasi(e, el, iconId, batalId) {
            e.preventDefault();
            const targetUrl = el.getAttribute('href');
            
            const iconBox = document.getElementById(iconId);
            if (iconBox) {
                iconBox.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
            }
            
            el.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Keluar...';
            el.style.pointerEvents = 'none';
            el.style.opacity = '0.85';
            
            const btnBatal = document.getElementById(batalId);
            if (btnBatal) btnBatal.style.display = 'none';

            setTimeout(function() {
                window.location.href = targetUrl;
            }, 300);
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') tutupModalLogout();
        });
    }
</script>