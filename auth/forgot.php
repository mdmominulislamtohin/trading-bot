<?php
// UPDATED: auth/forgot.php (send reset email via mailer)
session_start();
require_once __DIR__ . '/../src/mailer.php';
$sent = false; $token=null; $err=null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!$email) { $err = 'Enter email'; }
    else {
        $pdo = get_pdo();
        $st = $pdo->prepare('SELECT id,name FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        if ($u) {
            $token = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', time() + 60*60);
            $upd = $pdo->prepare('UPDATE users SET password_reset_token = ?, password_reset_expires_at = ? WHERE id = ?');
            $upd->execute([$token, $expires, $u['id']]);

            $siteName = getenv('SITE_NAME') ?: 'Trading Bot';
            $resetLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/auth/reset.php?token=' . urlencode($token);
            $tpl = file_get_contents(__DIR__ . '/../templates/email_reset.tpl');
            $body = str_replace(['{{name}}','{{link}}','{{site_name}}'], [htmlspecialchars($u['name'] ?: $email), $resetLink, htmlspecialchars($siteName)], $tpl);
            $sendRes = send_email($email, "Password reset for {$siteName}", $body, "Reset: {$resetLink}");
            if (!$sendRes['success']) {
                $_SESSION['dev_reset_token'] = $token;
                $_SESSION['mail_error'] = $sendRes['error'];
            }
            $sent = true;
        }
    }
}
function get_pdo(){
    return (new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASS')));
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Forgot password</title></head><body>
<h1>Forgot password</h1>
<?php if (!empty($err)) echo '<p style="color:red">'.htmlspecialchars($err).'</p>'; ?>
<?php if ($sent): ?>
  <p>If an account exists, a reset link has been sent.<?php if (!empty($_SESSION['mail_error'])) echo ' (Mailer error: '.htmlspecialchars($_SESSION['mail_error']).')'; ?></p>
  <pre><?php echo htmlspecialchars($_SESSION['dev_reset_token'] ?? '') ?></pre>
  <p><a href="/auth/reset.php?token=<?php echo urlencode($_SESSION['dev_reset_token'] ?? '') ?>">Use reset link</a></p>
<?php else: ?>
  <form method="post"><label>Email <input type="email" name="email" required></label><button type="submit">Send reset</button></form>
<?php endif; ?>
</body></html>
