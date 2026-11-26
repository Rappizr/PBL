<?php
// modules/components/sidebar.php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/notif_helper.php';
/** @var PDO $pdo */

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

$role_user = $_SESSION['user']['role'] ?? 'penghuni';
$is_pj     = !empty($_SESSION['user']['is_pj']) || !empty($_SESSION['user']['is_ketua_kos']);
$nama_user = $_SESSION['user']['nama'] ?? ($role_user === 'pemilik' ? 'Bapak Kos' : 'Penghuni');

// Ambil parameter page secara akurat dari $_GET maupun REQUEST_URI
$rawQuery = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?? '';
parse_str($rawQuery, $parsedQuery);
$currentPage = trim((string)($_GET['page'] ?? ($parsedQuery['page'] ?? ($current_page ?? 'dashboard'))));
if ($currentPage === '') {
    $currentPage = 'dashboard';
}

$ketuaWajibIsiRek = ($role_user !== 'pemilik' && $is_pj && $currentPage !== 'profile')
    && (empty(trim((string)($_SESSION['user']['nama_bank'] ?? ''))) || empty(trim((string)($_SESSION['user']['no_rekening'] ?? ''))));

// Ambil angka notifikasi terpusat
$initNotif = getSitemanSidebarNotif($pdo ?? null);
?>
<aside class="desktop-sidebar">
    <div>
        <h1 class="sidebar-brand">SITEMAN - KOS</h1>
        <a href="/?page=profile" class="sidebar-profile <?= $currentPage === 'profile' ? 'active' : '' ?>">
            <div class="sidebar-avatar"><i class="fa-solid fa-user"></i></div>
            <span class="sidebar-name"><?= htmlspecialchars($nama_user); ?></span>
        </a>

        <nav class="sidebar-nav">
            <?php if ($role_user === 'pemilik'): ?>
                <a href="/?page=dashboard_pemilik" class="nav-item <?= in_array($currentPage, ['dashboard_pemilik', 'dashboard']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-table-cells-large"></i><span>Dashboard</span>
                </a>
                <a href="/?page=manajemen_kamar" class="nav-item <?= $currentPage === 'manajemen_kamar' ? 'active' : '' ?>">
                    <i class="fa-solid fa-warehouse"></i><span>Manajemen Kamar &amp; Penghuni</span>
                    <span id="badge-sidebar-kamar" class="sidebar-notif-badge badge-warning <?= $initNotif['kamar'] > 0 ? '' : 'is-hidden' ?>">
                        <?= formatBadgeCount($initNotif['kamar']); ?>
                    </span>
                </a>
                <a href="/?page=analisis_kas" class="nav-item <?= in_array($currentPage, ['analisis_kas', 'arus_kas']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-wallet"></i><span>Analisis Kas</span>
                </a>
                <a href="/?page=edit_tatib" class="nav-item <?= $currentPage === 'edit_tatib' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book-open"></i><span>Manajemen Peraturan Kos</span>
                </a>
            <?php else: ?>
                <a href="/?page=dashboard_penghuni" class="nav-item <?= in_array($currentPage, ['dashboard_penghuni', 'dashboard']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-table-cells-large"></i><span>Dashboard</span>
                </a>
                <a href="/?page=tagihan_riwayat" class="nav-item <?= $currentPage === 'tagihan_riwayat' ? 'active' : '' ?>">
                    <i class="fa-solid fa-receipt"></i><span>Tagihan &amp; Riwayat</span>
                    <span id="badge-sidebar-tagihan" class="sidebar-notif-badge <?= $initNotif['tagihan'] > 0 ? '' : 'is-hidden' ?>">
                        <?= formatBadgeCount($initNotif['tagihan']); ?>
                    </span>
                </a>
                <a href="/?page=peraturan_kost" class="nav-item <?= $currentPage === 'peraturan_kost' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book-open"></i><span>Peraturan Kos</span>
                </a>
            <?php endif; ?>

            <a href="/?page=chat_penghuni" class="nav-item <?= $currentPage === 'chat_penghuni' ? 'active' : '' ?>">
                <i class="fa-solid fa-comments"></i><span>Chat Penghuni</span>
            </a>

            <?php if ($role_user === 'pemilik' || $is_pj): ?>
                <a href="/?page=kelola_iuran" class="nav-item <?= in_array($currentPage, ['kelola_iuran', 'iuran']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i><span>Kelola Iuran</span>
                    <span id="badge-sidebar-iuran" class="sidebar-notif-badge badge-warning <?= $initNotif['iuran'] > 0 ? '' : 'is-hidden' ?>">
                        <?= formatBadgeCount($initNotif['iuran']); ?>
                    </span>
                </a>
                <a href="/?page=verifikasi_bayar" class="nav-item <?= in_array($currentPage, ['verifikasi_bayar', 'verifikasi_pembayaran']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-list-check"></i><span>Verifikasi Bayar</span>
                    <span id="badge-sidebar-verifikasi" class="sidebar-notif-badge <?= $initNotif['verifikasi'] > 0 ? '' : 'is-hidden' ?>">
                        <?= formatBadgeCount($initNotif['verifikasi']); ?>
                    </span>
                </a>
            <?php else: ?>
                <a href="/?page=monitoring_iuran" class="nav-item <?= in_array($currentPage, ['monitoring_iuran', 'iuran']) ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i><span>Monitoring Iuran</span>
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="sidebar-bottom-group">
        <button type="button" class="btn-logout-sidebar" onclick="bukaModalLogoutSidebar()">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Keluar</span>
        </button>

        <div class="sidebar-footer">
            <p class="sidebar-footer-title">SITEMAN - KOS &copy; 2g2b - 2026</p>
            <p class="sidebar-footer-desc">Sistem Manajemen Operasional Kos</p>
        </div>
    </div>
</aside>

<div id="modalKonfirmasiLogoutSidebar" class="modal-overlay" onclick="tutupModalLogoutSidebar()">
    <div class="modal-card-konfirmasi-kamar" onclick="event.stopPropagation()">
        <div class="konfirmasi-icon-wrapper" id="logoutIconBoxSidebar">
            <i class="fa-solid fa-right-from-bracket"></i>
        </div>
        <h3 class="konfirmasi-title">Yakin ingin keluar?</h3>
        <p class="konfirmasi-desc">
            Sesi akun Anda akan diakhiri dan Anda harus masuk kembali untuk mengakses sistem.
        </p>
        <div class="konfirmasi-actions">
            <button type="button" class="btn-konfirmasi-batal" id="btnBatalLogoutSidebar" onclick="tutupModalLogoutSidebar()">Batal</button>
            <a href="/?page=logout" class="btn-konfirmasi-hapus btn-ya-keluar" onclick="prosesLogoutAnimasi(event, this, 'logoutIconBoxSidebar', 'btnBatalLogoutSidebar')">
                <span>Ya, Keluar</span>
            </a>
        </div>
    </div>
</div>

<script>
    function bukaModalLogoutSidebar() {
        const modal = document.getElementById('modalKonfirmasiLogoutSidebar');
        if (modal) modal.classList.add('active');
    }

    function tutupModalLogoutSidebar() {
        const modal = document.getElementById('modalKonfirmasiLogoutSidebar');
        if (modal) modal.classList.remove('active');
    }

    if (typeof prosesLogoutAnimasi !== 'function') {
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
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') tutupModalLogoutSidebar();
    });

    // Real-Time Notification Polling (Sinkron Desktop Sidebar & Mobile Grid)
    window.refreshSidebarNotif = async function () {
        function applyBadge(elIds, count) {
            const num = parseInt(count, 10) || 0;
            const nextText = num > 99 ? '99+' : String(num);

            elIds.forEach(function (elId) {
                const el = document.getElementById(elId);
                if (!el) return;
                const prevText = el.textContent.trim();

                if (num > 0) {
                    el.classList.remove('is-hidden');
                    if (prevText !== nextText) {
                        el.textContent = nextText;
                        el.classList.remove('badge-pop');
                        void el.offsetWidth;
                        el.classList.add('badge-pop');
                    }
                } else {
                    el.textContent = '0';
                    el.classList.add('is-hidden');
                }
            });
        }

        try {
            const res = await fetch('/api/notif_sidebar', {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!res.ok) return;
            const data = await res.json();
            applyBadge(['badge-sidebar-verifikasi', 'badge-mobile-verifikasi'], data.verifikasi);
            applyBadge(['badge-sidebar-tagihan', 'badge-mobile-tagihan'], data.tagihan);
            applyBadge(['badge-sidebar-kamar', 'badge-mobile-kamar'], data.kamar);
            applyBadge(['badge-sidebar-iuran', 'badge-mobile-iuran'], data.iuran);
        } catch (err) {
            // Abaikan error jaringan sementara
        }
    };

    // Sinkronisasi Active Menu & Indikator Loading Navigasi
    document.addEventListener('DOMContentLoaded', function () {
        const loader = document.getElementById('topNavLoader');
        const navLinks = document.querySelectorAll('.desktop-sidebar a[href^="/?page="]');
        const allPageLinks = document.querySelectorAll('a[href^="/?page="]');

        const aliasMap = {
            'kelola_iuran': 'iuran',
            'monitoring_iuran': 'iuran',
            'arus_kas': 'analisis_kas',
            'verifikasi_pembayaran': 'verifikasi_bayar'
        };

        function setSidebarActiveByPage(rawPage) {
            const normalizedTarget = aliasMap[rawPage] || rawPage || 'dashboard';
            navLinks.forEach(function (item) {
                const itemUrl = new URL(item.getAttribute('href'), window.location.origin);
                const itemPage = itemUrl.searchParams.get('page') || '';
                const normalizedItem = aliasMap[itemPage] || itemPage;

                const isMatch = (normalizedItem === normalizedTarget) ||
                    (normalizedTarget === 'dashboard' && (normalizedItem === 'dashboard_pemilik' || normalizedItem === 'dashboard_penghuni'));

                item.classList.toggle('active', isMatch);
            });
        }

        const currentParams = new URLSearchParams(window.location.search);
        setSidebarActiveByPage(currentParams.get('page') || 'dashboard');

        <?php if ($ketuaWajibIsiRek): ?>
        const desktopContainer = document.querySelector('.desktop-content');
        if (desktopContainer && !document.getElementById('bannerWajibRekKetua')) {
            const banner = document.createElement('div');
            banner.id = 'bannerWajibRekKetua';
            banner.className = 'iuran-notif-banner notif-warning banner-wajib-rek';
            banner.innerHTML = `
                <div class="iuran-notif-icon">
                    <i class="fa-solid fa-building-columns"></i>
                </div>
                <div class="iuran-notif-body">
                    <strong>Wajib Lengkapi Rekening Kas Iuran</strong>
                    <p>Anda menjabat sebagai Ketua Kos. Silakan lengkapi <b>Nama Bank</b> dan <b>Nomor Rekening</b> di halaman <a href="/?page=profile&modal=ubah_profil" class="link-wajib-rek">Profil Akun</a> agar penghuni dapat melakukan transfer iuran.</p>
                </div>
            `;
            desktopContainer.insertBefore(banner, desktopContainer.firstChild);
        }
        <?php endif; ?>

        allPageLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                if (e.ctrlKey || e.metaKey || this.target === '_blank') return;

                if (loader) {
                    loader.style.opacity = '1';
                    loader.style.width = '75%';
                }

                const targetUrl = new URL(this.getAttribute('href'), window.location.origin);
                const targetPage = targetUrl.searchParams.get('page');
                if (targetPage) {
                    setSidebarActiveByPage(targetPage);
                }
            });
        });

        window.addEventListener('pageshow', function () {
            const params = new URLSearchParams(window.location.search);
            setSidebarActiveByPage(params.get('page') || 'dashboard');

            if (loader) {
                loader.style.width = '100%';
                setTimeout(function () {
                    loader.style.opacity = '0';
                    loader.style.width = '0%';
                }, 200);
            }
            window.refreshSidebarNotif();
        });

        setInterval(window.refreshSidebarNotif, 2000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                window.refreshSidebarNotif();
            }
        });
    });
</script>