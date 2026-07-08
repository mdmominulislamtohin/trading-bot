<?php
// tools/generate_wallet.php
// Usage (CLI): php generate_wallet.php --user=123 --chain=bsc --label="User BSC Wallet"

require_once __DIR__ . '/../src/crypto.php';
require_once __DIR__ . '/../src/wallet/EvmWallet.php';

// Simple arg parser
$options = getopt('', ['user:', 'chain:', 'label::']);
if (empty($options['user']) || empty($options['chain'])) {
    echo "Usage: php generate_wallet.php --user=USER_ID --chain=bsc|arbitrum [--label=LABEL]\n";
    exit(1);
}
$userId = intval($options['user']);
$chain = strtolower($options['chain']);
$label = $options['label'] ?? null;

// Generate private key
$priv = EvmWallet::generatePrivateKey();
// Attempt to derive address (may throw if host lacks ECC support)
try {
    $result = EvmWallet::createAddressForChain($priv, $chain);
    $address = $result['address'];
} catch (Exception $e) {
    echo "Wallet generation failed: " . $e->getMessage() . "\n";
    echo "If OpenSSL secp256k1 is not available on your host, enable it or run wallet generation on a secure machine and import using the import script.\n";
    exit(1);
}

// Encrypt private key
$enc = encrypt_secret(hex2bin($priv));

// Insert into DB
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'trading';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare('INSERT INTO wallets (user_id, chain, address, encrypted_privkey, label) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $chain, $address, $enc, $label]);
    echo "Wallet created for user {$userId} on chain {$chain}: {$address}\n";
} catch (PDOException $ex) {
    echo "DB error: " . $ex->getMessage() . "\n";
    exit(1);
}
