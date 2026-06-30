-- migrations/0004_ledger.sql
-- Adds ledger_transactions table and processed flags to deposits

ALTER TABLE deposits
  ADD COLUMN processed TINYINT(1) DEFAULT 0,
  ADD COLUMN processed_at DATETIME DEFAULT NULL,
  ADD COLUMN processed_txid VARCHAR(128) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS ledger_transactions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  wallet_id BIGINT DEFAULT NULL,
  deposit_id BIGINT DEFAULT NULL,
  amount_raw DECIMAL(38,0) NOT NULL,
  amount DECIMAL(36,18) DEFAULT NULL,
  chain VARCHAR(50) DEFAULT NULL,
  type VARCHAR(50) DEFAULT 'deposit',
  metadata JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ux_deposit_id (deposit_id),
  INDEX (user_id),
  INDEX (wallet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- end migration
