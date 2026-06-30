<?php
// src/rbac.php
require_once __DIR__ . '/../../vendor/autoload.php'; // if present

function get_pdo() {
    $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
    $dbName = getenv('DB_NAME') ?: 'trading';
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPass = getenv('DB_PASS') ?: '';
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    return $pdo;
}

function assign_role_to_user(int $user_id, string $role_name) {
    $pdo = get_pdo();
    $st = $pdo->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
    $st->execute([$role_name]);
    $role = $st->fetch(PDO::FETCH_ASSOC);
    if (!$role) return false;
    $stmt = $pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)');
    return $stmt->execute([$user_id, $role['id']]);
}

function user_has_permission(int $user_id, string $permission_name): bool {
    $pdo = get_pdo();
    $sql = "SELECT 1 FROM user_roles ur
      JOIN role_permissions rp ON ur.role_id = rp.role_id
      JOIN permissions p ON rp.permission_id = p.id
      WHERE ur.user_id = ? AND p.name = ? LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$user_id, $permission_name]);
    return (bool)$st->fetchColumn();
}

function require_permission(string $perm) {
    if (empty($_SESSION['user_id'])) {
        header('Location: /auth/login.php'); exit;
    }
    if (!user_has_permission((int)$_SESSION['user_id'], $perm)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function get_user_roles(int $user_id): array {
    $pdo = get_pdo();
    $st = $pdo->prepare('SELECT r.name FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = ?');
    $st->execute([$user_id]);
    return $st->fetchAll(PDO::FETCH_COLUMN);
}
