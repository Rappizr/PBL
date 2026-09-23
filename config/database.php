<?php

$envFile = __DIR__ . '/../.env';

if (!file_exists($envFile)) {
    die('.env file tidak ditemukan');
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    $line = trim($line);

    if ($line === '' || strpos($line, '#')) {
        continue;
    }

    [$key, $value] = array_pad(explode('=', $line, 2), 2, '');

    $key = trim($key);
    $value = trim($value);

    putenv("$key=$value");
}

$host     = getenv('DB_HOST');
$port     = getenv('DB_PORT') ?: '5432';
$dbname   = getenv('DB_NAME');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');

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