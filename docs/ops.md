# docs/ops.md

Hostinger deployment notes & cron setup

1. Database
 - Run the SQL in migrations/0001_create_users_wallets_and_audit.sql against your MySQL database (via phpMyAdmin or CLI).

2. Environment
 - Create a .env file or set environment variables via Hostinger control panel with the keys in .env.example. APP_MASTER_KEY must be strong and kept secret.

3. Cron
 - Setup cron to run deposit poller every 3 minutes (recommended):
   /usr/bin/php /home/youruser/public_html/tools/check_deposits.php >> /home/youruser/logs/check_deposits.log 2>&1

4. Wallet generation
 - Wallets will be generated automatically when a new user registers (we scaffold generate call). If OpenSSL secp256k1 is not available on Hostinger, run tools/generate_wallet.php locally on a secure machine and import the encrypted private key using the same encrypt_secret logic (see src/crypto.php).

5. Admin
 - Place admin/ behind HTTPS and enable strong password + enable TOTP for admin users.
 - Admin decrypt/export actions require TOTP and are logged to wallet_exports.

6. Security
 - Keep APP_MASTER_KEY outside the repository and rotate if compromised.
 - Keep hot wallet balances minimal. Use multisig/cold storage for large reserves.
 - Consult legal counsel for custodial services and AML/KYC requirements.
