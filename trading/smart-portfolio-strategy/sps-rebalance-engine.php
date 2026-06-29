<?php
// bitget/trading/smart-portfolio-strategy/sps-rebalance-engine.php

header('Content-Type: text/plain');
require_once '../../settings/api-settings.php'; 

$botFile = 'bot-list-data.txt';
$logFile = 'rebalance-log.txt'; 
$historyFile = 'rebalance-history.json'; // নতুন: হিস্টোরি সেভ করার ফাইল

$MIN_TRADE_USDT = 1.05; 

function logAction($msg) {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$time] $msg\n", FILE_APPEND);
    echo "[$time] $msg\n";
}

if (!file_exists($botFile)) {
    logAction("Error: bot-list-data.txt not found.");
    exit;
}

$botData = json_decode(file_get_contents($botFile), true);
if (!$botData) {
    logAction("Error: Empty or invalid bot data.");
    exit;
}

// হিস্টোরি ফাইল লোড করা
$historyData = [];
if (file_exists($historyFile)) {
    $historyData = json_decode(file_get_contents($historyFile), true) ?: [];
}

// ১. লাইভ প্রাইস আনা
$urlTickers = "https://api.bitget.com/api/v2/spot/market/tickers";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $urlTickers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$responseTickers = curl_exec($ch);
curl_close($ch);

$livePrices = [];
if ($responseTickers) {
    $tickersData = json_decode($responseTickers, true);
    if (isset($tickersData['data'])) {
        foreach ($tickersData['data'] as $t) {
            $livePrices[$t['symbol']] = floatval($t['lastPr']);
        }
    }
} else {
    logAction("Error: Failed to fetch live prices from Bitget.");
    exit;
}

$isAnyBotUpdated = false;
$isHistoryUpdated = false;

// ২. প্রতিটি রানিং বটের জন্য চেকিং শুরু
foreach ($botData as &$bot) {
    if ($bot['status'] !== 'Running') continue;

    $botId = $bot['bot_id'];
    $rebalanceThreshold = floatval($bot['rebalancing_percentage']);
    
    $totalCurrentValue = 0;
    $totalInitialInvested = 0;
    $coinsStats = [];

    // পোর্টফোলিওর বর্তমান ভ্যালু ক্যালকুলেশন
    foreach ($bot['coins_details'] as $coin) {
        $symbol = $coin['coin_name'] . 'USDT';
        $currentPrice = isset($livePrices[$symbol]) ? $livePrices[$symbol] : 0;
        
        if ($currentPrice <= 0) continue;

        $currentValue = $coin['amount'] * $currentPrice;
        $totalCurrentValue += $currentValue;
        $totalInitialInvested += $coin['initial_investment'];
        
        $coinsStats[] = [
            'name' => $coin['coin_name'],
            'amount' => $coin['amount'],
            'price' => $currentPrice,
            'currentValue' => $currentValue,
            'initialInvest' => $coin['initial_investment']
        ];
    }

    $needsRebalance = false;

    // ডেভিয়েশন চেক করা
    foreach ($coinsStats as &$stat) {
        $targetWeight = $stat['initialInvest'] / $totalInitialInvested;
        $currentWeight = $stat['currentValue'] / $totalCurrentValue;
        
        $deviationPct = abs($currentWeight - $targetWeight) * 100;

        if ($deviationPct >= $rebalanceThreshold) {
            $needsRebalance = true;
        }
        
        $targetValue = $totalCurrentValue * $targetWeight;
        $differenceUSDT = $targetValue - $stat['currentValue'];
        
        $stat['targetValue'] = $targetValue;
        $stat['diffUSDT'] = $differenceUSDT;
    }

    if ($needsRebalance) {
        $sells = [];
        $buys = [];
        
        foreach ($coinsStats as $stat) {
            if ($stat['diffUSDT'] <= -$MIN_TRADE_USDT) {
                $qtyToSell = abs($stat['diffUSDT']) / $stat['price'];
                $sells[] = ['coin' => $stat['name'], 'qty' => $qtyToSell, 'symbol' => $stat['name'].'USDT', 'price' => $stat['price']];
            } elseif ($stat['diffUSDT'] >= $MIN_TRADE_USDT) {
                $buys[] = ['coin' => $stat['name'], 'usdt' => $stat['diffUSDT'], 'symbol' => $stat['name'].'USDT', 'price' => $stat['price']];
            }
        }

        if (empty($sells) && empty($buys)) continue;

        logAction("Rebalance Executing for Bot: $botId");

        // হিস্টোরি রেকর্ড তৈরি করার জন্য ডেটা অ্যারে
        $currentRebalanceRecord = [
            'timestamp' => date('c'),
            'sells' => [],
            'buys' => []
        ];

        // --- Execute SELLS First ---
        foreach ($sells as $sell) {
            $endpoint = '/api/v2/spot/trade/place-order';
            $bodyArray = ["symbol" => $sell['symbol'], "side" => "sell", "orderType" => "market", "force" => "normal", "size" => (string)round($sell['qty'], 6)];
            $body = json_encode($bodyArray);
            
            $timestamp = (string)(round(microtime(true) * 1000));
            $message = $timestamp . 'POST' . $endpoint . $body;
            $signature = base64_encode(hash_hmac('sha256', $message, $secretKey, true));

            $headers = ["ACCESS-KEY: " . $apiKey, "ACCESS-SIGN: " . $signature, "ACCESS-TIMESTAMP: " . $timestamp, "ACCESS-PASSPHRASE: " . $passphrase, "Content-Type: application/json"];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $res = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (isset($res['code']) && $res['code'] === '00000') {
                logAction("SUCCESS SELL: {$sell['coin']} (Qty: {$sell['qty']})");
                foreach ($bot['coins_details'] as &$c) { if ($c['coin_name'] === $sell['coin']) { $c['amount'] -= $sell['qty']; break; } }
                
                // হিস্টোরিতে অ্যাড করা
                $usdtEarned = $sell['qty'] * $sell['price'];
                $currentRebalanceRecord['sells'][] = [
                    'coin' => $sell['coin'],
                    'amount' => $sell['qty'],
                    'usdt' => round($usdtEarned, 4),
                    'price' => $sell['price'],
                    'time' => date('c')
                ];
            } else {
                logAction("FAILED SELL: {$sell['coin']} - " . json_encode($res));
            }
        }
        
        sleep(1);

        // --- Execute BUYS ---
        foreach ($buys as $buy) {
            $endpoint = '/api/v2/spot/trade/place-order';
            $bodyArray = ["symbol" => $buy['symbol'], "side" => "buy", "orderType" => "market", "force" => "normal", "size" => (string)round($buy['usdt'], 4)];
            $body = json_encode($bodyArray);
            
            $timestamp = (string)(round(microtime(true) * 1000));
            $message = $timestamp . 'POST' . $endpoint . $body;
            $signature = base64_encode(hash_hmac('sha256', $message, $secretKey, true));

            $headers = ["ACCESS-KEY: " . $apiKey, "ACCESS-SIGN: " . $signature, "ACCESS-TIMESTAMP: " . $timestamp, "ACCESS-PASSPHRASE: " . $passphrase, "Content-Type: application/json"];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $res = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (isset($res['code']) && $res['code'] === '00000') {
                logAction("SUCCESS BUY: {$buy['coin']} (USDT: {$buy['usdt']})");
                $estimatedQty = $buy['usdt'] / $buy['price'];
                foreach ($bot['coins_details'] as &$c) { if ($c['coin_name'] === $buy['coin']) { $c['amount'] += $estimatedQty; break; } }
                
                // হিস্টোরিতে অ্যাড করা
                $currentRebalanceRecord['buys'][] = [
                    'coin' => $buy['coin'],
                    'amount' => round($estimatedQty, 6),
                    'usdt' => round($buy['usdt'], 4),
                    'price' => $buy['price'],
                    'time' => date('c')
                ];
            } else {
                logAction("FAILED BUY: {$buy['coin']} - " . json_encode($res));
            }
        }

        // যদি কোনো সফল ট্রেড হয়ে থাকে, তবেই হিস্টোরি সেভ হবে
        if (count($currentRebalanceRecord['sells']) > 0 || count($currentRebalanceRecord['buys']) > 0) {
            if (!isset($historyData[$botId])) {
                $historyData[$botId] = [];
            }
            
            // Rebalance ID তৈরি করা (যেমন: #Rebalance 1)
            $nextRebalanceNum = count($historyData[$botId]) + 1;
            $currentRebalanceRecord['rebalance_id'] = "#Rebalance " . $nextRebalanceNum;
            
            // বটের হিস্টোরি অ্যারেতে পুশ করা
            $historyData[$botId][] = $currentRebalanceRecord;
            $isHistoryUpdated = true;
            
            $bot['last_updated'] = date('c');
            $isAnyBotUpdated = true;
            logAction("Rebalance Complete for Bot: $botId. Saved as {$currentRebalanceRecord['rebalance_id']}");
        }
    }
}

// ৩. আপডেট ফাইলগুলোতে সেভ করা
if ($isAnyBotUpdated) {
    file_put_contents($botFile, json_encode($botData, JSON_PRETTY_PRINT));
}
if ($isHistoryUpdated) {
    file_put_contents($historyFile, json_encode($historyData, JSON_PRETTY_PRINT));
}

?>
