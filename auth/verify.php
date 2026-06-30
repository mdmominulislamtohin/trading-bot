<?php
// auth/verify.php
session_start();
require_once __DIR__ . '/../src/rbac.php';
$token = $_GET['token'] ?? '';
if (!$token) { echo 'Invalid token'; exit; }
$pdo = get_pdo();
$st = $pdo->prepare('SELECT id,verification_expires_at FROM users WHERE verification_token = ? LIMIT 1');
$st->execute([$token]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo 'Token invalid'; exit; }
if (strtotime($row['verification_expires_at']) < time()) { echo 'Token expired'; exit; }
$upd = $pdo->prepare('UPDATE users SET email_verified = 1, verification_token = NULL, verification_expires_at = NULL WHERE id = ?');
$upd->execute([$row['id']]);
echo 'Email verified. You can now <a href="/auth/login.php">login</a>.';
