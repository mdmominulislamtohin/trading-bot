<?php
// tools/check_deposits.php
// Cron script to poll BscScan (BSC) and Alchemy (Arbitrum) for incoming deposits to wallets table.
// Configure environment variables: BSCSCAN_API_KEY, ALCHEMY_API_KEY, DB_*

require_once __DIR__ . '/../src/crypto.php';

$bsckey = getenv('BSCSCAN_API_KEY');
$alchemy = getenv('ALCHEMY_API_KEY');
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'trading';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
$pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Fetch all active wallets
$stmt = $pdo->query('SELECT id, user_id, chain, address FROM wallets WHERE is_active=1');
$wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($wallets as $w) {
    $chain = strtolower($w['chain']);
    $address = $w['address'];
    try {
        if ($chain === 'bsc') {
            // BscScan: get normal transactions (native) and token transfers
            if (!$bsckey) continue;
            $url = "https://api.bscscan.com/api?module=account&action=txlist&address={$address}&startblock=0&endblock=99999999&sort=asc&apikey={$bsckey}";
            $res = json_decode(file_get_contents($url), true);
            if (isset($res['result']) && is_array($res['result'])) {
                foreach ($res['result'] as $tx) {
                    // incoming native transfer (to == address)
                    if (strtolower($tx['to']) === strtolower($address)) {
                        upsert_deposit($pdo, $w['id'], $tx['hash'], $tx['from'], $tx['to'], $tx['value'], null, 'bsc', $tx['blockNumber']);
                    }
                }
            }
            // Token transfers
            $turl = "https://api.bscscan.com/api?module=account&action=tokentx&address={$address}&startblock=0&endblock=99999999&sort=asc&apikey={$bsckey}";
            $tres = json_decode(file_get_contents($turl), true);
            if (isset($tres['result']) && is_array($tres['result'])) {
                foreach ($tres['result'] as $tx) {
                    if (strtolower($tx['to']) === strtolower($address)) {
                        upsert_deposit($pdo, $w['id'], $tx['hash'], $tx['from'], $tx['to'], $tx['value']/pow(10, intval($tx['tokenDecimal'])), $tx['tokenSymbol'], 'bsc', $tx['blockNumber']);
                    }
                }
            }
        } elseif ($chain === 'arbitrum') {
            // Alchemy: use getLogs for the address (native transfers are not logs; for native ETH, use tx history via Alchemy or third party)
            if (!$alchemy) continue;
            // Example: fetch recent transactions via Alchemy's getAssetTransfers API (JSON-RPC via POST)
            $endpoint = "https://arb-mainnet.g.alchemy.com/v2/" . $alchemy;
            $payload = [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'alchemy_getAssetTransfers',
                'params' => [[
                    'toAddress' => $address,
                    'category' => ['external','erc20','erc721','erc1155'],
                    'maxCount' => '0x3e8'
                ]]
            ];
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            $result = curl_exec($ch);
            curl_close($ch);
            $j = json_decode($result, true);
            if (isset($j['result']['transfers'])) {
                foreach ($j['result']['transfers'] as $t) {
                    // alchemy returns normalized transfers
                    upsert_deposit($pdo, $w['id'], $t['uniqueId'] ?? $t['hash'], $t['from'], $t['to'], $t['value'] ?? 0, $t['asset'] ?? null, 'arbitrum', $t['blockNum'] ?? null);
                }
            }
        }
    } catch (Exception $e) {
        // Log but continue
        error_log('check_deposits error for ' . $address . ': ' . $e->getMessage());
    }
}

function upsert_deposit($pdo, $wallet_id, $txid, $from, $to, $amount, $token, $chain, $block) {
    // Avoid duplicate txs using unique constraint on txid+chain
    try {
        $stmt = $pdo->prepare('INSERT INTO deposits (wallet_id, txid, `from`, `to`, amount, token_symbol, chain, block_number, processed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)');
        $stmt->execute([$wallet_id, $txid, $from, $to, $amount, $token, $chain, $block]);
        // Optionally: mark user balance or create ledger entry
    } catch (PDOException $ex) {
        // Duplicate entry or other error -> ignore
    }
}

echo "Done check_deposits at " . date('c') . "\n";
