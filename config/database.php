<?php

// Opsional: load .env hanya kalau sedang di local
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value, " \t\"'");
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

// Ambil dari environment (berfungsi di Vercel + local)
$host     = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? null);
$port     = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '5432');
$dbname   = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? null);
$username = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? null);
$password = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? null);

// Cek apakah variabel penting sudah terisi
if (!$host || !$dbname || !$username) {
    die('Environment variables DB_HOST / DB_NAME / DB_USER belum di-set. Pastikan sudah ditambahkan di Vercel Dashboard dan sudah re-deploy.');
}

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi ke database gagal: " . $e->getMessage());
}