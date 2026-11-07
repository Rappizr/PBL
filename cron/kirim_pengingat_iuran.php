<?php
/**
 * CRON SCRIPT: PENGINGAT OTOMATIS IURAN WA VIA FONNTE
 * Otomatis berjalan hanya pada Hari H (tanggal_mulai iuran)
 */

require_once __DIR__ . '/../config/database.php';

try {
    $today = date('Y-m-d');

    // 1. CEK APAKAH HARI INI ADALAH TANGGAL MULAI IURAN DARI KATEGORI_IURAN
    $stmtCek = $pdo->prepare("
        SELECT id, nominal, tanggal_mulai, tanggal_selesai 
        FROM kategori_iuran 
        WHERE tanggal_mulai = :today 
        LIMIT 1
    ");
    $stmtCek->execute([':today' => $today]);
    $iuranBerlaku = $stmtCek->fetch(PDO::FETCH_ASSOC);

    // Jika hari ini BUKAN tanggal mulai iuran, hentikan eksekusi
    if (!$iuranBerlaku) {
        exit("Hari ini ($today) bukan tanggal mulai pembayaran iuran. Tidak ada WA yang dikirim.\n");
    }

    // 2. CARI DATA KETUA KOS (PJ) SAAT INI
    $stmtPJ = $pdo->query("
        SELECT id, nama, no_wa 
        FROM penghuni 
        WHERE is_ketua_kos = TRUE 
          AND LOWER(COALESCE(status_akun, 'aktif')) = 'aktif'
        LIMIT 1
    ");
    $ketuaKos = $stmtPJ->fetch(PDO::FETCH_ASSOC);

    if (!$ketuaKos || empty($ketuaKos['no_wa'])) {
        exit("Gagal: Ketua Kos (PJ) belum ditunjuk atau tidak memiliki nomor WA.\n");
    }

    // 3. AMBIL SEMUA PENGHUNI AKTIF
    $stmtPenghuni = $pdo->query("
        SELECT pen.id, pen.nama, pen.no_wa, COALESCE(k.no_kamar, '-') AS kamar
        FROM penghuni pen
        LEFT JOIN penghuni_kamar pk ON pen.id = pk.penghuni_id
        LEFT JOIN kamar k ON pk.kamar_id = k.id
        WHERE LOWER(COALESCE(pen.status_akun, 'aktif')) = 'aktif'
          AND pen.no_wa IS NOT NULL AND pen.no_wa != ''
    ");
    $listPenerima = $stmtPenghuni->fetchAll(PDO::FETCH_ASSOC);

    $tokenFonnte = 'Xwo9qu8sN1ZrNPKctffY';
    $nominalRp   = 'Rp. ' . number_format((float)$iuranBerlaku['nominal'], 0, ',', '.');
    $tglSelesai  = date('d/m/Y', strtotime($iuranBerlaku['tanggal_selesai']));

    // 4. LOOPING KIRIM PESAN WA
    foreach ($listPenerima as $penerima) {
        $noWaTarget = preg_replace('/\D/', '', $penerima['no_wa']);
        if (str_starts_with($noWaTarget, '0')) {
            $noWaTarget = '62' . substr($noWaTarget, 1);
        }

        $pesan  = "Halo Kak *{$penerima['nama']}* (Kamar {$penerima['kamar']}),\n\n";
        $pesan .= "Pembayaran iuran kos telah dibuka hari ini (" . date('d/m/Y') . ").\n";
        $pesan .= "Nominal: *{$nominalRp}*\n";
        $pesan .= "Batas Pembayaran: *{$tglSelesai}*\n\n";
        $pesan .= "Pesan ini dikirim otomatis atas nama Ketua Kos: *{$ketuaKos['nama']}*.\n";
        $pesan .= "Mohon segera melakukan pembayaran via aplikasi SITEMAN - KOS. Terima kasih!";

        $payload = http_build_query([
            'target'  => $noWaTarget,
            'message' => $pesan,
        ]);

        $options = [
            'http' => [
                'header'  => "Authorization: $tokenFonnte\r\n" .
                             "Content-Type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => $payload,
                'timeout' => 10
            ]
        ];

        $context = stream_context_create($options);
        @file_get_contents('https://api.fonnte.com/send', false, $context);
    }

    echo "Sukses: Pengingat iuran hari ini terkirim ke " . count($listPenerima) . " penghuni.\n";

} catch (PDOException $e) {
    echo "[CRON PJ WA ERROR] " . $e->getMessage() . "\n";
}