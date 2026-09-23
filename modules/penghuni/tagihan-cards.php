<?php
// Data dummy — nanti diganti tim backend jadi query ke tabel pembayaran

$tagihan = [
    'sewa' => [
        'status' => 'belum_lunas',
        'tanggal_jatuh_tempo' => '2026-10-11',
    ],
    'iuran' => [
        'status' => 'belum_lunas',
        'tanggal_jatuh_tempo' => '2026-10-11',
    ],
];

function formatTanggal(?string $tanggal): string {
    if (!$tanggal) return '-';
     $bulan = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
              7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
        $ts = strtotime($tanggal);
    return date('D', $ts) . ', ' . date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);  
}

?>
<div class="page-content">
     <?php foreach (['sewa' => 'Pembayaran Sewa', 'iuran' => 'Pembayaran Iuran'] as $jenis => $label): ?>
        <div class="tagihan-card">
            <span class="label"><?= $label ?></span>
            <div class="row">
                <span>Status Pembayaran</span>
                <?php $t = $tagihan[$jenis]; ?>
                <?php if ($t && $t['status'] === 'lunas'): ?>
                    <span class="status-lunas">Lunas</span>
                <?php else: ?>
                    <span class="status-belum">Belum Lunas</span>
                <?php endif; ?>
            </div>
            <div class="row">
                <span>Tenggat Pembayaran</span>
                <span><?= formatTanggal($t['tanggal_jatuh_tempo'] ?? null) ?></span>
            </div>
        </div>
    <?php endforeach; ?>