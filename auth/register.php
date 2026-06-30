<?php
// auth/register.php
// Simple registration handler that creates a user and auto-generates BSC & Arbitrum wallets.

require_once __DIR__ . '/../src/crypto.php';
require_once __DIR__ . '/../src/wallet/EvmWallet.php';

// Basic POST-based registration. In production, add validation, captcha, email verification.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
        $dbName = getenv('DB_NAME') ?: 'trading';
        $dbUser = getenv('DB_USER') ?: 'root';
        $dbPass = getenv('DB_PASS') ?: '';
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // Check existing
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            // Create user
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
            $ins->execute([$name, $email, $passwordHash, 'user']);
            $userId = $pdo->lastInsertId();

            // Auto-generate wallets for chains
            $chains = ['bsc','arbitrum'];
            foreach ($chains as $chain) {
                try {
                    $privHex = EvmWallet::generatePrivateKey();
                    $info = EvmWallet::createAddressForChain($privHex, $chain);
                    $address = $info['address'];
                    // encrypt private key (store binary)
                    $enc = encrypt_secret(hex2bin($privHex));
                    $stmt = $pdo->prepare('INSERT INTO wallets (user_id, chain, address, encrypted_privkey, label) VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([$userId, $chain, $address, $enc, 'auto']);
                } catch (Exception $e) {
                    // If address derivation fails, roll back or notify admin - here we continue but log.
                    error_log('Wallet generation failed for user '.$userId.' chain '.$chain.': '.$e->getMessage());
                }
            }

            // Redirect to login or success
            header('Location: /auth/register_success.php');
            exit;
        }
    }
}

?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Register</title></head>
<body>
<h1>Register</h1>
<?php if (!empty($error)) echo '<p style="color:red;">'.htmlspecialchars($error).'</p>'; ?>
<form method="post">
  <label>Name: <input name="name" required></label><br>
  <label>Email: <input name="email" type="email" required></label><br>
  <label>Password: <input name="password" type="password" required></label><br>
  <button type="submit">Register</button>
</form>
</body>
</html>
