<?php
// UPDATED: auth/login.php -> add CSRF verify and field
session_start();
require_once __DIR__ . '/../src/rbac.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/validate.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid_or_die();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!v_required($email) || !v_email($email) || !v_required($password)) {
        $errors[] = 'Please provide valid credentials.';
    } else {
        $pdo = get_pdo();
        $st = $pdo->prepare('SELECT id,password_hash,locked_until FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        if (!$u) $errors[] = 'Invalid credentials.';
        else {
            if ($u['locked_until'] && strtotime($u['locked_until']) > time()) { $errors[] = 'Account locked. Try later.'; }
            elseif (!password_verify($password, $u['password_hash'])) {
                // increment failed attempts
                $upd = $pdo->prepare('UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?');
                $upd->execute([$u['id']]);
                $errors[] = 'Invalid credentials.';
            } else {
                // success
                $_SESSION['user_id'] = $u['id'];
                $upd = $pdo->prepare('UPDATE users SET failed_login_attempts = 0, last_login = NOW() WHERE id = ?');
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
