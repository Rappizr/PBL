<?php
require_once __DIR__ . '/../../config/init.php';
/** @var PDO $pdo */

// Helper untuk mengambil nama depan saja
if (!function_exists('getNamaDepan')) {
    function getNamaDepan(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? $fullName;
    }
}

// Sinkronisasi Real-Time Nama & Status dari Database (maksimal 1x per request)
if (empty($GLOBALS['siteman_user_synced']) && isset($pdo) && ($pdo instanceof PDO) && !empty($_SESSION['user']['id'])) {
    $uid  = (int)$_SESSION['user']['id'];
    $role = $_SESSION['user']['role'] ?? 'penghuni';

    try {
        if ($role === 'pemilik') {
            $stmtSync = $pdo->prepare("SELECT nama, nama_bank, no_rekening FROM pemilik WHERE id = :id LIMIT 1");
            $stmtSync->execute([':id' => $uid]);
            $freshPem = $stmtSync->fetch(PDO::FETCH_ASSOC);

            if ($freshPem) {
                $_SESSION['user']['nama']        = $freshPem['nama'];
                $_SESSION['user']['nama_bank']   = $freshPem['nama_bank'] ?? null;
                $_SESSION['user']['no_rekening'] = $freshPem['no_rekening'] ?? null;
            }
        } else {
            $stmtSync = $pdo->prepare("SELECT nama, is_ketua_kos, nama_bank, no_rekening FROM penghuni WHERE id = :id LIMIT 1");
            $stmtSync->execute([':id' => $uid]);
            $freshUser = $stmtSync->fetch(PDO::FETCH_ASSOC);

            if ($freshUser) {
                $isKetuaSync = (bool)$freshUser['is_ketua_kos'];
                $_SESSION['user']['nama']         = $freshUser['nama'];
                $_SESSION['user']['is_ketua_kos'] = $isKetuaSync;
                $_SESSION['user']['is_pj']        = $isKetuaSync;
                $_SESSION['user']['nama_bank']    = $isKetuaSync ? ($freshUser['nama_bank'] ?? null) : null;
                $_SESSION['user']['no_rekening']  = $isKetuaSync ? ($freshUser['no_rekening'] ?? null) : null;
            }
        }
        $GLOBALS['siteman_user_synced'] = true;
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

// Cek apakah Ketua Kos wajib melengkapi Nama Bank & Nomor Rekening
$rawQueryHeader = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '';
parse_str($rawQueryHeader, $parsedQueryHeader);
$currentPageHeader = trim((string)($_GET['page'] ?? ($parsedQueryHeader['page'] ?? 'dashboard')));
$ketuaWajibIsiRek  = ($is_pj && $currentPageHeader !== 'profile')
    && (empty(trim((string)($_SESSION['user']['nama_bank'] ?? ''))) || empty(trim((string)($_SESSION['user']['no_rekening'] ?? ''))));

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

    document.addEventListener('DOMContentLoaded', function () {
        <?php if ($ketuaWajibIsiRek): ?>
        const mobileMain = document.querySelector('.mobile-container .page-content') || document.querySelector('.mobile-container');
        const mobileHeader = document.querySelector('.mobile-container .dashboard-header');

        if (mobileMain && !document.getElementById('bannerWajibRekKetuaMobile')) {
            const bannerMob = document.createElement('div');
            bannerMob.id = 'bannerWajibRekKetuaMobile';
            bannerMob.className = 'iuran-notif-banner notif-warning banner-wajib-rek-mobile';
            bannerMob.innerHTML = `
                <div class="iuran-notif-icon">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div class="iuran-notif-body">
                    <strong>Wajib Lengkapi Rekening Kas Iuran</strong>
                    <p>Anda menjabat sebagai Ketua Kos. Silakan lengkapi <b>Nama Bank</b> dan <b>Nomor Rekening</b> di halaman <a href="/?page=profile&modal=ubah_profil" class="link-wajib-rek">Profil Akun</a> agar penghuni dapat melakukan transfer iuran.</p>
                </div>
            `;

            if (mobileMain.classList.contains('page-content')) {
                mobileMain.insertBefore(bannerMob, mobileMain.firstChild);
            } else if (mobileHeader && mobileHeader.nextSibling) {
                mobileHeader.parentNode.insertBefore(bannerMob, mobileHeader.nextSibling);
            }
        }
        <?php endif; ?>
    });
</script>