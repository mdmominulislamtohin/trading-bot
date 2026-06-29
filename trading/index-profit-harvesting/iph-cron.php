<?php
// bitget/trading/index-profit-harvesting/iph-cron.php
set_time_limit(0);
header('Content-Type: text/plain');
require_once '../../settings/api-settings.php'; 

$botFile = 'iph-bot-data.txt';
$logFile = 'iph-cron-log.txt'; 
$historyFile = 'iph-history.json'; 

function logAction($msg) {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$time] $msg\n", FILE_APPEND | LOCK_EX); 
    echo "[$time] $msg\n";
}

function safeTruncate($val, $precision = 4) {
    $multiplier = pow(10, $precision);
    return floor($val * $multiplier) / $multiplier;
}

if (!file_exists($botFile)) {
    logAction("Error: iph-bot-data.txt not found.");
    exit;
}

$botData = json_decode(file_get_contents($botFile), true);
if (!$botData || !is_array($botData)) {
    logAction("Error: Empty or invalid bot data.");
    exit;
}

$historyData = [];
if (file_exists($historyFile)) {
    $historyData = json_decode(file_get_contents($historyFile), true) ?: [];
}

// 1. Fetch Live Prices
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
    logAction("Error: Failed to fetch live prices.");
    exit;
}

// 2. Fetch Live Rules (Min Trade & Precision)
$urlSymbols = "https://api.bitget.com/api/v2/spot/public/symbols";
$chSym = curl_init();
curl_setopt($chSym, CURLOPT_URL, $urlSymbols);
curl_setopt($chSym, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chSym, CURLOPT_SSL_VERIFYPEER, false);
$responseSymbols = curl_exec($chSym);
curl_close($chSym);

$symbolRules = [];
if ($responseSymbols) {
    $symbolsData = json_decode($responseSymbols, true);
    if (isset($symbolsData['code']) && $symbolsData['code'] === '00000' && isset($symbolsData['data'])) {
        foreach ($symbolsData['data'] as $s) {
            $sp = isset($s['sizePlace']) ? intval($s['sizePlace']) : (isset($s['quantityScale']) ? intval($s['quantityScale']) : 4);
            $minT = isset($s['minTradeUSDT']) ? floatval($s['minTradeUSDT']) : 5.0; // Bitget Default is usually 5
            $symbolRules[$s['symbol']] = ['precision' => $sp, 'min_trade' => $minT];
        }
    }
}

// SMART PROPORTIONAL ALLOCATOR WITH API LIMIT FILTER
function calculateAllocationsWithFilter($weightsMap, $totalTargetUsdt, $symbolRules) {
    $allocated = [];
    $activeCoins = array_keys($weightsMap);
    $remainingTarget = $totalTargetUsdt;

    while (!empty($activeCoins) && $remainingTarget > 0) {
        $totalWeight = 0;
        foreach ($activeCoins as $coin) { $totalWeight += $weightsMap[$coin]; }
        if ($totalWeight <= 0) break;

        $toRemove = [];
        foreach ($activeCoins as $coin) {
            $symbol = $coin . 'USDT';
            $alloc = ($weightsMap[$coin] / $totalWeight) * $remainingTarget;
            $minTrade = isset($symbolRules[$symbol]) ? $symbolRules[$symbol]['min_trade'] : 5.0;
            
            if ($alloc < $minTrade) {
                $toRemove[] = $coin;
            }
        }

        if (empty($toRemove)) {
            // All passed the limit filter
            foreach ($activeCoins as $coin) {
                $allocated[$coin] = ($weightsMap[$coin] / $totalWeight) * $remainingTarget;
            }
            break;
        } else {
            // Remove the ones that failed and loop again to re-distribute
            foreach ($toRemove as $c) {
                $index = array_search($c, $activeCoins);
                if ($index !== false) { unset($activeCoins[$index]); }
            }
            $activeCoins = array_values($activeCoins);
        }
    }
    return $allocated;
}

$isAnyBotUpdated = false;
$isHistoryUpdated = false;

// 3. Process Each Bot
foreach ($botData as &$bot) {
    if ($bot['status'] !== 'Running') continue;
    
    $botId = $bot['bot_id'];
    $baseValue = floatval($bot['base_target_value']);
    $tpTargetVal = $baseValue * (1 + ($bot['take_profit_pct'] / 100));
    $dropTargetVal = $baseValue * (1 - ($bot['drop_buy_pct'] / 100));
    
    $totalCurrentVal = 0;
    
    // Assess current portfolio state
    foreach ($bot['coins_details'] as &$coin) {
        $symbol = $coin['coin_name'] . 'USDT';
        $currentPrice = isset($livePrices[$symbol]) ? $livePrices[$symbol] : 0;
        $coin['temp_live_price'] = $currentPrice;
        $coin['temp_current_val'] = $coin['amount'] * $currentPrice;
        $totalCurrentVal += $coin['temp_current_val'];
    }
    unset($coin);

    // ==========================================
    // CONDITION 1: HARVEST PROFIT (Market Up)
    // ==========================================
    if ($totalCurrentVal >= $tpTargetVal) {
        $overflowProfit = $totalCurrentVal - $baseValue; // Uncapped Profit Logic
        
        // Reverse Buffer: Add 0.3% to cover 0.1% spot fee and 0.2% slippage safety
        $sellTargetUsdt = $overflowProfit * 1.003; 
        
        // Find Winners and their weight (gain)
        $gainWeights = [];
        foreach ($bot['coins_details'] as $coin) {
            $gain = $coin['temp_current_val'] - $coin['initial_investment'];
            if ($gain > 0) {
                $gainWeights[$coin['coin_name']] = $gain;
            }
        }
        
        // Filter and allocate proportionally
        $allocations = calculateAllocationsWithFilter($gainWeights, $sellTargetUsdt, $symbolRules);
        
        if (!empty($allocations)) {
            logAction("Harvest Triggered for Bot: $botId. Overflow: $overflowProfit");
            
            $cycleRecord = [
                'trade_id' => 'Harvesting',
                'timestamp' => date('c'),
                'event_type' => 'harvest',
                'orders' => []
            ];
            
            $cycleTotalNetReceived = 0;
            
            foreach ($allocations as $coinName => $targetSellUsdt) {
                // Find coin reference
                $coinRef = null;
                foreach ($bot['coins_details'] as &$c) { if ($c['coin_name'] === $coinName) { $coinRef = &$c; break; } }
                
                $symbol = $coinName . 'USDT';
                $precision = isset($symbolRules[$symbol]) ? $symbolRules[$symbol]['precision'] : 2;
                
                $rawQty = $targetSellUsdt / $coinRef['temp_live_price'];
                $sellQty = safeTruncate($rawQty, $precision);
                
                if ($sellQty > $coinRef['amount']) $sellQty = safeTruncate($coinRef['amount'], $precision);
                if ($sellQty <= 0) continue;
                
                $sellQtyStr = number_format($sellQty, $precision, '.', '');
                
                // --- Execute SELL API ---
                $endpoint = '/api/v2/spot/trade/place-order';
                $bodyStr = json_encode(["symbol" => $symbol, "side" => "sell", "orderType" => "market", "force" => "normal", "size" => $sellQtyStr]);
                $timestamp = (string)(round(microtime(true) * 1000));
                $signature = base64_encode(hash_hmac('sha256', $timestamp . 'POST' . $endpoint . $bodyStr, $secretKey, true));
                $headers = ["ACCESS-KEY: " . $apiKey, "ACCESS-SIGN: " . $signature, "ACCESS-TIMESTAMP: " . $timestamp, "ACCESS-PASSPHRASE: " . $passphrase, "Content-Type: application/json"];

                $ch = curl_init($baseUrl . $endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyStr); curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $res = json_decode(curl_exec($ch), true); curl_close($ch);

                if (isset($res['code']) && $res['code'] === '00000') {
                    $orderId = $res['data']['orderId'];
                    sleep(1);
                    
                    // Fetch real execution data
                    $infoCh = curl_init($baseUrl . '/api/v2/spot/trade/orderInfo?orderId=' . $orderId);
                    $infoTs = (string)(round(microtime(true) * 1000));
                    $infoSig = base64_encode(hash_hmac('sha256', $infoTs . 'GET/api/v2/spot/trade/orderInfo?orderId=' . $orderId, $secretKey, true));
                    curl_setopt($infoCh, CURLOPT_RETURNTRANSFER, true); curl_setopt($infoCh, CURLOPT_HTTPHEADER, ["ACCESS-KEY: " . $apiKey, "ACCESS-SIGN: " . $infoSig, "ACCESS-TIMESTAMP: " . $infoTs, "ACCESS-PASSPHRASE: " . $passphrase, "Content-Type: application/json"]); curl_setopt($infoCh, CURLOPT_SSL_VERIFYPEER, false);
                    $infoRes = json_decode(curl_exec($infoCh), true); curl_close($infoCh);
                    
                    $actQty = $sellQty; $grossUsdt = $targetSellUsdt; $avgPrice = $coinRef['temp_live_price']; $fee = 0; $feeCoin = 'USDT';
                    
                    if (isset($infoRes['code']) && $infoRes['code'] === '00000') {
                        $det = isset($infoRes['data'][0]) ? $infoRes['data'][0] : $infoRes['data'];
                        if (isset($det['baseVolume']) && floatval($det['baseVolume']) > 0) $actQty = floatval($det['baseVolume']);
                        if (isset($det['quoteVolume']) && floatval($det['quoteVolume']) > 0) $grossUsdt = floatval($det['quoteVolume']);
                        if (isset($det['priceAvg']) && floatval($det['priceAvg']) > 0) $avgPrice = floatval($det['priceAvg']);
                        if (isset($det['feeDetail']) && !empty($det['feeDetail'])) {
                            $fee = floatval($det['feeDetail'][0]['totalFee']);
                            $feeCoin = $det['feeDetail'][0]['feeCoin'];
                        }
                    }
                    
                    // Estimate net received if fee not explicitly in USDT
                    $netReceivedUsdt = ($feeCoin === 'USDT') ? ($grossUsdt - $fee) : ($grossUsdt * 0.999);
                    
                    // Update Portfolio
                    $coinRef['amount'] -= $actQty;
                    $coinRef['initial_investment'] -= $netReceivedUsdt; // Maintain equilibrium
                    
                    $cycleTotalNetReceived += $netReceivedUsdt;
                    
                    $cycleRecord['orders'][] = [
                        'direction' => 'Sell', 'coin' => $coinName, 'time' => date('Y-m-d H:i:s'),
                        'avg_price' => $avgPrice, 'amount_usdt' => $grossUsdt, 'volume_coin' => $actQty, 'fee' => $fee, 'fee_coin' => $feeCoin
                    ];
                }
                unset($coinRef);
            }
            
            // Add actual received cash to Reserve
            if ($cycleTotalNetReceived > 0) {
                if (!isset($bot['reserve_usdt'])) $bot['reserve_usdt'] = 0;
                $bot['reserve_usdt'] += $cycleTotalNetReceived;
                
                // --- Cycle Count Logic ---
                if (!isset($bot['cycle_count'])) $bot['cycle_count'] = 0;
                $bot['cycle_count'] += 1;
                // -------------------------
                
                $bot['last_updated'] = date('c');
                $isAnyBotUpdated = true;
                
                if (!isset($historyData[$botId])) $historyData[$botId] = [];
                $historyData[$botId][] = $cycleRecord;
                $isHistoryUpdated = true;
                
                logAction("Harvest completed for $botId. Added $" . number_format($cycleTotalNetReceived, 2) . " to reserve. Cycles: {$bot['cycle_count']}");
            }
        } else {
            logAction("Harvest triggered for $botId but trade amounts were below API limits. Skipping.");
        }
    }
    
    // ==========================================
    // CONDITION 2: DIP BUY (Market Down)
    // ==========================================
    else if ($totalCurrentVal <= $dropTargetVal) {
        $reserve = isset($bot['reserve_usdt']) ? floatval($bot['reserve_usdt']) : 0;
        
        if ($reserve > 1.0) { // Safety check to prevent ghost runs
            $shortfall = $baseValue - $totalCurrentVal;
            $buyTargetUsdt = min($reserve, $shortfall);
            
            // Find Losers and their weight (loss)
            $lossWeights = [];
            foreach ($bot['coins_details'] as $coin) {
                $loss = $coin['initial_investment'] - $coin['temp_current_val'];
                if ($loss > 0) {
                    $lossWeights[$coin['coin_name']] = $loss;
                }
            }
            
            // Filter and allocate proportionally
            $allocations = calculateAllocationsWithFilter($lossWeights, $buyTargetUsdt, $symbolRules);
            
            if (!empty($allocations)) {
                logAction("DCA Dip Buy Triggered for Bot: $botId. Using Reserve: $buyTargetUsdt");
                
                $cycleRecord = [
                    'trade_id' => 'Dip Buy DCA',
                    'timestamp' => date('c'),
                    'event_type' => 'dip_buy',
                    'orders' => []
                ];
                
                $cycleTotalSpent = 0;
                
                foreach ($allocations as $coinName => $targetBuyUsdt) {
                    $coinRef = null;
                    foreach ($bot['coins_details'] as &$c) { if ($c['coin_name'] === $coinName) { $coinRef = &$c; break; } }
                    
                    $symbol = $coinName . 'USDT';
                    $safeBuyUsdt = safeTruncate($targetBuyUsdt, 2); // USDT max 2 decimals
                    $buyUsdtStr = number_format($safeBuyUsdt, 2, '.', '');
                    
                    // --- Execute BUY API ---
                    $endpoint = '/api/v2/spot/trade/place-order';
                    $bodyStr = json_encode(["symbol" => $symbol, "side" => "buy", "orderType" => "market", "force" => "normal", "size" => $buyUsdtStr]);
                    $timestamp = (string)(round(microtime(true) * 1000));
                    $signature = base64_encode(hash_hmac('sha256', $timestamp . 'POST' . $endpoint . $bodyStr, $secretKey, true));
                    $headers = ["ACCESS-KEY: " . $apiKey, "ACCESS-SIGN: " . $signature, "ACCESS-TIMESTAMP: " . $timestamp, "ACCESS-PASSPHRASE: " . $passphrase, "Content-Type: application/json"];

                    $ch = curl_init($baseUrl . $endpoint);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyStr); curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $res = json_decode(curl_exec($ch), true); curl_close($ch);

                    if (isset($res['code']) && $res['code'] === '00000') {
                        $orderId = $res['data']['orderId'];
                        sleep(1);
                        
                        $infoCh = curl_init($baseUrl . '/api/v2/spot/trade/orderInfo?orderId=' . $orderId);
                        $infoTs = (string)(round(microtime(true) * 1000));
                        $infoSig = base64_encode(hash_hmac('sha256', $infoTs . 'GET/api/v2/spot/trade/orderInfo?orderId=' . $orderId, $secretKey, true));
                        curl_setopt($infoCh, CURLOPT_RETURNTRANSFER, true); curl_setopt($infoCh, CURLOPT_HTTPHEADER, ["ACCESS-KEY: " . $apiKey, "ACCESS-SIGN: " . $infoSig, "ACCESS-TIMESTAMP: " . $infoTs, "ACCESS-PASSPHRASE: " . $passphrase, "Content-Type: application/json"]); curl_setopt($infoCh, CURLOPT_SSL_VERIFYPEER, false);
                        $infoRes = json_decode(curl_exec($infoCh), true); curl_close($infoCh);
                        
                        $actQty = ($safeBuyUsdt / $coinRef['temp_live_price']); $actSpentUsdt = $safeBuyUsdt; $avgPrice = $coinRef['temp_live_price']; $fee = 0; $feeCoin = $coinName;
                        
                        if (isset($infoRes['code']) && $infoRes['code'] === '00000') {
                            $det = isset($infoRes['data'][0]) ? $infoRes['data'][0] : $infoRes['data'];
                            if (isset($det['baseVolume']) && floatval($det['baseVolume']) > 0) $actQty = floatval($det['baseVolume']);
                            if (isset($det['quoteVolume']) && floatval($det['quoteVolume']) > 0) $actSpentUsdt = floatval($det['quoteVolume']);
                            if (isset($det['priceAvg']) && floatval($det['priceAvg']) > 0) $avgPrice = floatval($det['priceAvg']);
                            if (isset($det['feeDetail']) && !empty($det['feeDetail'])) {
                                $fee = floatval($det['feeDetail'][0]['totalFee']);
                                $feeCoin = $det['feeDetail'][0]['feeCoin'];
                            }
                        }
                        
                        // Update Portfolio
                        $coinRef['amount'] += $actQty;
                        $coinRef['initial_investment'] += $actSpentUsdt;
                        if ($coinRef['amount'] > 0) { $coinRef['buy_price'] = $coinRef['initial_investment'] / $coinRef['amount']; }
                        
                        $cycleTotalSpent += $actSpentUsdt;
                        
                        $cycleRecord['orders'][] = [
                            'direction' => 'Buy', 'coin' => $coinName, 'time' => date('Y-m-d H:i:s'),
                            'avg_price' => $avgPrice, 'amount_usdt' => $actSpentUsdt, 'volume_coin' => $actQty, 'fee' => $fee, 'fee_coin' => $feeCoin
                        ];
                    }
                    unset($coinRef);
                }
                
                if ($cycleTotalSpent > 0) {
                    $bot['reserve_usdt'] -= $cycleTotalSpent;
                    if ($bot['reserve_usdt'] < 0) $bot['reserve_usdt'] = 0;
                    
                    $bot['last_updated'] = date('c');
                    $isAnyBotUpdated = true;
                    
                    if (!isset($historyData[$botId])) $historyData[$botId] = [];
                    $historyData[$botId][] = $cycleRecord;
                    $isHistoryUpdated = true;
                    
                    logAction("Dip Buy completed for $botId. Spent $" . number_format($cycleTotalSpent, 2) . " from reserve.");
                }
            } else {
                logAction("Dip buy triggered for $botId but trade limits blocked it. Holding.");
            }
        } else {
            // Drop target hit, but no reserve USDT available
            // In safe mode, we just do nothing.
        }
    }
}

// Cleanup Temp vars and Save
if ($isAnyBotUpdated) {
    foreach ($botData as &$bot) {
        if (isset($bot['coins_details'])) {
            foreach ($bot['coins_details'] as &$c) {
                unset($c['temp_live_price']);
                unset($c['temp_current_val']);
            }
        }
    }
    file_put_contents($botFile, json_encode($botData, JSON_PRETTY_PRINT), LOCK_EX);
}

if ($isHistoryUpdated) {
    file_put_contents($historyFile, json_encode($historyData, JSON_PRETTY_PRINT), LOCK_EX);
}

echo "IPH Engine check completed.\n";
?>
