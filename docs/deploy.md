# docs/deploy.md

Deployment checklist (minimal)

1. Environment
- Copy .env.example to .env and set values: APP_MASTER_KEY, DB_HOST, DB_NAME, DB_USER, DB_PASS, APP_ENV=production, APP_TIMEZONE=UTC
- Ensure APP_MASTER_KEY is a securely generated 64+ hex string. Keep it safe: it is used to encrypt secrets.

2. Migrations
- Run migrations in order:
  mysql -u DB_USER -p -h DB_HOST DB_NAME < migrations/0001_create_users_wallets_and_audit.sql
  mysql -u DB_USER -p -h DB_HOST DB_NAME < migrations/0002_create_app_settings.sql
  mysql -u DB_USER -p -h DB_HOST DB_NAME < migrations/0003_users_rbac_and_auth.sql

3. File permissions
- Ensure web server can write logs directory:
  mkdir -p logs && chown www-data:www-data logs || true
  chmod 750 logs

4. Installer
- If you used install.php, DELETE or rename it after installation.

5. Cron
- Add cron for deposit poller (example):
  */3 * * * * /usr/bin/php /path/to/project/tools/check_deposits.php >> /path/to/project/logs/check_deposits.log 2>&1

6. Tests
- Register a user, verify email, login, test forgot/reset flows, and check admin settings editing.

7. Troubleshooting
- Check logs/php_error.log for PHP errors when APP_ENV=production.

