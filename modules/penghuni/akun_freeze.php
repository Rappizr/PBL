<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../shared/handler/sewa_reminder_helper.php';

$currentUser = $_SESSION['user'] ?? [];
$penghuniId  = (int)($currentUser['id'] ?? 0);

if ($penghuniId <= 0) {
    header('Location: /?page=login');
    exit;
}

$infoFreeze = evaluasiStatusSewaPenghuni($pdo, $penghuniId) ?? [
    'nama_penghuni'        => $currentUser['nama'] ?? 'Penghuni',
    'no_kamar'             => $currentUser['no_kamar'] ?? '-',
    'jumlah_bulan_nunggak' => 1,
    'total_tunggakan'      => 0,
    'batas_freeze_fmt'     => '-',
];

$defaultJanji = date('Y-m-d', strtotime('+3 days'));
$waUrlAwal    = buatLinkWaPermohonanUnfreeze($pdo, $infoFreeze, $defaultJanji);

// Ambil nomor WA pemilik untuk pembaruan link dinamis di JS saat tanggal diubah
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

    <div class="login-card" style="max-width: 420px; border-top: 6px solid #dc2626;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background-color: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 14px;">
            <i class="fa-solid fa-lock"></i>
        </div>

        <h1 class="brand-title" style="color: #dc2626;">AKUN DIBEKUKAN (FREEZE)</h1>
        <p class="brand-subtitle" style="color: #475569; font-size: 13.5px; margin-bottom: 16px;">
            Halo <strong><?= htmlspecialchars($infoFreeze['nama_penghuni']); ?></strong> (Kamar <strong><?= htmlspecialchars($infoFreeze['no_kamar']); ?></strong>), akses akun Anda sementara dibatasi karena tagihan sewa telah melewati masa tenggang.
        </p>

        <!-- Ringkasan Tunggakan -->
        <div style="background-color: #fef2f2; border: 1.5px solid #fecaca; border-radius: 14px; padding: 14px 16px; text-align: left; margin-bottom: 18px;">
            <div style="display: flex; justify-content: space-between; font-size: 13px; color: #7f1d1d; margin-bottom: 6px;">
                <span>Durasi Tunggakan</span>
                <strong><?= (int)$infoFreeze['jumlah_bulan_nunggak']; ?> Bulan</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 14px; color: #991b1b; padding-top: 6px; border-top: 1px dashed #fca5a5;">
                <span>Total Tagihan Sewa</span>
                <strong>Rp <?= number_format((float)$infoFreeze['total_tunggakan'], 0, ',', '.'); ?></strong>
            </div>
        </div>

        <!-- Input Tanggal Rencana Bayar untuk Otomatis Terisi di Pesan WA -->
        <div style="text-align: left; margin-bottom: 16px;">
            <label for="inputTglJanjiFreeze" style="display: block; font-size: 12.5px; font-weight: 800; color: #1a4d55; margin-bottom: 6px;">
                Pilih Tanggal Rencana Pelunasan:
            </label>
            <input type="date" id="inputTglJanjiFreeze"
                   min="<?= date('Y-m-d'); ?>"
                   value="<?= $defaultJanji; ?>"
                   onchange="perbaruiLinkWaFreeze()"
                   style="width: 100%; padding: 10px 14px; border-radius: 10px; border: 1.5px solid #4ca5b0; font-size: 13.5px; font-weight: 700; color: #1a4d55; box-sizing: border-box;">
            <small style="display: block; font-size: 11px; color: #64748b; margin-top: 5px;">
                Tanggal ini akan otomatis dicantumkan ke dalam pesan WhatsApp kepada Pemilik Kos.
            </small>
        </div>

        <a href="<?= htmlspecialchars($waUrlAwal); ?>" target="_blank" id="btnWaUnfreeze"
           class="btn-submit btn-as-link"
           style="background-color: #25D366; display: inline-flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 12px;">
            <i class="fa-brands fa-whatsapp" style="font-size: 18px;"></i>
            <span>Hubungi Pemilik Kos via WA</span>
        </a>

        <a href="/?page=logout" class="link-kembali" style="color: #64748b;">
            <i class="fa-solid fa-right-from-bracket"></i> Keluar dari Akun
        </a>
    </div>

    <div class="auth-spacer"></div>

    <?php require_once __DIR__ . '/../components/footer.php'; ?>

    <script>
        function perbaruiLinkWaFreeze() {
            const noWaPemilik = <?= json_encode($noWaPemilik); ?>;
            const btnWa       = document.getElementById('btnWaUnfreeze');
            const inputTgl    = document.getElementById('inputTglJanjiFreeze');
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
            const jmlBulan   = <?= (int)$infoFreeze['jumlah_bulan_nunggak']; ?>;
            const nominalFmt = <?= json_encode('Rp ' . number_format((float)$infoFreeze['total_tunggakan'], 0, ',', '.')); ?>;

            const pesan = `Halo Bapak/Ibu Pemilik Kos,\n\n`
                        + `Saya *${nama}* penghuni *Kamar ${kamar}*. Akun SITEMAN - KOS saya saat ini ter-freeze karena menunggak sewa selama *${jmlBulan} bulan* dengan total *${nominalFmt}*.\n\n`
                        + `Saya memohon kebijaksanaannya untuk membuka akses akun saya sementara, dan saya berjanji akan melunasi tunggakan tersebut pada tanggal *${tglFmt}*.\n\n`
                        + `Terima kasih banyak atas pengertiannya. 🙏`;

            btnWa.href = `https://wa.me/${noWaPemilik}?text=${encodeURIComponent(pesan)}`;
        }
    </script>
</body>

</html>