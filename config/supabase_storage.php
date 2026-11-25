<?php
// config/supabase_storage.php

if (!isset($_ENV['SUPABASE_URL']) && getenv('SUPABASE_URL') === false) {
    $envPath = dirname(__DIR__) . '/.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val, " \t\n\r\0\x0B\"'");
                $_ENV[$key] = $val;
                putenv("$key=$val");
            }
        }
    }
}

$rawSupabaseUrl = (string)($_ENV['SUPABASE_URL'] ?? getenv('SUPABASE_URL') ?: '');
if ($rawSupabaseUrl === '') {
    $dbUser = (string)($_ENV['DB_USER'] ?? getenv('DB_USER') ?: '');
    if (str_starts_with($dbUser, 'postgres.')) {
        $projectRef = substr($dbUser, strlen('postgres.'));
        $rawSupabaseUrl = 'https://' . $projectRef . '.supabase.co';
    }
}

if (!defined('SUPABASE_URL')) {
    define('SUPABASE_URL', rtrim($rawSupabaseUrl, '/'));
}
if (!defined('SUPABASE_SERVICE_KEY')) {
    define('SUPABASE_SERVICE_KEY', (string)($_ENV['SUPABASE_SERVICE_KEY'] ?? getenv('SUPABASE_SERVICE_KEY') ?: ''));
}

/**
 * Helper internal header autentikasi Supabase Storage
 * @return array<int, string>
 */
function getSupabaseStorageHeaders(array $extraHeaders = []): array {
    return array_merge([
        'apikey: ' . SUPABASE_SERVICE_KEY,
        'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
    ], $extraHeaders);
}

/**
 * 1. Upload file langsung ke Private Bucket Supabase
 * @param array<string, mixed> $file   Element dari $_FILES['nama_input']
 * @param string               $bucket Nama bucket ('bukti_tf' atau 'ktp')
 * @return string|false                Mengembalikan nama file untuk disimpan ke DB
 */
function uploadKeSupabase(array $file, string $bucket): string|false {
    if (SUPABASE_URL === '' || SUPABASE_SERVICE_KEY === '') {
        return false;
    }

    if (!isset($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    $mimeType = mime_content_type((string)$file['tmp_name']);

    if ($mimeType === false || !isset($allowedMime[$mimeType])) {
        return false;
    }

    $ext      = $allowedMime[$mimeType];
    $namaFile = $bucket . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

    $endpoint = SUPABASE_URL . '/storage/v1/object/' . rawurlencode($bucket) . '/' . rawurlencode($namaFile);
    $fileData = file_get_contents((string)$file['tmp_name']);
    if ($fileData === false) {
        return false;
    }

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $fileData,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => getSupabaseStorageHeaders([
            'Content-Type: ' . $mimeType,
            'x-upsert: true',
        ]),
    ]);

    curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

    return ($httpCode >= 200 && $httpCode < 300) ? $namaFile : false;
}

/**
 * 2. Ambil 1 Signed URL (untuk preview 1 KTP / 1 bukti)
 */
function getSignedUrlSupabase(string $bucket, ?string $namaFile, int $expiresIn = 300): string {
    if (empty($namaFile) || SUPABASE_URL === '' || SUPABASE_SERVICE_KEY === '') {
        return '';
    }

    $endpoint = SUPABASE_URL . '/storage/v1/object/sign/' . rawurlencode($bucket) . '/' . rawurlencode($namaFile);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['expiresIn' => $expiresIn]),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => getSupabaseStorageHeaders([
            'Content-Type: application/json',
        ]),
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode >= 200 && $httpCode < 300 && is_string($response)) {
        $data = json_decode($response, true);
        if (!empty($data['signedURL'])) {
            return SUPABASE_URL . '/storage/v1' . $data['signedURL'];
        }
    }

    return '';
}

/**
 * 3. Ambil BANYAK Signed URL sekaligus dalam 1 request (cepat untuk list verifikasi/kamar)
 * @param string              $bucket    Nama bucket ('bukti_tf' atau 'ktp')
 * @param array<int, ?string> $listFiles Array berisi nama-nama file dari DB
 * @param int                 $expiresIn Masa berlaku link dalam detik
 * @return array<string, string>         Map ['nama_file.jpg' => 'https://...signedUrl...']
 */
function getBatchSignedUrlsSupabase(string $bucket, array $listFiles, int $expiresIn = 300): array {
    $paths = array_values(array_unique(array_filter($listFiles)));
    if (empty($paths) || SUPABASE_URL === '' || SUPABASE_SERVICE_KEY === '') {
        return [];
    }

    $endpoint = SUPABASE_URL . '/storage/v1/object/sign/' . rawurlencode($bucket);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'expiresIn' => $expiresIn,
            'paths'     => $paths,
        ]),
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => getSupabaseStorageHeaders([
            'Content-Type: application/json',
        ]),
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $resultMap = [];
    if ($httpCode >= 200 && $httpCode < 300 && is_string($response)) {
        $items = json_decode($response, true);
        if (is_array($items)) {
            foreach ($items as $item) {
                if (!empty($item['path']) && !empty($item['signedURL'])) {
                    $resultMap[$item['path']] = SUPABASE_URL . '/storage/v1' . $item['signedURL'];
                }
            }
        }
    }

    return $resultMap;
}

/**
 * 4. Hapus file dari Supabase Storage (saat ganti KTP / tolak / hapus penghuni)
 */
function hapusDariSupabase(string $bucket, ?string $namaFile): bool {
    if (empty($namaFile) || SUPABASE_URL === '' || SUPABASE_SERVICE_KEY === '') {
        return false;
    }

    $endpoint = SUPABASE_URL . '/storage/v1/object/' . rawurlencode($bucket) . '/' . rawurlencode($namaFile);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'DELETE',
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => getSupabaseStorageHeaders(),
    ]);

    curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

    return ($httpCode >= 200 && $httpCode < 300);
}