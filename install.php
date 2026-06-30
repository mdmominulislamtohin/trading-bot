<?php
// install.php
// Web installer for Trading Bot (Hostinger-friendly). IMPORTANT: Remove this file after successful installation.

session_start();
require_once __DIR__ . '/install/helpers.php';

$errors = [];
$success = null;
$showResult = false;

$extensions = check_php_extensions();

// Preserve posted values
$posted = [
    'DB_HOST' => $_POST['DB_HOST'] ?? '127.0.0.1',
    'DB_NAME' => $_POST['DB_NAME'] ?? 'trading',
    'DB_USER' => $_POST['DB_USER'] ?? 'root',
    'DB_PASS' => $_POST['DB_PASS'] ?? '',
    'ADMIN_EMAIL' => $_POST['ADMIN_EMAIL'] ?? 'admin@example.com',
    'ADMIN_NAME' => $_POST['ADMIN_NAME'] ?? 'Admin',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $db_host = trim($_POST['DB_HOST'] ?? '127.0.0.1');
        $db_name = trim($_POST['DB_NAME'] ?? 'trading');
        $db_user = trim($_POST['DB_USER'] ?? 'root');
        $db_pass = $_POST['DB_PASS'] ?? '';
        $admin_email = trim($_POST['ADMIN_EMAIL'] ?? '');
        $admin_pass = $_POST['ADMIN_PASSWORD'] ?? '';
        $admin_name = trim($_POST['ADMIN_NAME'] ?? 'Admin');
        $app_key = trim($_POST['APP_MASTER_KEY'] ?? '');
        $run_migrations = isset($_POST['RUN_MIGRATIONS']);
        $enable_totp = isset($_POST['GENERATE_TOTP']);

        // Server-side required validation
        if (!$db_name || !$db_user || !$admin_email || !$admin_pass) {
            $errors[] = 'Please fill all required fields.';
        }

        if (empty($app_key)) $app_key = generate_app_master_key();

        // Prepare env values (API keys omitted from installer)
        $envPath = __DIR__ . '/../.env';
        $envValues = [
            'APP_MASTER_KEY' => $app_key,
            'DB_HOST' => $db_host,
            'DB_NAME' => $db_name,
            'DB_USER' => $db_user,
            'DB_PASS' => $db_pass,
        ];

        // Try to write .env
        $wrote = write_env_file($envPath, $envValues);
        if (!$wrote) {
            $errors[] = 'Failed to write .env file to project root. Please create the file manually with the contents shown below.';
        }

        // Set envs for current runtime so src/crypto.php works
        set_env_runtime($envValues);

        // Attempt DB connection
        try {
            $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
            $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $ex) {
            $errors[] = 'Database connection failed: ' . $ex->getMessage();
            $pdo = null;
        }

        // Run migrations if requested and DB connection ok
        if ($run_migrations && $pdo) {
            $migr = run_sql_file($pdo, __DIR__ . '/../migrations/0001_create_users_wallets_and_audit.sql');
            if (!$migr['success']) {
                $errors[] = 'Migration errors: ' . implode(' | ', $migr['errors']);
            } else {
                // Run app_settings migration too (if not exists)
                $migr2 = run_sql_file($pdo, __DIR__ . '/../migrations/0002_create_app_settings.sql');
                if (!$migr2['success']) $errors[] = 'Migration(errors) for app_settings: ' . implode(' | ', $migr2['errors']);
            }
        }

        // Create initial admin user
        if ($pdo && empty($errors)) {
            try {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
                $stmt->execute([$admin_email]);
                $exists = $stmt->fetchColumn() > 0;
                if (!$exists) {
                    $passHash = password_hash($admin_pass, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())');
                    $ins->execute([$admin_name, $admin_email, $passHash, 'admin']);
                    $adminId = $pdo->lastInsertId();
                } else {
                    $adminId = null; // admin exists
                }
            } catch (PDOException $ex) {
                $errors[] = 'Failed to create admin user: ' . $ex->getMessage();
            }
        }

        // Optionally generate TOTP secret and store encrypted
        $totpSecret = null;
        if ($enable_totp && $pdo && $adminId) {
            // ensure crypto functions available
            require_once __DIR__ . '/../src/crypto.php';
            require_once __DIR__ . '/../src/totp.php';
            $totpSecret = generate_base32_secret();
            try {
                $enc = encrypt_secret($totpSecret);
                $upd = $pdo->prepare('UPDATE users SET totp_secret_encrypted = ? WHERE id = ?');
                $upd->execute([$enc, $adminId]);
            } catch (Exception $ex) {
                $errors[] = 'Failed to store TOTP secret: ' . $ex->getMessage();
            }
        }

        $showResult = true;
        if (empty($errors)) {
            $success = true;
        } else {
            $success = false;
        }

        // Preserve posted values for re-display
        $posted['DB_HOST'] = $db_host;
        $posted['DB_NAME'] = $db_name;
        $posted['DB_USER'] = $db_user;
        $posted['DB_PASS'] = $db_pass;
        $posted['ADMIN_EMAIL'] = $admin_email;
        $posted['ADMIN_NAME'] = $admin_name;
    }
}

// generate CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf_token = $_SESSION['csrf_token'];

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Installer — Trading Bot</title>
<style>body{font-family:Arial,Helvetica,sans-serif;max-width:900px;margin:20px auto;padding:0 16px}label{display:block;margin:8px 0;font-weight:600}input[type=text],input[type=password],textarea{width:100%;padding:8px;border:1px solid #ccc;border-radius:4px}button{padding:10px 16px;border:0;background:#007bff;color:#fff;border-radius:4px}pre{background:#f6f8fa;padding:12px;border:1px solid #eee;overflow:auto}.error{color:#b00020}</style>
</head>
<body>
<h1>Trading Bot — Web Installer</h1>
<p><strong>Important:</strong> After a successful install, delete or rename this file (<code>install.php</code>) immediately.</p>

<h2>Server checks</h2>
<ul>
<?php foreach ($extensions['required'] as $ext => $ok): ?>
  <li><?php echo htmlspecialchars($ext) ?>: <?php echo $ok ? '<strong style="color:green">OK</strong>' : '<strong style="color:red">MISSING</strong>' ?></li>
<?php endforeach; ?>
<?php foreach ($extensions['optional'] as $ext => $ok): ?>
  <li><?php echo htmlspecialchars($ext) ?>: <?php echo $ok ? '<strong style="color:green">Available</strong>' : '<strong style="color:orange">Not available (optional)</strong>' ?></li>
<?php endforeach; ?>
</ul>

<p>API keys (BscScan, Alchemy) can be added later from the Admin → Settings screen after installation. It's safer to add them via admin UI.</p>

<h2>Install</h2>
<form method="post" id="installForm">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token) ?>">
  <label>DB Host
    <input type="text" name="DB_HOST" id="DB_HOST" value="<?php echo htmlspecialchars($posted['DB_HOST']) ?>" required>
  </label>
  <label>DB Name
    <input type="text" name="DB_NAME" id="DB_NAME" value="<?php echo htmlspecialchars($posted['DB_NAME']) ?>" required>
  </label>
  <label>DB User
    <input type="text" name="DB_USER" id="DB_USER" value="<?php echo htmlspecialchars($posted['DB_USER']) ?>" required>
  </label>
  <label>DB Password
    <input type="password" name="DB_PASS" id="DB_PASS" value="<?php echo htmlspecialchars($posted['DB_PASS']) ?>" required>
  </label>

  <h3>Admin account</h3>
  <label>Admin name
    <input type="text" name="ADMIN_NAME" id="ADMIN_NAME" value="<?php echo htmlspecialchars($posted['ADMIN_NAME']) ?>" required>
  </label>
  <label>Admin email
    <input type="text" name="ADMIN_EMAIL" id="ADMIN_EMAIL" value="<?php echo htmlspecialchars($posted['ADMIN_EMAIL']) ?>" required>
  </label>
  <label>Admin password
    <input type="password" name="ADMIN_PASSWORD" id="ADMIN_PASSWORD" required>
  </label>

  <label>APP_MASTER_KEY (optional — leave empty to auto-generate)
    <input type="text" name="APP_MASTER_KEY" id="APP_MASTER_KEY" placeholder="Leave empty to auto-generate">
  </label>

  <label><input type="checkbox" name="RUN_MIGRATIONS" id="RUN_MIGRATIONS" checked> Run DB migrations now</label>
  <label><input type="checkbox" name="GENERATE_TOTP" id="GENERATE_TOTP"> Generate Admin TOTP secret now (recommended)</label>

  <div style="margin-top:12px"><button type="submit" id="installBtn" disabled>Install</button></div>
</form>

<script>
// Client-side validation: enable Install button only when required fields have values
(function(){
  const required = ['DB_HOST','DB_NAME','DB_USER','DB_PASS','ADMIN_EMAIL','ADMIN_PASSWORD','ADMIN_NAME'];
  const installBtn = document.getElementById('installBtn');
  function check(){
    let ok = true;
    for(const id of required){
      const el = document.getElementById(id);
      if(!el || el.value.trim() === ''){ ok = false; break; }
    }
    installBtn.disabled = !ok;
  }
  required.forEach(id => { const el = document.getElementById(id); if(el) el.addEventListener('input', check); });
  // initial check
  check();
})();
</script>

<?php if ($showResult): ?>
  <h2>Result</h2>
  <?php if ($success): ?>
    <p style="color:green;font-weight:700">Installation completed successfully.</p>
    <p><strong>APP_MASTER_KEY:</strong> <?php echo htmlspecialchars($app_key) ?></p>
    <?php if (!empty($totpSecret)): ?>
      <p>Admin TOTP secret (BASE32): <strong><?php echo htmlspecialchars($totpSecret) ?></strong></p>
      <p>OTPAuth URL: <code><?php echo htmlspecialchars("otpauth://totp/TradingBot:admin?secret={$totpSecret}&issuer=TradingBot") ?></code></p>
      <p>Scan the QR or add the secret to Google Authenticator / Authy and then delete this file.</p>
    <?php endif; ?>
    <p>Please delete <code>install.php</code> from your server now (or rename to install.php.disabled).</p>
  <?php else: ?>
    <p class="error">Installation completed with errors:</p>
    <ul class="error">
      <?php foreach ($errors as $e): ?>
        <li><?php echo htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
    <p>If .env was not written, create it manually with the contents below (copy shown values into a file named <code>.env</code> in project root).</p>
  <?php endif; ?>

  <h3>.env contents (if .env could not be written automatically)</h3>
  <pre><?php echo htmlspecialchars(implode("\n", array_map(function($k,$v){ return "$k=$v"; }, array_keys($envValues ?? []), array_values($envValues ?? [])))) ?></pre>

<?php endif; ?>

<hr>
<p><strong>Security note:</strong> This installer creates secrets and writes them to <code>.env</code>. After install, DELETE OR RENAME this file. Storing private keys on shared hosting is risky — keep hot funds minimal and consider cold multisig for large amounts.</p>

</body>
</html>
