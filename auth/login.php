<?php
// UPDATED: auth/login.php -> add IP & account rate limiting
session_start();
require_once __DIR__ . '/../src/rbac.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/validate.php';
require_once __DIR__ . '/../src/rate_limit.php';
$errors = [];
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Configurable env
$ip_window = intval(getenv('RATE_LIMIT_WINDOW_SECONDS') ?: 60);
$ip_max = intval(getenv('RATE_LIMIT_MAX_REQUESTS') ?: 60);
$acct_window = intval(getenv('RATE_LIMIT_ACCOUNT_WINDOW_SECONDS') ?: 300);
$acct_max = intval(getenv('RATE_LIMIT_ACCOUNT_MAX_REQUESTS') ?: 10);
$login_fail_limit = intval(getenv('LOGIN_FAIL_LIMIT') ?: 5);
$login_lock_minutes = intval(getenv('LOGIN_FAIL_LOCK_MINUTES') ?: 15);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid_or_die();

    // Rate limit by IP for login attempts
    $ipKey = 'ip:'.$ip.':login';
    rate_limit_check_or_block($ipKey, $ip_max, $ip_window);

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!v_required($email) || !v_email($email) || !v_required($password)) {
        $errors[] = 'Please provide valid credentials.';
    } else {
        $pdo = get_pdo();
        $st = $pdo->prepare('SELECT id,password_hash,failed_login_attempts,locked_until FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $u = $st->fetch(PDO::FETCH_ASSOC);

        // Account-level rate limit (based on email) - throttle both existent and non-existent attempts
        $acctKey = 'acct:'.strtolower($email).':login';
        $acctRes = rate_limit_increment($acctKey, $acct_max, $acct_window);
        if (!$acctRes['allowed']) {
            http_response_code(429); echo 'Too many attempts for this account. Try later.'; exit;
        }

        if (!$u) $errors[] = 'Invalid credentials.';
        else {
            // enforce account lock
            if ($u['locked_until'] && strtotime($u['locked_until']) > time()) { $errors[] = 'Account locked. Try later.'; }
            elseif (!password_verify($password, $u['password_hash'])) {
                // increment failed attempts
                $upd = $pdo->prepare('UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?');
                $upd->execute([$u['id']]);
                // check failed attempts now
                $st2 = $pdo->prepare('SELECT failed_login_attempts FROM users WHERE id = ? LIMIT 1'); $st2->execute([$u['id']]); $fa = intval($st2->fetchColumn());
                if ($fa >= $login_fail_limit) {
                    $lockUntil = date('Y-m-d H:i:s', time() + $login_lock_minutes*60);
                    $upd2 = $pdo->prepare('UPDATE users SET locked_until = ? WHERE id = ?'); $upd2->execute([$lockUntil, $u['id']]);
                }
                $errors[] = 'Invalid credentials.';
            } else {
                // success
                $_SESSION['user_id'] = $u['id'];
                $upd = $pdo->prepare('UPDATE users SET failed_login_attempts = 0, last_login = NOW(), locked_until = NULL WHERE id = ?');
                $upd->execute([$u['id']]);
                header('Location: /admin/dashboard.php'); exit;
            }
        }
    }
}
function get_pdo(){
    return (new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASS')));
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Login</title></head><body>
<h1>Login</h1>
<?php foreach($errors as $e) echo '<p style="color:red">'.htmlspecialchars($e).'</p>'; ?>
<form method="post">
  <?php echo csrf_token_field(); ?>
  <label>Email <input type="email" name="email" required></label><br>
  <label>Password <input type="password" name="password" required></label><br>
  <button type="submit">Login</button>
</form>
<p><a href="/auth/forgot.php">Forgot password?</a></p>
</body></html>
