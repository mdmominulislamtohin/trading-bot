-- migrations/0007_add_totp_columns.sql
-- Add TOTP columns to users table

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS totp_secret VARCHAR(128) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS totp_enabled TINYINT(1) DEFAULT 0;
