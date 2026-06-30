<?php
// UPDATED: user/profile.php -> add TOTP enable/disable flows
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/validate.php';
require_once __DIR__ . '/../src/mailer.php';
require_once __DIR__ . '/../src/totp.php';

if (empty($_SESSION['user_id'])) { header('Location: /auth/login.php'); exit; }
$uid = (int)$_SESSION['user_id'];
$pdo = new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASS'));
$msgs = [];
$errs = [];

// fetch current
$st = $pdo->prepare('SELECT id,name,email,totp_enabled FROM users WHERE id = ? LIMIT 1');
$st->execute([$uid]);
$user = $st->fetch(PDO::FETCH_ASSOC);
if (!$user) { echo 'User not found'; exit; }

// TOTP flows use session to store pending secret during the verify step
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid_or_die();
    if (isset($_POST['action']) && $_POST['action'] === 'generate_totp') {
        // generate secret, store in session pending, show QR for verification
        $secret = generateSecret(20);
        $_SESSION['pending_totp_secret'] = $secret;
        $msgs[] = 'TOTP secret generated. Please verify the code from your authenticator app.';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'confirm_totp') {
        $code = trim($_POST['totp_code'] ?? '');
        $secret = $_SESSION['pending_totp_secret'] ?? null;
        if (!$secret) { $errs[] = 'No pending TOTP setup found. Generate a new secret.'; }
        else {
            if (verifyTotp($secret, $code, 1)) {
                $upd = $pdo->prepare('UPDATE users SET totp_secret = ?, totp_enabled = 1 WHERE id = ?');
                $upd->execute([$secret, $uid]);
                unset($_SESSION['pending_totp_secret']);
                $msgs[] = 'Two‑factor authentication enabled for your account.';
            } else {
                $errs[] = 'Invalid TOTP code. Please try again.';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'disable_totp') {
        // require current totp code to disable
        $code = trim($_POST['totp_code_disable'] ?? '');
        $st = $pdo->prepare('SELECT totp_secret FROM users WHERE id = ? LIMIT 1'); $st->execute([$uid]); $sec = $st->fetchColumn();
        if (!$sec) { $errs[] = 'TOTP not enabled on your account.'; }
        else {
            if (verifyTotp($sec, $code, 1)) {
                $upd = $pdo->prepare('UPDATE users SET totp_secret = NULL, totp_enabled = 0 WHERE id = ?');
                $upd->execute([$uid]);
                $msgs[] = 'Two‑factor authentication disabled for your account.';
            } else {
                $errs[] = 'Invalid TOTP code. Cannot disable.';
            }
        }
    } else {
        // existing profile update (name/email)
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if (!v_required($name)) $errs[] = 'Name is required';
        if (!v_required($email) || !v_email($email)) $errs[] = 'Valid email is required';
        if (empty($errs)) {
            $emailChanged = ($email !== $user['email']);
            $upd = $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
            $upd->execute([$name, $email, $uid]);
            $msgs[] = 'Profile updated.';
        }
    }
}

// fetch fresh user data
$st = $pdo->prepare('SELECT id,name,email,totp_enabled FROM users WHERE id = ? LIMIT 1'); $st->execute([$uid]); $user = $st->fetch(PDO::FETCH_ASSOC);
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Edit Profile</h1>
<?php foreach($msgs as $m) echo '<p style="color:green">'.htmlspecialchars($m).'</p>'; ?>
<?php foreach($errs as $e) echo '<p style="color:red">'.htmlspecialchars($e).'</p>'; ?>
<form method="post">
  <?php echo csrf_token_field(); ?>
  <label>Name <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']) ?>" required></label><br>
  <label>Email <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']) ?>" required></label><br>
  <button type="submit">Save</button>
</form>
<hr>
<h2>Two‑Factor Authentication (2FA)</h2>
<?php if ($user['totp_enabled']): ?>
  <p>2FA is currently enabled on your account.</p>
  <form method="post">
    <?php echo csrf_token_field(); ?>
    <input type="hidden" name="action" value="disable_totp">
    <label>Enter current code to disable <input type="text" name="totp_code_disable" required></label>
    <button type="submit">Disable 2FA</button>
  </form>
<?php else: ?>
  <?php if (!empty($_SESSION['pending_totp_secret'])): ?>
    <?php $secret = $_SESSION['pending_totp_secret']; $otpUrl = getOtpAuthUrl(getenv('SITE_NAME') ?: 'Trading Bot', $user['email'], $secret); ?>
    <p>Scan this QR in your authenticator app or enter this secret: <strong><?php echo htmlspecialchars($secret) ?></strong></p>
    <p>otpauth URL: <code><?php echo htmlspecialchars($otpUrl) ?></code></p>
    <form method="post">
      <?php echo csrf_token_field(); ?>
      <input type="hidden" name="action" value="confirm_totp">
      <label>Enter code from app <input type="text" name="totp_code" required></label>
      <button type="submit">Confirm & Enable 2FA</button>
    </form>
  <?php else: ?>
    <form method="post">
      <?php echo csrf_token_field(); ?>
      <input type="hidden" name="action" value="generate_totp">
      <button type="submit">Enable 2FA</button>
    </form>
  <?php endif; ?>
<?php endif; ?>

<p><a href="/user/dashboard.php">Back to dashboard</a></p>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
