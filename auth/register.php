<?php
// UPDATED: auth/register.php (wired to send verification email using src/mailer.php)
require_once __DIR__ . '/../src/crypto.php';
require_once __DIR__ . '/../src/wallet/EvmWallet.php';
require_once __DIR__ . '/../src/rbac.php';
require_once __DIR__ . '/../src/mailer.php';
session_start();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        $errors[] = 'Please provide valid name, email and password (min 8 chars).';
    } else {
        $pdo = get_pdo();
        // check exists
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        if ($st->fetchColumn()) {
            $errors[] = 'Email already registered.';
        } else {
            $passHash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,is_active,created_at) VALUES (?,?,?,?,1,NOW())');
            $ins->execute([$name, $email, $passHash, 'user']);
            $userId = $pdo->lastInsertId();

            // auto-create wallets (bsc, arbitrum)
            $chains = ['bsc','arbitrum'];
            foreach ($chains as $chain) {
                $priv = EvmWallet::generatePrivateKey();
                $address = EvmWallet::privateKeyToAddress($priv);
                $enc = encrypt_secret($priv);
                $stmt = $pdo->prepare('INSERT INTO wallets (user_id,chain,address,encrypted_privkey,label,created_at) VALUES (?,?,?,?,?,NOW())');
                $stmt->execute([$userId, $chain, $address, $enc, 'Primary '.$chain]);
            }

            // generate verification token
            $token = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', time() + 60*60*24);
            $upd = $pdo->prepare('UPDATE users SET verification_token = ?, verification_expires_at = ? WHERE id = ?');
            $upd->execute([$token, $expires, $userId]);

            // assign default role (user)
            assign_role_to_user((int)$userId, 'user');

            // Send verification email if possible
            $siteName = getenv('SITE_NAME') ?: 'Trading Bot';
            $verifyLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/auth/verify.php?token=' . urlencode($token);
            $tpl = file_get_contents(__DIR__ . '/../templates/email_verification.tpl');
            $body = str_replace(['{{name}}','{{link}}','{{site_name}}'], [htmlspecialchars($name), $verifyLink, htmlspecialchars($siteName)], $tpl);
            $sendRes = send_email($email, "Verify your email for {$siteName}", $body, "Verify: {$verifyLink}");
            if (!$sendRes['success']) {
                // fallback: show token on success page
                $_SESSION['just_registered_token'] = $token;
                $_SESSION['mail_error'] = $sendRes['error'];
            }
            header('Location: /auth/register_success.php'); exit;
        }
    }
}

function get_pdo(){
    return (new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASS')));
}

?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Register</title></head><body>
<h1>Register</h1>
<?php foreach($errors as $e) echo '<p style="color:red">'.htmlspecialchars($e).'</p>'; ?>
<form method="post">
  <label>Name <input type="text" name="name" required></label><br>
  <label>Email <input type="email" name="email" required></label><br>
  <label>Password <input type="password" name="password" required></label><br>
  <button type="submit">Register</button>
</form>
</body></html>
