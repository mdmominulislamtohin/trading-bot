# docs/bignum.md

Big-number (wei) migration and helpers

Overview
- Goal: store all amounts in the smallest unit (wei-like integer) as the canonical source of truth to avoid floating/decimal rounding issues.
- Files added:
  - migrations/0006_add_raw_amount_columns.sql
  - src/bignum.php (toWei/fromWei helpers)
  - tools/backfill_amounts.php (dry-run & batch backfill)
  - updated src/deposit_processor.php to prefer amount_raw
  - updated admin/deposits.php UI display

Pre-requisites
- ext-bcmath must be enabled (php -m | grep bcmath)
- Take a DB backup before running migrations/backfill

Backfill (staging recommended)
1) Run migration to add raw columns:
   mysql -u DB_USER -p -h DB_HOST DB_NAME < migrations/0006_add_raw_amount_columns.sql
2) Dry-run backfill on staging:
   php tools/backfill_amounts.php --dry-run --batch=500
3) Real backfill (staging):
   php tools/backfill_amounts.php --batch=500
4) Verify counts and sums; then repeat on production in maintenance window.

Notes
- Migration is additive; legacy human columns (amount, balance) are not removed. After verification you may decide to stop using them in code and eventually drop them.
- All new code uses amount_raw as canonical field. If amount_raw is missing, code will attempt to derive it from existing human column.
