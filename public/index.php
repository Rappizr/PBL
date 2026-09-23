<?php

session_start();
$page = $_GET['page'] ?? 'login';

switch ($page) {
    case 'login':
        require_once __DIR__ . '/../modules/auth/login.php';
        break;
        case 'dashboard-penghuni':
        require_once __DIR__ . '/../modules/auth/penghuni/dashboard.php';
        break;
}