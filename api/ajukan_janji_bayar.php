<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$currentUser = $_SESSION['user'] ?? [];
$penghuniId  = (int)($currentUser['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $penghuniId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Akses tidak valid']);
    exit;
}

$tanggalJanji = trim((string)($_POST['tanggal_janji_bayar'] ?? ''));
$jenis        = trim((string)($_POST['jenis_tunggakan'] ?? 'sewa'));

if ($tanggalJanji === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalJanji)) {
    echo json_encode(['status' => 'error', 'message' => 'Format tanggal tidak valid']);
    exit;
}

try {
    $filterJenis = ($jenis === 'iuran')
        ? "AND kategori_iuran_id IS NOT NULL"
        : "AND kategori_iuran_id IS NULL";

    $stmt = $pdo->prepare("
        UPDATE tagihan
        SET tanggal_janji_bayar = :tgl_janji,
            catatan_tunggakan = CASE 
                WHEN catatan_tunggakan LIKE '[Ditolak]%' THEN NULL 
                ELSE catatan_tunggakan 
            END
        WHERE penghuni_kamar_id IN (
            SELECT id FROM penghuni_kamar WHERE penghuni_id = :pid
        )
        AND LOWER(TRIM(status_tagihan)) != 'lunas'
        {$filterJenis}
    ");
    $stmt->execute([
        ':tgl_janji' => $tanggalJanji,
        ':pid'       => $penghuniId
    ]);

    echo json_encode(['status' => 'ok', 'tanggal' => $tanggalJanji]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}