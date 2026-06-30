<?php
// tools/check_deposits.php
// Poller to process deposits into ledger transactions. Run via cron every few minutes.

require_once __DIR__ . '/../src/deposit_processor.php';

$limit = intval(getenv('DEPOSIT_POLL_LIMIT') ?: 100);
$results = process_pending_deposits($limit);
foreach ($results as $r) {
    if ($r['ok']) echo "OK: deposit {$r['deposit_id']} -> {$r['message']}\n";
    else echo "SKIP/ERR: deposit {$r['deposit_id']} -> {$r['message']}\n";
}

if (empty($results)) echo "No pending deposits found.\n";
