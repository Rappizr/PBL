<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $pdo;
if (!isset($pdo) || !($pdo instanceof PDO)) {
    require_once __DIR__ . '/database.php';
}