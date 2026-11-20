<?php
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../misc/loadenv.php';

global $pdo;

if (isset($pdo) && ($pdo instanceof PDO)) {
    return;
}

$appEnv = strtolower(getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'production'));
if ($appEnv === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

$host     = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? null);
$port     = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '6543');
$dbname   = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'postgres');
$username = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? null);
$password = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? null);

if (!$host || !$dbname || !$username) {
    http_response_code(500);
    die('Konfigurasi database server belum lengkap.');
}

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
            PDO::ATTR_PERSISTENT         => true,
            PDO::ATTR_TIMEOUT            => 5,
        ]
    );

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
} catch (PDOException $e) {
    error_log('[SITEMAN-KOS DB ERROR] ' . $e->getMessage());
    http_response_code(503);
    die('Layanan database sedang sibuk. Silakan muat ulang halaman beberapa saat lagi.');
}