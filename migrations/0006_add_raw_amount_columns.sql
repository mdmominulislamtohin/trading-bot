-- migrations/0006_add_raw_amount_columns.sql
-- Add raw integer (wei-like) columns for amounts and balances. Additive, non-destructive.

ALTER TABLE wallets
  ADD COLUMN IF NOT EXISTS balance_raw DECIMAL(38,0) DEFAULT NULL;

ALTER TABLE deposits
  ADD COLUMN IF NOT EXISTS amount_raw DECIMAL(38,0) DEFAULT NULL;

ALTER TABLE ledger_transactions
  ADD COLUMN IF NOT EXISTS amount_raw DECIMAL(38,0) DEFAULT NULL;

-- If withdrawals table exists, add raw columns used for computation
ALTER TABLE withdrawals
  ADD COLUMN IF NOT EXISTS amount_raw DECIMAL(38,0) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS fee_raw DECIMAL(38,0) DEFAULT NULL;

-- Note: This migration only adds columns. A backfill script is provided to populate amount_raw/balance_raw from existing human columns.
