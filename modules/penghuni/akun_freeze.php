<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../shared/handler/sewa_reminder_helper.php';
require_once __DIR__ . '/../shared/handler/iuran_reminder_helper.php';

$currentUser = $_SESSION['user'] ?? [];
$penghuniId  = (int)($currentUser['id'] ?? 0);

if ($penghuniId <= 0) {
    header('Location: /?page=login');
    exit;
}

$stmtReason = $pdo->prepare("SELECT LOWER(TRIM(COALESCE(freeze_reason, ''))) FROM penghuni WHERE id = :pid");
$stmtReason->execute([':pid' => $penghuniId]);
$dbFreezeReason = (string)$stmtReason->fetchColumn();

$evalSewa  = evaluasiStatusSewaPenghuni($pdo, $penghuniId);
$evalIuran = evaluasiStatusIuranPenghuni($pdo, $penghuniId);

if ($dbFreezeReason === 'iuran' && $evalIuran) {
    $infoFreeze = $evalIuran;
} elseif ($evalSewa && ($evalSewa['fase'] ?? '') === 'freeze') {
    $infoFreeze = $evalSewa;
} elseif ($evalIuran && ($evalIuran['fase'] ?? '') === 'freeze') {
    $infoFreeze = $evalIuran;
} else {
    $infoFreeze = $evalSewa ?? $evalIuran ?? [
        'jenis_tunggakan'      => ($dbFreezeReason === 'iuran') ? 'iuran' : 'sewa',
        'nama_penghuni'        => $currentUser['nama'] ?? 'Penghuni',
        'no_kamar'             => $currentUser['no_kamar'] ?? '-',
        'nama_iuran'           => 'Iuran Kas Bersama',
        'jumlah_bulan_nunggak' => 1,
        'total_tunggakan'      => 0,
        'jatuh_tempo_fmt'      => '-',
        'batas_freeze_fmt'     => '-',
        'tanggal_janji_bayar'  => '',
        'catatan_tunggakan'    => '',
    ];
}

$isFreezeIuran    = (($infoFreeze['jenis_tunggakan'] ?? 'sewa') === 'iuran');
$namaIuran        = $infoFreeze['nama_iuran'] ?? 'Iuran Kas Bersama';
$tglJanjiDiajukan = $infoFreeze['tanggal_janji_bayar'] ?? '';
$catatanTunggakan = trim((string)($infoFreeze['catatan_tunggakan'] ?? ''));
$isJanjiDitolak   = str_starts_with($catatanTunggakan, '[Ditolak]');
$alasanDitolakTxt = $isJanjiDitolak ? trim(substr($catatanTunggakan, strlen('[Ditolak]'))) : '';

$defaultJanji = ($tglJanjiDiajukan !== '' && !$isJanjiDitolak)
    ? $tglJanjiDiajukan
    : date('Y-m-d', strtotime('+3 days'));

$noWaPemilik = '';
try {
    $rawWa = (string)$pdo->query("SELECT no_wa FROM pemilik ORDER BY id ASC LIMIT 1")->fetchColumn();
    $noWaPemilik = preg_replace('/\D/', '', $rawWa);
    if (str_starts_with($noWaPemilik, '0')) {
        $noWaPemilik = '62' . substr($noWaPemilik, 1);
    }
} catch (Throwable $e) {
    $noWaPemilik = '';
}

$waUrlAwal = $isFreezeIuran
    ? buatLinkWaPermohonanUnfreezeIuran($pdo, $infoFreeze, $defaultJanji)
    : buatLinkWaPermohonanUnfreeze($pdo, $infoFreeze, $defaultJanji);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SITEMAN - KOS | Akun Dibekukan (Freeze)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/css/global.css?v=<?= filemtime(__DIR__ . '/../../public/css/global.css'); ?>">
    <link rel="stylesheet" href="/css/modules.css?v=<?= filemtime(__DIR__ . '/../../public/css/modules.css'); ?>">
</head>

<body class="login-body">

    <div class="auth-spacer"></div>

    <div class="login-card freeze-card">
        <div class="freeze-icon-circle">
            <i class="fa-solid fa-lock"></i>
        </div>

        <h1 class="brand-title freeze-title">AKUN DIBEKUKAN (FREEZE)</h1>
        <p class="brand-subtitle freeze-subtitle">
            Halo <strong><?= htmlspecialchars($infoFreeze['nama_penghuni']); ?></strong> (Kamar <strong><?= htmlspecialchars($infoFreeze['no_kamar']); ?></strong>),
            <?php if ($isFreezeIuran): ?>
                akses akun Anda sementara dibatasi karena pembayaran <strong><?= htmlspecialchars($namaIuran); ?></strong> telah melewati masa tenggang +7 hari.
            <?php else: ?>
                akses akun Anda sementara dibatasi karena tagihan sewa telah melewati masa tenggang.
            <?php endif; ?>
        </p>

        <!-- Ringkasan Tunggakan -->
        <div class="freeze-summary-box">
            <?php if ($isFreezeIuran): ?>
                <div class="freeze-summary-row">
                    <span>Jenis Tagihan</span>
                    <strong><?= htmlspecialchars($namaIuran); ?></strong>
                </div>
                <div class="freeze-summary-row">
                    <span>Batas Tenggang (+7 Hari)</span>
                    <strong><?= htmlspecialchars($infoFreeze['batas_freeze_fmt'] ?? '-'); ?></strong>
                </div>
                <div class="freeze-summary-total">
                    <span>Total Tagihan Iuran</span>
                    <strong>Rp <?= number_format((float)$infoFreeze['total_tunggakan'], 0, ',', '.'); ?></strong>
                </div>
            <?php else: ?>
                <div class="freeze-summary-row">
                    <span>Durasi Tunggakan</span>
                    <strong><?= (int)$infoFreeze['jumlah_bulan_nunggak']; ?> Bulan</strong>
                </div>
                <div class="freeze-summary-total">
                    <span>Total Tagihan Sewa</span>
                    <strong>Rp <?= number_format((float)$infoFreeze['total_tunggakan'], 0, ',', '.'); ?></strong>
                </div>
            <?php endif; ?>
        </div>

        <!-- Status Pengajuan / Penolakan -->
        <div id="boxStatusPengajuan">
            <?php if ($isJanjiDitolak): ?>
                <div class="alert-error">
                    <strong><i class="fa-solid fa-circle-xmark"></i> Pengajuan Sebelumnya Ditolak</strong><br>
                    <?= htmlspecialchars($alasanDitolakTxt); ?>
                </div>
            <?php elseif ($tglJanjiDiajukan !== ''): ?>
                <div class="alert-success">
                    <i class="fa-solid fa-clock"></i> Pengajuan tanggal <strong><?= date('d/m/Y', strtotime($tglJanjiDiajukan)); ?></strong> telah terkirim dan menunggu persetujuan Pemilik Kos.
                </div>
            <?php endif; ?>
        </div>

        <!-- Input Tanggal Rencana Bayar -->
        <div class="freeze-date-group">
            <label for="inputTglJanjiFreeze" class="freeze-date-label">
                Pilih Tanggal Rencana Pelunasan:
            </label>
            <input type="date" id="inputTglJanjiFreeze"
                   min="<?= date('Y-m-d'); ?>"
                   value="<?= htmlspecialchars($defaultJanji); ?>"
                   onchange="perbaruiLinkWaFreeze()"
                   class="freeze-date-input">
            <small class="freeze-date-hint">
                Tanggal ini akan otomatis tersimpan dan dicantumkan ke dalam pesan WhatsApp kepada Pemilik Kos.
            </small>
        </div>

        <a href="<?= htmlspecialchars($waUrlAwal); ?>" target="_blank" id="btnWaUnfreeze"
           onclick="kirimPermohonanUnfreeze()"
           class="btn-submit btn-as-link freeze-btn-wa">
            <i class="fa-brands fa-whatsapp freeze-btn-wa-icon"></i>
            <span><?= $isJanjiDitolak ? 'Ajukan Ulang via WA' : 'Hubungi Pemilik Kos via WA'; ?></span>
        </a>

        <a href="/?page=logout" class="link-kembali freeze-btn-logout">
            <i class="fa-solid fa-right-from-bracket"></i> Keluar dari Akun
        </a>
    </div>

    <div class="auth-spacer"></div>

    <?php require_once __DIR__ . '/../components/footer.php'; ?>

    <script>
        function perbaruiLinkWaFreeze() {
            const noWaPemilik   = <?= json_encode($noWaPemilik); ?>;
            const isFreezeIuran = <?= json_encode($isFreezeIuran); ?>;
            const btnWa         = document.getElementById('btnWaUnfreeze');
            const inputTgl      = document.getElementById('inputTglJanjiFreeze');
            if (!noWaPemilik || !btnWa || !inputTgl) return;

            let tglFmt = '[Isi Tanggal Bayar]';
            if (inputTgl.value) {
                const parts = inputTgl.value.split('-');
                if (parts.length === 3) {
                    tglFmt = `${parts[2]}/${parts[1]}/${parts[0]}`;
                }
            }

            const nama       = <?= json_encode($infoFreeze['nama_penghuni']); ?>;
            const kamar      = <?= json_encode($infoFreeze['no_kamar']); ?>;
            const namaIuran  = <?= json_encode($namaIuran); ?>;
            const jmlBulan   = <?= (int)$infoFreeze['jumlah_bulan_nunggak']; ?>;
            const nominalFmt = <?= json_encode('Rp ' . number_format((float)$infoFreeze['total_tunggakan'], 0, ',', '.')); ?>;

            let pesan = '';
            if (isFreezeIuran) {
                pesan = `Halo Bapak/Ibu Pemilik Kos,\n\n`
                      + `Saya *${nama}* penghuni *Kamar ${kamar}*. Akun SITEMAN - KOS saya saat ini ter-freeze karena tunggakan *${namaIuran}* sebesar *${nominalFmt}*.\n\n`
                      + `Saya memohon kebijaksanaannya untuk membuka akses akun saya sementara, dan saya berjanji akan melunasi iuran tersebut pada tanggal *${tglFmt}*.\n\n`
                      + `Terima kasih banyak atas pengertiannya. 🙏`;
            } else {
                pesan = `Halo Bapak/Ibu Pemilik Kos,\n\n`
                      + `Saya *${nama}* penghuni *Kamar ${kamar}*. Akun SITEMAN - KOS saya saat ini ter-freeze karena menunggak sewa selama *${jmlBulan} bulan* dengan total *${nominalFmt}*.\n\n`
                      + `Saya memohon kebijaksanaannya untuk membuka akses akun saya sementara, dan saya berjanji akan melunasi tunggakan tersebut pada tanggal *${tglFmt}*.\n\n`
                      + `Terima kasih banyak atas pengertiannya. 🙏`;
            }

            btnWa.href = `https://wa.me/${noWaPemilik}?text=${encodeURIComponent(pesan)}`;
        }

        function kirimPermohonanUnfreeze() {
            const inputTgl      = document.getElementById('inputTglJanjiFreeze');
            const isFreezeIuran = <?= json_encode($isFreezeIuran); ?>;
            const statusBox     = document.getElementById('boxStatusPengajuan');
            if (!inputTgl || !inputTgl.value) return;

            const formData = new FormData();
            formData.append('tanggal_janji_bayar', inputTgl.value);
            formData.append('jenis_tunggakan', isFreezeIuran ? 'iuran' : 'sewa');

            if (navigator.sendBeacon) {
                navigator.sendBeacon('/api/ajukan_janji_bayar.php', formData);
            } else {
                fetch('/api/ajukan_janji_bayar.php', {
                    method: 'POST',
                    body: formData,
                    keepalive: true
                });
            }

            if (statusBox) {
                const parts = inputTgl.value.split('-');
                const tglFmt = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : inputTgl.value;
                statusBox.innerHTML = `<div class="alert-success"><i class="fa-solid fa-clock"></i> Pengajuan tanggal <strong>${tglFmt}</strong> telah terkirim dan menunggu persetujuan Pemilik Kos.</div>`;
            }
        }
    </script>
</body>

</html>