<?php
// auth/reset.php
session_start();
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$done = false; $err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $token = $_POST['token'] ?? '';
    if (strlen($password) < 8) { $err = 'Password too short'; }
    else {
        $pdo = get_pdo();
        $st = $pdo->prepare('SELECT id,password_reset_expires_at FROM users WHERE password_reset_token = ? LIMIT 1');
        $st->execute([$token]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        if (!$u) $err = 'Invalid token';
        elseif (strtotime($u['password_reset_expires_at']) < time()) $err = 'Token expired';
        else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare('UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires_at = NULL WHERE id = ?');
            $upd->execute([$hash, $u['id']]);
            $done = true;
        }
    }
}
function get_pdo(){
    return (new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASS')));
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Reset password</title></head><body>
<h1>Reset password</h1>
<?php if ($done): ?>
  <p>Password updated. <a href="/auth/login.php">Login</a></p>
<?php else: ?>
  <?php if ($err) echo '<p style="color:red">'.htmlspecialchars($err).'</p>'; ?>
  <form method="post">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token) ?>">
    <label>New password <input type="password" name="password" required></label>
    <button type="submit">Set password</button>
  </form>
<?php endif; ?>
</body></html>
