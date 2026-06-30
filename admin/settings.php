<?php
// admin/settings.php (extended)
require_once __DIR__ . '/../src/crypto.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /auth/login.php'); exit; }
require_once __DIR__ . '/../src/rbac.php';
if (!user_has_permission((int)$_SESSION['user_id'], 'manage_settings')) { http_response_code(403); echo 'Forbidden'; exit; }
$pdo = get_pdo();
$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $bullets = trim($_POST['bullets'] ?? '');
    // Save into app_settings
    $encSite = encrypt_secret($site_name);
    $encContact = encrypt_secret($contact_email);
    $encBullets = encrypt_secret($bullets);
    $stmt = $pdo->prepare('INSERT INTO app_settings (`key`, encrypted_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE encrypted_value = VALUES(encrypted_value)');
    $stmt->execute(['site_name', $encSite]);
    $stmt->execute(['contact_email', $encContact]);
    $stmt->execute(['site_bullets', $encBullets]);
    $messages[] = 'Settings saved.';
}
function get_setting(PDO $pdo, $key) {
    $st = $pdo->prepare('SELECT encrypted_value FROM app_settings WHERE `key` = ? LIMIT 1');
    $st->execute([$key]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if (!$r) return null; try { return decrypt_secret($r['encrypted_value']); } catch(Exception $e) { return null; }
}
$site_name = get_setting($pdo, 'site_name');
$contact_email = get_setting($pdo, 'contact_email');
$bullets = get_setting($pdo, 'site_bullets');
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Admin Settings</title></head><body>
<h1>Admin Settings</h1>
<?php foreach ($messages as $m) echo '<p style="color:green">'.htmlspecialchars($m).'</p>'; ?>
<form method="post">
  <label>Site name <input type="text" name="site_name" value="<?php echo htmlspecialchars($site_name ?? '') ?>"></label><br>
  <label>Contact email <input type="text" name="contact_email" value="<?php echo htmlspecialchars($contact_email ?? '') ?>"></label><br>
  <label>Site bullets (one per line)<br><textarea name="bullets" rows="6" cols="60"><?php echo htmlspecialchars($bullets ?? '') ?></textarea></label><br>
  <button type="submit">Save</button>
</form>
</body></html>
