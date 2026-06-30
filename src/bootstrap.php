<?php
// src/bootstrap.php
// Central bootstrap for small app: environment, error handling, and common includes.
if (!defined('APP_BOOTSTRAPPED')) {
    define('APP_BOOTSTRAPPED', true);

    // Environment
    $env = getenv('APP_ENV') ?: 'development';

    // Error handling
    if ($env !== 'production') {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    } else {
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        ini_set('error_log', __DIR__ . '/../logs/php_error.log');
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    }

    date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'UTC');

    // Start session with secure defaults (can be adjusted per-host)
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }

    // Useful helpers
    require_once __DIR__ . '/crypto.php';
    require_once __DIR__ . '/rbac.php';
}
