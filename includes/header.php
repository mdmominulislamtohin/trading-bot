<?php
// includes/header.php (updated to include dashboard link for logged-in users)
require_once __DIR__ . '/../src/bootstrap.php';
function get_setting_value($key) {
    try {
        $pdo = (new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASS')));
        $st = $pdo->prepare('SELECT encrypted_value FROM app_settings WHERE `key` = ? LIMIT 1');
        $st->execute([$key]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) return null;
        return decrypt_secret($r['encrypted_value']);
    } catch (Exception $e) {
        return null;
    }
}
$siteName = get_setting_value('site_name') ?: 'Trading Bot';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($siteName) ?></title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;margin:0;padding:0;background:#0b0e11;color:#eaecef}
    header{background:#111;padding:12px 18px;border-bottom:1px solid #222}
    .container{max-width:1100px;margin:0 auto;padding:12px}
    a{color:#ffd966}
    nav a{margin-right:12px}
  </style>
</head>
<body>
<header>
  <div class="container">
    <strong><?php echo htmlspecialchars($siteName) ?></strong>
    <nav style="float:right">
      <a href="/landing.php">Home</a>
      <?php if (empty($_SESSION['user_id'])): ?>
        <a href="/auth/register.php">Sign up</a>
        <a href="/auth/login.php">Login</a>
      <?php else: ?>
        <a href="/user/dashboard.php">Dashboard</a>
        <a href="/admin/dashboard.php">Admin</a>
        <a href="/auth/logout.php">Logout</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container" style="padding-top:18px">
