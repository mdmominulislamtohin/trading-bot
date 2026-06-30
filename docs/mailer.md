# docs/mailer.md

Mailer integration

This project supports sending emails via a lightweight SMTP client (built into src/mailer.php) or falling back to PHP's mail().

Required environment variables (add to .env):
- SMTP_HOST (optional): smtp host (if omitted, PHP mail() is used)
- SMTP_PORT (optional): smtp port (default: 25 or 587)
- SMTP_USER (optional): smtp username
- SMTP_PASS (optional): smtp password
- SMTP_ENCRYPT (optional): tls | ssl | none
- MAIL_FROM: default from address (e.g. no-reply@yourdomain.tld)
- MAIL_FROM_NAME: display name

Testing
1. Pull the feature/mailer branch and set .env
2. Run: php tools/send_test_email.php --to=you@domain.tld
3. Register a new user via /auth/register.php and verify the verification email arrives (or, if mail fails, the token will still be shown on the register_success page as a fallback).

Notes
- The SMTP client included is basic and supports AUTH LOGIN and STARTTLS. For production reliability, consider installing a robust mailer library (PHPMailer via composer) and/or a dedicated transactional email provider.
