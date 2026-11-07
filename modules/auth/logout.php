<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Kosongkan semua isi array session
$_SESSION = array();

// 2. Hapus cookie PHPSESSID dari browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Hancurkan session server
session_destroy();

// 4. Redirect bersih ke halaman login
header("Location: /?page=login");
exit;