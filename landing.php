<?php
// landing.php - simple landing page that reads admin settings
require_once __DIR__ . '/src/crypto.php';
function get_pdo() { $dbHost = getenv('DB_HOST') ?: '127.0.0.1'; $dbName = getenv('DB_NAME') ?: 'trading'; $dbUser = getenv('DB_USER') ?: 'root'; $dbPass = getenv('DB_PASS') ?: ''; return new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); }
$pdo = get_pdo();
$st = $pdo->prepare('SELECT `key`, encrypted_value FROM app_settings'); $st->execute(); $rows = $st->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($rows as $r) { try { $settings[$r['key']] = decrypt_secret($r['encrypted_value']); } catch(Exception $e) { $settings[$r['key']] = null; } }
$site_name = $settings['site_name'] ?? 'Trading Bot';
$contact = $settings['contact_email'] ?? 'support@example.com';
$bullets = isset($settings['site_bullets']) ? explode("\n", trim($settings['site_bullets'])) : ['Secure wallets on BSC & Arbitrum','Auto wallet creation','Admin RBAC & 2FA ready'];
?>
<!doctype html>
<html><head><meta charset="utf-8"><title><?php echo htmlspecialchars($site_name) ?></title></head><body>
<h1><?php echo htmlspecialchars($site_name) ?></h1>
<ul>
<?php foreach($bullets as $b) if(trim($b)) echo '<li>'.htmlspecialchars($b).'</li>'; ?>
</ul>
<p>Contact: <?php echo htmlspecialchars($contact) ?></p>
<p><a href="/auth/register.php">Sign up</a> | <a href="/auth/login.php">Login</a></p>
</body></html>
