<?php
// admin/wallets.php
// Very small admin UI scaffold. This file must be placed behind HTTPS and protected by admin auth.
// It expects a simple session-based admin login and TOTP secret stored encrypted per admin user.

require_once __DIR__ . '/../src/crypto.php';
require_once __DIR__ . '/../src/totp.php';

session_start();
// Simple admin auth - replace with your real auth system
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// DB
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'trading';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Handle decrypt action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['decrypt_wallet_id']) && isset($_POST['totp_code'])) {
    $wallet_id = intval($_POST['decrypt_wallet_id']);
    $totp = trim($_POST['totp_code']);
    // Load admin TOTP secret from table `users` (assume admin is user id stored in session)
    $adminId = $_SESSION['admin_id'];
    $stmt = $pdo->prepare('SELECT totp_secret_encrypted FROM users WHERE id = ?');
    $stmt->execute([$adminId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { $error = 'Admin record not found'; }
    else {
        $secretEncrypted = $row['totp_secret_encrypted'] ?? null;
        if (!$secretEncrypted) { $error = '2FA not enabled for admin'; }
        else {
            try {
                $secret = decrypt_secret($secretEncrypted);
                if (!verify_totp($secret, $totp)) { $error = 'Invalid TOTP code'; }
                else {
                    // Decrypt wallet private key and show
                    $st = $pdo->prepare('SELECT id, address, encrypted_privkey FROM wallets WHERE id = ?');
                    $st->execute([$wallet_id]);
                    $w = $st->fetch(PDO::FETCH_ASSOC);
                    if (!$w) { $error = 'Wallet not found'; }
                    else {
                        $priv = decrypt_secret($w['encrypted_privkey']);
                        // Log export
                        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                        $log = $pdo->prepare('INSERT INTO wallet_exports (wallet_id, admin_user_id, action, reason, ip) VALUES (?, ?, ?, ?, ?)');
                        $log->execute([$wallet_id, $adminId, 'decrypt', $_POST['reason'] ?? '', $ip]);
                        $decrypted_private = bin2hex($priv);
                    }
                }
            } catch (Exception $ex) { $error = 'Decryption error: ' . $ex->getMessage(); }
        }
    }
}

// List wallets
$rows = $pdo->query('SELECT w.id,w.user_id,w.chain,w.address,u.email FROM wallets w LEFT JOIN users u ON u.id = w.user_id ORDER BY w.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin — Wallets</title>
<style>table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px}</style>
</head>
<body>
<h1>Wallets</h1>
<?php if (!empty($error)) echo "<p style='color:red;'>".htmlspecialchars($error)."</p>"; ?>
<?php if (!empty($decrypted_private)) echo "<p style='color:green;'>Decrypted private key (hex): <code>".htmlspecialchars($decrypted_private)."</code></p>"; ?>
<table>
<thead><tr><th>ID</th><th>User</th><th>Chain</th><th>Address</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
  <td><?=htmlspecialchars($r['id'])?></td>
  <td><?=htmlspecialchars($r['email'].' ('.$r['user_id'].')')?></td>
  <td><?=htmlspecialchars($r['chain'])?></td>
  <td><?=htmlspecialchars($r['address'])?></td>
  <td>
    <form method="post" style="display:inline">
      <input type="hidden" name="decrypt_wallet_id" value="<?=htmlspecialchars($r['id'])?>">
      <input name="totp_code" placeholder="TOTP code" required>
      <input name="reason" placeholder="Reason (audit)" required>
      <button type="submit">Decrypt & Export</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body>
</html>
