<?php
// tools/setup_admin_totp.php
// Usage: php setup_admin_totp.php --admin=1
// Generates a base32 secret, stores it encrypted in users.totp_secret_encrypted, and prints an otpauth:// URL for scanning.

require_once __DIR__ . '/../src/crypto.php';
require_once __DIR__ . '/../src/totp.php';

$options = getopt('', ['admin:']);
if (empty($options['admin'])) {
    echo "Usage: php setup_admin_totp.php --admin=ADMIN_USER_ID\n";
    exit(1);
}
$adminId = intval($options['admin']);
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'trading';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Generate random base32 secret
$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
$secret = '';
for ($i = 0; $i < 16; $i++) {
    $secret .= $alphabet[random_int(0, 31)];
}

// Encrypt and store
$enc = encrypt_secret($secret);
$stmt = $pdo->prepare('UPDATE users SET totp_secret_encrypted = ? WHERE id = ?');
$stmt->execute([$enc, $adminId]);

$issuer = urlencode('TradingBot');
$label = urlencode('admin-' . $adminId);
otpauth = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";

echo "TOTP secret set for admin {$adminId}\n";
echo "Secret (BASE32): {$secret}\n";
echo "OTPAuth URL (scan in Google Authenticator / Authy):\n" . $otpauth . "\n";

// Optional: generate ASCII QR code through Google Charts URL
$chart = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode($otpauth);
echo "QR URL: {$chart}\n";
