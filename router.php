<?php
/**
 * Local dev router — use with:
 *   php -S localhost:3000 router.php
 *
 * vercel.json routes only work on Vercel.
 * This file mirrors those routes for local testing.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

// Static CSS: /css/styles.css → public/css/styles.css
if (preg_match('#^/css/(.+)$#', $uri, $m)) {
    $file = __DIR__ . '/public/css/' . $m[1];
    if (is_file($file)) {
        header('Content-Type: text/css; charset=utf-8');
        readfile($file);
        return true;
    }
    http_response_code(404);
    echo 'CSS not found';
    return true;
}

// Login POST handler
if ($uri === '/api/auth') {
    require __DIR__ . '/api/auth.php';
    return true;
}

// Pretty URLs → front controller
$map = [
    '/login'              => 'login',
    '/logout'             => 'logout',
    '/dashboard/pemilik'  => 'dashboard_pemilik',
    '/dashboard/penghuni' => 'dashboard_penghuni',
];

if (isset($map[$uri])) {
    $_GET['page'] = $map[$uri];
    require __DIR__ . '/api/index.php';
    return true;
}

// Root and ?page=... style
if ($uri === '/' || !str_starts_with($uri, '/api/')) {
    require __DIR__ . '/api/index.php';
    return true;
}

return false;
