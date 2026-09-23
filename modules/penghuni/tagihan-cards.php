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
        
    
}