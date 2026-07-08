<?php
// tools/test_totp.php - simple CLI to test TOTP helper
if (php_sapi_name() !== 'cli') { echo "CLI only\n"; exit(1); }
require_once __DIR__ . '/../src/totp.php';
$secret = generateSecret(20);
echo "Generated secret: {$secret}\n";
$code = getTotpCode($secret);
echo "Current code: {$code}\n";
echo "Verify: ".(verifyTotp($secret,$code)?'OK':'FAIL')."\n";
