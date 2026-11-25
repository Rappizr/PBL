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
 * Helper internal eksekusi request ke Supabase Storage
 * @param string             $method
 * @param string             $endpoint
 * @param array<int, string> $extraHeaders
 * @param string|null        $body
 * @param int                $timeout
 * @return array{status: int, body: string}
 */
function executeSupabaseRequest(string $method, string $endpoint, array $extraHeaders = [], ?string $body = null, int $timeout = 15): array {
    $headers = [
        'apikey: ' . SUPABASE_SERVICE_KEY,
    ];
    // Header Authorization: Bearer hanya dikirim untuk key berformat JWT (eyJ...)
    if (str_starts_with(SUPABASE_SERVICE_KEY, 'eyJ')) {
        $headers[] = 'Authorization: Bearer ' . SUPABASE_SERVICE_KEY;
    }
    $headers = array_merge($headers, $extraHeaders);

    // Deteksi PHP built-in server lokal (localhost, 127.0.0.1, atau 0.0.0.0)
    $isLocalDev = PHP_SAPI === 'cli-server'
        || in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1', '0.0.0.0'], true);

    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => !$isLocalDev,
            CURLOPT_SSL_VERIFYHOST => $isLocalDev ? 0 : 2,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("[SupabaseStorage] {$method} {$endpoint} -> HTTP {$httpCode} | cURL: {$curlErr} | Resp: " . (string)$response);
        }

        return [
            'status' => $httpCode,
            'body'   => is_string($response) ? $response : '',
        ];
    }

    // Fallback stream jika cURL tidak aktif
    $httpOpts = [
        'method'        => $method,
        'header'        => implode("\r\n", $headers) . "\r\n",
        'timeout'       => $timeout,
        'ignore_errors' => true,
    ];
    if ($body !== null) {
        $httpOpts['content'] = $body;
    }

    $context = stream_context_create([
        'http' => $httpOpts,
        'ssl'  => [
            'verify_peer'      => !$isLocalDev,
            'verify_peer_name' => !$isLocalDev,
        ],
    ]);
    $response = @file_get_contents($endpoint, false, $context);
    $httpCode = 0;

    if (!empty($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $hdr) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $hdr, $m)) {
                $httpCode = (int)$m[1];
                break;
            }
        }
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("[SupabaseStorage Stream] {$method} {$endpoint} -> HTTP {$httpCode} | Resp: " . (string)$response);
    }

    return [
        'status' => $httpCode,
        'body'   => is_string($response) ? $response : '',
    ];
}

/**
 * 1. Upload file langsung ke Private Bucket Supabase
 * @param array<string, mixed> $file   Element dari $_FILES['nama_input']
 * @param string               $bucket Nama bucket ('bukti_tf' atau 'ktp')
 * @return string|false                Mengembalikan nama file untuk disimpan ke DB
 */
function uploadKeSupabase(array $file, string $bucket): string|false {
    if (SUPABASE_URL === '' || SUPABASE_SERVICE_KEY === '') {
        error_log('[SupabaseStorage] SUPABASE_URL atau SUPABASE_SERVICE_KEY kosong.');
        return false;
    }

    if (!isset($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        error_log('[SupabaseStorage] File upload error code: ' . ($file['error'] ?? -1));
        return false;
    }

    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $mimeType = false;
    if (function_exists('mime_content_type')) {
        $mimeType = mime_content_type((string)$file['tmp_name']);
    } elseif (function_exists('getimagesize')) {
        $imgInfo  = @getimagesize((string)$file['tmp_name']);
        $mimeType = $imgInfo['mime'] ?? false;
    } else {
        $mimeType = $file['type'] ?? false;
    }

    if ($mimeType === false || !isset($allowedMime[$mimeType])) {
        error_log('[SupabaseStorage] MIME tidak didukung: ' . (string)$mimeType);
        return false;
    }

    $ext      = $allowedMime[$mimeType];
    $namaFile = $bucket . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

    $endpoint = SUPABASE_URL . '/storage/v1/object/' . rawurlencode($bucket) . '/' . rawurlencode($namaFile);
    $fileData = file_get_contents((string)$file['tmp_name']);
    if ($fileData === false) {
        return false;
    }

    $res = executeSupabaseRequest('POST', $endpoint, [
        'Content-Type: ' . $mimeType,
        'x-upsert: true',
    ], $fileData, 20);

    return ($res['status'] >= 200 && $res['status'] < 300) ? $namaFile : false;
}

/**
 * 2. Ambil 1 Signed URL (untuk preview 1 KTP / 1 bukti)
 */
function getSignedUrlSupabase(string $bucket, ?string $namaFile, int $expiresIn = 300): string {
    if (empty($namaFile) || SUPABASE_URL === '' || SUPABASE_SERVICE_KEY === '') {
        return '';
    }

    $endpoint = SUPABASE_URL . '/storage/v1/object/sign/' . rawurlencode($bucket) . '/' . rawurlencode($namaFile);
    $res      = executeSupabaseRequest('POST', $endpoint, [
        'Content-Type: application/json',
    ], json_encode(['expiresIn' => $expiresIn]) ?: '{}', 15);

    if ($res['status'] >= 200 && $res['status'] < 300 && $res['body'] !== '') {
        $data = json_decode($res['body'], true);
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
    $payload  = json_encode([
        'expiresIn' => $expiresIn,
        'paths'     => $paths,
    ]) ?: '{}';

    $res = executeSupabaseRequest('POST', $endpoint, [
        'Content-Type: application/json',
    ], $payload, 15);

    $resultMap = [];
    if ($res['status'] >= 200 && $res['status'] < 300 && $res['body'] !== '') {
        $items = json_decode($res['body'], true);
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
    $res      = executeSupabaseRequest('DELETE', $endpoint, [], null, 15);

    return ($res['status'] >= 200 && $res['status'] < 300);
}