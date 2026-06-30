# docs/rate_limit.md

Rate limiting and brute-force protections

What was added
- DB table: migrations/0005_rate_limits.sql
- src/rate_limit.php: DB-backed rate limiter using the rate_limits table
- Login endpoint (auth/login.php) updated to:
  - Rate limit by IP (RATE_LIMIT_WINDOW_SECONDS / RATE_LIMIT_MAX_REQUESTS)
  - Account-based rate limit per email (RATE_LIMIT_ACCOUNT_WINDOW_SECONDS / RATE_LIMIT_ACCOUNT_MAX_REQUESTS)
  - Existing failed_login_attempts counter used to lock account after LOGIN_FAIL_LIMIT failures for LOGIN_FAIL_LOCK_MINUTES
- tools/test_rate_limit.php to simulate repeated requests

Environment variables (defaults used if omitted)
- RATE_LIMIT_WINDOW_SECONDS (default 60)
- RATE_LIMIT_MAX_REQUESTS (default 60)
- RATE_LIMIT_ACCOUNT_WINDOW_SECONDS (default 300)
- RATE_LIMIT_ACCOUNT_MAX_REQUESTS (default 10)
- LOGIN_FAIL_LIMIT (default 5)
- LOGIN_FAIL_LOCK_MINUTES (default 15)

How to test
1) Run migration:
   mysql -u DB_USER -p -h DB_HOST DB_NAME < migrations/0005_rate_limits.sql
2) Use the test tool to simulate attempts:
   php tools/test_rate_limit.php --key="ip:127.0.0.1:login" --times=10 --window=60 --max=5
3) Try HTTP requests (curl) to /auth/login.php repeatedly from same IP and observe 429 after threshold.

Notes
- This is a simple DB-backed limiter. For higher throughput or distributed setups, use Redis or a dedicated rate-limiter.
- Account-based limits are conservative; adjust thresholds to your needs.
