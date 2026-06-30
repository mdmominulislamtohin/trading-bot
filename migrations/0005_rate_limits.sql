-- migrations/0005_rate_limits.sql
-- Adds a simple rate_limits table used for IP/account rate limiting

CREATE TABLE IF NOT EXISTS rate_limits (
  `key` VARCHAR(191) NOT NULL PRIMARY KEY,
  `count` INT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` DATETIME DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
