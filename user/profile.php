<?php
// user/profile.php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/validate.php';
require_once __DIR__ . '/../src/mailer.php';

if (empty($_SESSION['user_id'])) { header('Location: /auth/login.php'); exit; }
$uid = (int)$_SESSION['user_id'];
$pdo = new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASS'));
$msgs = [];
$errs = [];

// fetch current
$st = $pdo->prepare('SELECT id,name,email FROM users WHERE id = ? LIMIT 1');
$st->execute([$uid]);
$user = $st->fetch(PDO::FETCH_ASSOC);
if (!$user) { echo 'User not found'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid_or_die();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if (!v_required($name)) $errs[] = 'Name is required';
    if (!v_required($email) || !v_email($email)) $errs[] = 'Valid email is required';
    if (empty($errs)) {
        // If email changed, mark unverified and send verification
        $emailChanged = ($email !== $user['email']);
        $upd = $pdo->prepare('UPDATE users SET name = ?, email = ?, email_verified = ?, verification_token = ?, verification_expires_at = ? WHERE id = ?');
        $token = null; $expires = null; $verified = $user['email_verified'];
        if ($emailChanged) {
            $verified = 0;
            $token = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', time() + 60*60*24);
        }
        $upd->execute([$name, $email, $verified, $token, $expires, $uid]);
        $msgs[] = 'Profile updated.';
        if ($emailChanged) {
            $siteName = getenv('SITE_NAME') ?: 'Trading Bot';
            $verifyLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/auth/verify.php?token=' . urlencode($token);
            $tpl = file_get_contents(__DIR__ . '/../templates/email_verification.tpl');
            $body = str_replace(['{{name}}','{{link}}','{{site_name}}'], [htmlspecialchars($name), $verifyLink, htmlspecialchars($siteName)], $tpl);
            $res = send_email($email, "Verify your email for {$siteName}", $body, "Verify: {$verifyLink}");
            if (!$res['success']) {
                $msgs[] = 'Verification email failed to send: ' . ($res['error'] ?? 'unknown');
            } else {
                $msgs[] = 'Verification email sent to new address.';
            }
        }
        // refresh user
        $st = $pdo->prepare('SELECT id,name,email FROM users WHERE id = ? LIMIT 1'); $st->execute([$uid]); $user = $st->fetch(PDO::FETCH_ASSOC);
    }
}
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
<p><a href="/user/dashboard.php">Back to dashboard</a></p>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
