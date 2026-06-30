<?php
// UPDATED: auth/register_success.php (show mail error if any)
session_start();
$token = $_SESSION['just_registered_token'] ?? null;
$mailErr = $_SESSION['mail_error'] ?? null;
unset($_SESSION['just_registered_token']); unset($_SESSION['mail_error']);
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Registered</title></head><body>
<h1>Registration complete</h1>
<?php if ($mailErr): ?>
  <p style="color:orange">We tried to send a verification email but it failed: <?php echo htmlspecialchars($mailErr) ?></p>
<?php endif; ?>
<?php if ($token): ?>
  <p>Your account was created. Verification token (use this in dev if email not configured):</p>
  <pre><?php echo htmlspecialchars($token) ?></pre>
  <p>Visit <a href="/auth/verify.php?token=<?php echo urlencode($token) ?>">Verify account</a></p>
<?php else: ?>
  <p>An email has been sent to you with a verification link. Check your inbox.</p>
<?php endif; ?>
</body></html>
