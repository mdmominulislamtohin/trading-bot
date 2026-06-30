<?php
// tools/backfill_amounts.php
// Backfill newly added *_raw columns using existing human-readable amount/balance columns.
// Usage:
// php tools/backfill_amounts.php --batch=500 --dry-run

$options = getopt('', ['batch::','dry-run','resume::']);
$batch = intval($options['batch'] ?? 500);
$dry = isset($options['dry-run']);
$resume = $options['resume'] ?? null;

require_once __DIR__ . '/../src/bignum.php';

function get_pdo() {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
$pdo = get_pdo();

echo "Backfill amounts (dry-run=".($dry?"yes":"no").") batch={$batch}\n";

// Helper to process a table with id ordering
function backfill_table($pdo, $table, $idColumn, $humanCol, $rawCol, $batch, $dry) {
    echo "Backfilling {$table}: human={$humanCol} -> raw={$rawCol}\n";
    $lastId = 0;
    while (true) {
        $st = $pdo->prepare("SELECT {$idColumn}, {$humanCol}, {$rawCol} FROM {$table} WHERE {$rawCol} IS NULL AND {$humanCol} IS NOT NULL AND {$humanCol} != '' AND {$idColumn} > ? ORDER BY {$idColumn} ASC LIMIT ?");
        $st->execute([$lastId, $batch]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) { echo "  No more rows to backfill for {$table}.\n"; break; }
        foreach ($rows as $r) {
            $id = $r[$idColumn];
            $human = (string)$r[$humanCol];
            try {
                $raw = toWei($human, 18);
            } catch (Exception $e) {
                echo "  ERROR converting id={$id} human={$human}: {$e->getMessage()}\n";
                continue;
            }
            echo "  id={$id} human={$human} -> raw={$raw}".($dry?" (dry)":"")."\n";
            if (!$dry) {
                $up = $pdo->prepare("UPDATE {$table} SET {$rawCol} = ? WHERE {$idColumn} = ?");
                $up->execute([$raw, $id]);
            }
            $lastId = $id;
        }
        if (count($rows) < $batch) break; // done
    }
}

// Backfill deposits.amount_raw from deposits.amount
try {
    backfill_table($pdo, 'deposits', 'id', 'amount', 'amount_raw', $batch, $dry);
} catch (Exception $e) { echo "Backfill deposits failed: {$e->getMessage()}\n"; }

// Backfill ledger_transactions.amount_raw from ledger_transactions.amount
try {
    backfill_table($pdo, 'ledger_transactions', 'id', 'amount', 'amount_raw', $batch, $dry);
} catch (Exception $e) { echo "Backfill ledger_transactions failed: {$e->getMessage()}\n"; }

// Backfill wallets.balance_raw from wallets.balance
try {
    backfill_table($pdo, 'wallets', 'id', 'balance', 'balance_raw', $batch, $dry);
} catch (Exception $e) { echo "Backfill wallets failed: {$e->getMessage()}\n"; }

echo "Backfill complete.\n";
