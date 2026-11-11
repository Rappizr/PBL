<?php
// Set default timezone PHP ke WIB
date_default_timezone_set('Asia/Jakarta');

// Opsional: load .env hanya kalau sedang di local
require_once __DIR__ . '/../misc/loadenv.php';

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
    // Sinkronisasi timezone koneksi PostgreSQL
    $pdo->exec("SET TIME ZONE 'Asia/Jakarta';");
} catch (PDOException $e) {
    die("Koneksi ke database gagal: " . $e->getMessage());
}