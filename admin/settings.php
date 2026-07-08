<?php
// admin/settings.php
// Admin page to manage application settings (BSCSCAN/ALCHEMY API keys etc.)

require_once __DIR__ . '/../src/crypto.php';
session_start();
// Replace with your real admin auth. Here we check a simple session flag.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'trading';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF and TOTP verification should be here in production
    $bsc = trim($_POST['BSCSCAN_API_KEY'] ?? '');
    $alchemy = trim($_POST['ALCHEMY_API_KEY'] ?? '');
    try {
        if ($bsc !== '') {
            $enc = encrypt_secret($bsc);
            $stmt = $pdo->prepare('INSERT INTO app_settings (`key`, encrypted_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE encrypted_value = VALUES(encrypted_value)');
            $stmt->execute(['BSCSCAN_API_KEY', $enc]);
        }
        if ($alchemy !== '') {
            $enc = encrypt_secret($alchemy);
            $stmt = $pdo->prepare('INSERT INTO app_settings (`key`, encrypted_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE encrypted_value = VALUES(encrypted_value)');
            $stmt->execute(['ALCHEMY_API_KEY', $enc]);
        }
        $messages[] = 'Settings saved successfully.';
    } catch (Exception $e) {
        $messages[] = 'Failed to save settings: ' . $e->getMessage();
    }
}

function get_setting(PDO $pdo, $key) {
    $st = $pdo->prepare('SELECT encrypted_value FROM app_settings WHERE `key` = ? LIMIT 1');
    $st->execute([$key]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if (!$r) return null;
    try {
        return decrypt_secret($r['encrypted_value']);
    } catch (Exception $e) {
        return null;
    }
}

$bsc_val = get_setting($pdo, 'BSCSCAN_API_KEY');
$alchemy_val = get_setting($pdo, 'ALCHEMY_API_KEY');

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><title>Admin Settings</title>
<style>label{display:block;margin:8px 0}input[type=text]{width:100%;padding:8px}</style>
</head>
<body>
<h1>Admin Settings</h1>
<?php foreach ($messages as $m) echo '<p style="color:green">'.htmlspecialchars($m).'</p>'; ?>
<form method="post">
  <label>BSCSCAN API Key
    <input type="text" name="BSCSCAN_API_KEY" value="<?= htmlspecialchars($bsc_val ? str_repeat('*', 8) : '') ?>" placeholder="Enter new to update">
  </label>
  <label>Alchemy API Key (Arbitrum)
    <input type="text" name="ALCHEMY_API_KEY" value="<?= htmlspecialchars($alchemy_val ? str_repeat('*', 8) : '') ?>" placeholder="Enter new to update">
  </label>
  <p>Note: Existing keys are masked. Enter a new key to update.</p>
  <button type="submit">Save Settings</button>
</form>
</body>
</html>
