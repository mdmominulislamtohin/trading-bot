<?php
// src/deposit_processor.php
// Provides functions to process deposits idempotently and update ledger.

function get_pdo_conn() {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function required_confirmations_for_chain(string $chain): int {
    $chain = strtolower($chain);
    $defaults = [
        'bsc' => intval(getenv('CONFIRMATIONS_BSC') ?: 12),
        'arbitrum' => intval(getenv('CONFIRMATIONS_ARB') ?: 15),
        'ethereum' => intval(getenv('CONFIRMATIONS_ETH') ?: 12)
    ];
    return $defaults[$chain] ?? intval(getenv('CONFIRMATIONS_DEFAULT') ?: 12);
}

function find_wallet_by_address(PDO $pdo, string $address) {
    $st = $pdo->prepare('SELECT id,user_id,chain,address,label FROM wallets WHERE address = ? LIMIT 1');
    $st->execute([$address]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

function ledger_exists_for_deposit(PDO $pdo, $depositId) {
    $st = $pdo->prepare('SELECT id FROM ledger_transactions WHERE deposit_id = ? LIMIT 1');
    $st->execute([$depositId]);
    return (bool)$st->fetchColumn();
}

function process_single_deposit(PDO $pdo, array $deposit): array {
    // Returns ['ok'=>bool,'message'=>string]
    try {
        // determine chain and confirmations
        $chain = $deposit['chain'] ?? ($deposit['chain_name'] ?? '');
        $conf = isset($deposit['confirmations']) ? intval($deposit['confirmations']) : 0;
        $required = required_confirmations_for_chain($chain);
        if ($conf < $required) return ['ok'=>false,'message'=>"Not enough confirmations ({$conf}/{$required})"];

        // find wallet by address (to_address or to)
        $toAddress = $deposit['to_address'] ?? $deposit['address'] ?? $deposit['to'] ?? null;
        if (!$toAddress) return ['ok'=>false,'message'=>'Deposit missing destination address'];
        $wallet = find_wallet_by_address($pdo, $toAddress);
        if (!$wallet) return ['ok'=>false,'message'=>'No wallet found for address '.$toAddress];

        // idempotency check
        if (isset($deposit['id']) && ledger_exists_for_deposit($pdo, $deposit['id'])) {
            // mark deposit processed to be safe
            $upd = $pdo->prepare('UPDATE deposits SET processed = 1, processed_at = NOW() WHERE id = ?');
            if (isset($deposit['id'])) $upd->execute([$deposit['id']]);
            return ['ok'=>false,'message'=>'Ledger already exists for deposit'];
        }

        // prepare amount
        // prefer amount_raw field, else amount
        $amountRaw = $deposit['amount_raw'] ?? $deposit['amount_wei'] ?? null;
        $amount = $deposit['amount'] ?? null; // human-readable
        if ($amountRaw === null) {
            // try to convert decimal to raw by multiplying with 1e18 if amount present
            if ($amount !== null) {
                // naive conversion
                $amountRaw = bcmul(str_replace(',','',$amount), bcpow('10','18',0));
            } else {
                return ['ok'=>false,'message'=>'Deposit amount not found'];
            }
        }

        // Insert ledger and update wallet balance (if wallet.balance exists)
        $pdo->beginTransaction();
        // double-check ledger uniqueness
        if (isset($deposit['id']) && ledger_exists_for_deposit($pdo, $deposit['id'])) {
            $pdo->commit();
            return ['ok'=>false,'message'=>'Ledger already exists (after recheck)'];
        }

        $ins = $pdo->prepare('INSERT INTO ledger_transactions (user_id,wallet_id,deposit_id,amount_raw,amount,chain,type,metadata,created_at) VALUES (?,?,?,?,?,?,?,?,NOW())');
        $metadata = json_encode(['tx_hash'=>$deposit['tx_hash'] ?? $deposit['txid'] ?? null, 'confirmations'=>$conf]);
        $ins->execute([$wallet['user_id'],$wallet['id'], $deposit['id'] ?? null, $amountRaw, $amount, $chain, 'deposit', $metadata]);

        // attempt to update wallets.balance if column exists
        $hasBalanceCol = false;
        $res = $pdo->query("SHOW COLUMNS FROM wallets LIKE 'balance'")->fetchAll();
        if (count($res)) $hasBalanceCol = true;
        if ($hasBalanceCol) {
            // assume balance stored in decimal human format; if amount present use amount else derive
            if ($amount === null && $amountRaw !== null) {
                // naive conversion:
                $amtHuman = bcdiv($amountRaw, bcpow('10','18',0), 18);
            } else {
                $amtHuman = $amount;
            }
            // update using expression to avoid race
            $upd = $pdo->prepare('UPDATE wallets SET balance = COALESCE(balance,0) + ? WHERE id = ?');
            $upd->execute([$amtHuman, $wallet['id']]);
        }

        // mark deposit processed
        if (isset($deposit['id'])) {
            $upd = $pdo->prepare('UPDATE deposits SET processed = 1, processed_at = NOW(), processed_txid = ? WHERE id = ?');
            $upd->execute([$deposit['tx_hash'] ?? $deposit['txid'] ?? null, $deposit['id']]);
        }

        $pdo->commit();
        return ['ok'=>true,'message'=>'Processed deposit for wallet '.$wallet['id']];
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['ok'=>false,'message'=>'Exception: '.$ex->getMessage()];
    }
}

function process_pending_deposits($limit = 50) {
    $pdo = get_pdo_conn();
    $out = [];
    // select deposits not processed and with confirmations >= required for their chain
    $st = $pdo->prepare('SELECT * FROM deposits WHERE processed = 0 ORDER BY id ASC LIMIT ?');
    $st->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $res = process_single_deposit($pdo, $r);
        $out[] = ['deposit_id'=>$r['id'] ?? null, 'ok'=>$res['ok'], 'message'=>$res['message']];
    }
    return $out;
}

function get_pdo() { return get_pdo_conn(); }
