# docs/auth_and_admin.md

Iteration 1: Auth, RBAC & Landing

What's included
- Registration, login, logout, email verification, password reset
- RBAC schema (roles, permissions, user_roles, role_permissions)
- Admin Settings (site name, contact, bullets) editable from Admin → Settings
- Landing page (landing.php) reads settings
- Signup auto-creates BSC & Arbitrum wallets for new users

How to install & test
1. Run migrations (including migrations/0003_users_rbac_and_auth.sql).
2. Ensure APP_MASTER_KEY is set in .env and DB connection is correct.
3. Create / login as admin (installer-created admin is auto-assigned superadmin if present). If not, use tools/create_user_with_role.php (coming) to create.
4. Visit /landing.php to view editable landing page. Visit /auth/register.php to sign up.
5. Admin: /admin/settings.php and /admin/users.php

Password reset/verification (dev fallback)
- If SMTP is not configured, verification and reset tokens are shown on success pages for ease of local testing.
