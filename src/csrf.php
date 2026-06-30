<?php
// src/csrf.php
// Simple session-based CSRF helper
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function csrf_token(): string {
    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token_time'] = time();
    }
    return $_SESSION['_csrf_token'];
}

function csrf_token_field(): string {
    $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="_csrf" value="'.$t.'">';
}

function csrf_verify_request(): bool {
    // Only verify for POST/PUT/DELETE
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'POST' && $method !== 'PUT' && $method !== 'DELETE') return true;
    $posted = $_POST['_csrf'] ?? null;
    if (!$posted) return false;
    $valid = hash_equals($_SESSION['_csrf_token'] ?? '', $posted);
    return $valid;
}

function csrf_require_valid_or_die() {
    if (!csrf_verify_request()) {
        error_log('CSRF validation failed for ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
        http_response_code(400);
        echo 'Invalid CSRF token.';
        exit;
    }
}
