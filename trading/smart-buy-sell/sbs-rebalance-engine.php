<?php
// bitget/trading/smart-buy-sell/sbs-rebalance-engine.php
set_time_limit(0);
header('Content-Type: text/plain');
require_once '../../settings/api-settings.php'; 

$botFile = 'sbs-bot-data.txt';
$logFile = 'sbs-rebalance-log.txt'; 
$historyFile = 'sbs-history.json'; 

function logAction($msg) {
    global $logFile;
    $time = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$time] $msg\n", FILE_APPEND | LOCK_EX); 
    echo "[$time] $msg\n";
}

// সেফ ট্রাংকেশন ফাংশন
function safeTruncate($val, $precision = 4) {
    $multiplier = pow(10, $precision);
    return floor($val * $multiplier) / $multiplier;
}

if (!file_exists($botFile)) {
    logAction("Error: sbs-bot-data.txt not found.");
    exit;
}

$botData = json_decode(file_get_contents($botFile), true);
if (!$botData) {
    logAction("Error: Empty or invalid bot data.");
    exit;
}

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

// ২. এক্সচেঞ্জ থেকে লাইভ ডেসিমাল (Precision) এবং লিমিট আনা 
$urlSymbols = "https://api.bitget.com/api/v2/spot/public/symbols";
$chSym = curl_init();
curl_setopt($chSym, CURLOPT_URL, $urlSymbols);
curl_setopt($chSym, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chSym, CURLOPT_SSL_VERIFYPEER, false);
$responseSymbols = curl_exec($chSym);
curl_close($chSym);

$symbolPrecisions = [];
$quotePrecisions = [];
$minTradeUsdt = [];
$minTradeQty = [];
$isSymbolApiSuccess = false;

if ($responseSymbols) {
    $symbolsData = json_decode($responseSymbols, true);
    if (isset($symbolsData['code']) && $symbolsData['code'] === '00000' && isset($symbolsData['data'])) {
        $isSymbolApiSuccess = true;
        foreach ($symbolsData['data'] as $s) {
            // 💡 Quantity Precision Fix (Coin Size এর জন্য)
            if (isset($s['quantityPrecision'])) {
                $symbolPrecisions[$s['symbol']] = intval($s['quantityPrecision']);
            } elseif (isset($s['sizePlace'])) {
                $symbolPrecisions[$s['symbol']] = intval($s['sizePlace']);
            } elseif (isset($s['quantityScale'])) {
                $symbolPrecisions[$s['symbol']] = intval($s['quantityScale']);
            }
            
            // 💡 Quote Precision Fix (USDT এর জন্য)
            if (isset($s['quotePrecision'])) {
                $quotePrecisions[$s['symbol']] = intval($s['quotePrecision']);
            } elseif (isset($s['pricePlace'])) {
                $quotePrecisions[$s['symbol']] = intval($s['pricePlace']);
            }
            
            if (isset($s['minTradeUSDT'])) {
                $minTradeUsdt[$s['symbol']] = floatval($s['minTradeUSDT']);
            }
            if (isset($s['minTradeAmount'])) {
                $minTradeQty[$s['symbol']] = floatval($s['minTradeAmount']);
            }
        }
    }
}

if (!$isSymbolApiSuccess) {
    logAction("Error: Failed to fetch live precisions from Bitget API. Safety abort.");
    exit;
}

// ৩. লাইভ স্পট ব্যালেন্স আনা
$endpointAssets = '/api/v2/spot/account/assets';
$timestampAssets = (string)(round(microtime(true) * 1000));
$signatureAssets = base64_encode(hash_hmac('sha256', $timestampAssets . 'GET' . $endpointAssets, $secretKey, true));
$headersAssets = [
    "ACCESS-KEY: " . $apiKey,
    "ACCESS-SIGN: " . $signatureAssets,
    "ACCESS-TIMESTAMP: " . $timestampAssets,
    "ACCESS-PASSPHRASE: " . $passphrase,
    "Content-Type: application/json"
];

$chAssets = curl_init();
curl_setopt($chAssets, CURLOPT_URL, $baseUrl . $endpointAssets);
curl_setopt($chAssets, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chAssets, CURLOPT_HTTPHEADER, $headersAssets);
curl_setopt($chAssets, CURLOPT_SSL_VERIFYPEER, false);
$resAssets = json_decode(curl_exec($chAssets), true);
curl_close($chAssets);

$actualBalances = [];
$balanceFetchSuccess = false;

if (isset($resAssets['code']) && $resAssets['code'] === '00000' && isset($resAssets['data'])) {
    $balanceFetchSuccess = true;
    foreach ($resAssets['data'] as $asset) {
        $actualBalances[$asset['coin']] = floatval($asset['available']);
    }
} else {
    logAction("Warning: Failed to fetch live spot balances. Safety check skipped for this run.");
}

$isAnyBotUpdated = false;
$isHistoryUpdated = false;

// ৪. প্রতিটি রানিং বটের জন্য চেকিং শুরু
foreach ($botData as &$bot) {
    if ($bot['status'] !== 'Running') continue;
    
    // Missing Coin Safety Check
    if ($balanceFetchSuccess) {
        $isBalanceMissing = false;
        $missingCoin = '';

        foreach ($bot['coins_details'] as $coin) {
            $coinName = $coin['coin_name'];
            $recordedAmount = floatval($coin['amount']);
            $actualAvail = isset($actualBalances[$coinName]) ? $actualBalances[$coinName] : 0;

            if ($actualAvail < ($recordedAmount * 0.99)) {
                $isBalanceMissing = true;
                $missingCoin = $coinName;
                break;
            }
        }

        if ($isBalanceMissing) {
            $bot['status'] = 'Paused';
            $bot['last_updated'] = date('c');
            $isAnyBotUpdated = true;
            logAction("SAFETY TRIGGER: Bot {$bot['bot_id']} Paused! Missing balance for {$missingCoin}.");
            continue; 
        }
    }
    
    $botId = $bot['bot_id'];
    $gapTrigger = floatval($bot['gap_trigger']);
    
    $maxVal = -1;
    $minVal = 999999999;
    $maxCoinIndex = -1;
    $minCoinIndex = -1;

    foreach ($bot['coins_details'] as $index => &$coin) {
        $symbol = $coin['coin_name'] . 'USDT';
        $currentPrice = isset($livePrices[$symbol]) ? $livePrices[$symbol] : 0;
        
        if ($currentPrice <= 0) continue;

        $currentValue = $coin['amount'] * $currentPrice;
        $coin['temp_live_price'] = $currentPrice; 
        $coin['temp_current_val'] = $currentValue;
        
        if ($currentValue > $maxVal) {
            $maxVal = $currentValue;
            $maxCoinIndex = $index;
        }
        if ($currentValue < $minVal) {
            $minVal = $currentValue;
            $minCoinIndex = $index;
        }
    }

    if ($maxCoinIndex === -1 || $minCoinIndex === -1 || $maxCoinIndex === $minCoinIndex) continue;

    $currentGap = $maxVal - $minVal;

    if ($currentGap >= $gapTrigger) {
        $tradeUsdt = $currentGap / 2;

        logAction("SBS Triggered for Bot: $botId. Gap: $currentGap >= Target: $gapTrigger. Raw Trade Amount: $tradeUsdt");

        $maxCoin = &$bot['coins_details'][$maxCoinIndex];
        $minCoin = &$bot['coins_details'][$minCoinIndex];

        $sellSymbol = $maxCoin['coin_name'] . 'USDT';
        $buySymbol = $minCoin['coin_name'] . 'USDT';
        
        $sellPrecision = isset($symbolPrecisions[$sellSymbol]) ? $symbolPrecisions[$sellSymbol] : 2;
        
        // API থেকে লিমিট নেওয়া, না পেলে ফলব্যাক হিসেবে ১ USDT ধরবে
        $requiredMinUsdt = isset($minTradeUsdt[$sellSymbol]) ? $minTradeUsdt[$sellSymbol] : 1.0;
        $requiredMinQty = isset($minTradeQty[$sellSymbol]) ? $minTradeQty[$sellSymbol] : 0.0001;
        
        $safeMinUsdt = max($requiredMinUsdt, 1.0) * 1.05; 
        
        if ($tradeUsdt < $safeMinUsdt) {
            $tradeUsdt = $safeMinUsdt;
            logAction("Auto-Adjustment: Trade amount boosted to $tradeUsdt USDT to meet exchange limits.");
        }

        $rawSellQty = $tradeUsdt / $maxCoin['temp_live_price'];
        
        $safeMinQty = $requiredMinQty * 1.05;
        if ($rawSellQty < $safeMinQty) {
            $rawSellQty = $safeMinQty;
        }

        $sellQty = safeTruncate($rawSellQty, $sellPrecision); 

        if ($maxCoin['amount'] < $sellQty) {
            $sellQty = safeTruncate($maxCoin['amount'], $sellPrecision); 
            logAction("Warning: Max balance reached. Adjusted sell quantity to available: $sellQty");
        }

        $currentTradeValue = $sellQty * $maxCoin['temp_live_price'];

        if ($sellQty <= 0) {
            logAction("Skipping {$maxCoin['coin_name']}: Truncated quantity is 0.");
            continue;
        }

        if ($sellQty < $requiredMinQty || $currentTradeValue < $requiredMinUsdt) {
            logAction("Skipping {$maxCoin['coin_name']}: API Requires Min Qty: {$requiredMinQty}, Min USDT: {$requiredMinUsdt}. Current Trade: {$sellQty} ({$currentTradeValue} USDT).");
            continue;
        }

        $sellQtyStr = number_format($sellQty, $sellPrecision, '.', '');
        $tradeRecord = ['timestamp' => date('c'), 'sell' => null, 'buy' => null];

        // --- Execute SELL Order First (Top Coin) ---
        $endpoint = '/api/v2/spot/trade/place-order';
        $bodyArray = ["symbol" => $sellSymbol, "side" => "sell", "orderType" => "market", "force" => "normal", "size" => $sellQtyStr];
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
        $sellRes = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($sellRes['code']) && $sellRes['code'] === '00000') {
            $sellOrderId = $sellRes['data']['orderId'];
            logAction("Placed SELL Order: {$maxCoin['coin_name']} Qty: $sellQtyStr (OrderId: $sellOrderId)");
            
            sleep(2); 

            $infoEndpoint = '/api/v2/spot/trade/orderInfo?orderId=' . $sellOrderId;
            $infoTimestamp = (string)(round(microtime(true) * 1000));
            $infoSignature = base64_encode(hash_hmac('sha256', $infoTimestamp . 'GET' . $infoEndpoint, $secretKey, true));
            $infoHeaders = ["ACCESS-KEY: " . $apiKey, "ACCESS-SIGN: " . $infoSignature, "ACCESS-TIMESTAMP: " . $infoTimestamp, "ACCESS-PASSPHRASE: " . $passphrase, "Content-Type: application/json"];
            
            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, $baseUrl . $infoEndpoint);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, $infoHeaders);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            $infoData = json_decode(curl_exec($ch2), true);
            curl_close($ch2);

            // 💡 সম্পূর্ণ ডিটেইলস এক্সট্রাক্ট করা (Sell)
            $actualSoldQty = $sellQty;
            $actualReceivedUsdt = $tradeUsdt;
            $sellAvgPrice = $maxCoin['temp_live_price'];
            $sellTime = date('Y-m-d H:i:s');
            
            $sellFee = 0.00;
            $sellFeeCoin = "USDT";
            
            if (isset($infoData['code']) && $infoData['code'] === '00000') {
                $orderDetail = isset($infoData['data'][0]) ? $infoData['data'][0] : $infoData['data'];
                if (isset($orderDetail['baseVolume']) && floatval($orderDetail['baseVolume']) > 0) $actualSoldQty = floatval($orderDetail['baseVolume']);
                if (isset($orderDetail['quoteVolume']) && floatval($orderDetail['quoteVolume']) > 0) $actualReceivedUsdt = floatval($orderDetail['quoteVolume']);
                if (isset($orderDetail['priceAvg']) && floatval($orderDetail['priceAvg']) > 0) $sellAvgPrice = floatval($orderDetail['priceAvg']);
                if (isset($orderDetail['uTime'])) $sellTime = date('Y-m-d H:i:s', intval($orderDetail['uTime']) / 1000);
                
                // 💡 UPDATED: Robust Fee Extraction (SELL)
                if (isset($orderDetail['feeDetail']) && is_array($orderDetail['feeDetail'])) {
                    foreach ($orderDetail['feeDetail'] as $feeItem) {
                        $fVal = floatval($feeItem['totalFee'] ?? $feeItem['fee'] ?? 0);
                        if ($fVal > 0) {
                            $sellFee += $fVal;
                            $sellFeeCoin = $feeItem['feeCoin'] ?? $sellFeeCoin;
                        }
                    }
                } else {
                    if (isset($orderDetail['quoteFee']) && floatval($orderDetail['quoteFee']) > 0) {
                        $sellFee = floatval($orderDetail['quoteFee']);
                    } elseif (isset($orderDetail['fee'])) {
                        $sellFee = floatval($orderDetail['fee']);
                    }
                }
            }

            $maxCoin['amount'] -= $actualSoldQty;
            $maxCoin['initial_investment'] -= $actualReceivedUsdt; 

            $tradeRecord['sell'] = [
                'direction' => 'Sell',
                'coin' => $maxCoin['coin_name'],
                'time' => $sellTime,
                'avg_price' => $sellAvgPrice,
                'amount_usdt' => $actualReceivedUsdt,
                'volume_coin' => $actualSoldQty,
                'fee' => number_format($sellFee, 6, '.', ''),
                'fee_coin' => $sellFeeCoin
            ];

            // 💡 UPDATED: Fee Deduction Logic for Next Trade
            $usableUsdt = $actualReceivedUsdt;
            if ($sellFeeCoin === 'USDT' || $sellFeeCoin === 'usdt') {
                $usableUsdt -= $sellFee;
            }

            // 💡 UPDATED: Dynamic Precision for BUY Order
            $buyPrecision = isset($quotePrecisions[$buySymbol]) ? $quotePrecisions[$buySymbol] : 2;
            $safeBuyUsdt = safeTruncate($usableUsdt, $buyPrecision); 
            $buyUsdtStr = number_format($safeBuyUsdt, $buyPrecision, '.', ''); 
            
            // 💡 UPDATED: Min USDT Check before BUY
            $buyRequiredMinUsdt = isset($minTradeUsdt[$buySymbol]) ? $minTradeUsdt[$buySymbol] : 1.0;
            
            if ($safeBuyUsdt < $buyRequiredMinUsdt) {
                logAction("Warning: Skipped BUY for {$minCoin['coin_name']}. Usable USDT ($safeBuyUsdt) is less than API min limit ($buyRequiredMinUsdt USDT). Sell was successful.");
                
                // শুধুমাত্র Sell রেকর্ড সেভ করা হচ্ছে
                if (!isset($historyData[$botId])) { $historyData[$botId] = []; }
                $nextTradeNum = count($historyData[$botId]) + 1;
                $tradeRecord['trade_id'] = "#Trade " . $nextTradeNum;
                $historyData[$botId][] = $tradeRecord;
                $isHistoryUpdated = true;
                continue;
            }

            // --- Execute BUY Order Next (Bottom Coin) ---
            $bodyArray = ["symbol" => $buySymbol, "side" => "buy", "orderType" => "market", "force" => "normal", "size" => $buyUsdtStr];
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
            $buyRes = json_decode(curl_exec($ch), true);
            curl_close($ch);

            if (isset($buyRes['code']) && $buyRes['code'] === '00000') {
                $buyOrderId = $buyRes['data']['orderId'];
                logAction("Placed BUY Order: {$minCoin['coin_name']} Amount: $buyUsdtStr USDT (OrderId: $buyOrderId)");
                
                sleep(2); 
                
                $infoEndpoint = '/api/v2/spot/trade/orderInfo?orderId=' . $buyOrderId;
                $infoTimestamp = (string)(round(microtime(true) * 1000));
                $infoSignature = base64_encode(hash_hmac('sha256', $infoTimestamp . 'GET' . $infoEndpoint, $secretKey, true));
                $infoHeaders = ["ACCESS-KEY: " . $apiKey, "ACCESS-SIGN: " . $infoSignature, "ACCESS-TIMESTAMP: " . $infoTimestamp, "ACCESS-PASSPHRASE: " . $passphrase, "Content-Type: application/json"];
                
                $ch3 = curl_init();
                curl_setopt($ch3, CURLOPT_URL, $baseUrl . $infoEndpoint);
                curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch3, CURLOPT_HTTPHEADER, $infoHeaders);
                curl_setopt($ch3, CURLOPT_SSL_VERIFYPEER, false);
                $buyInfoData = json_decode(curl_exec($ch3), true);
                curl_close($ch3);

                $actualBoughtQty = ($safeBuyUsdt / $minCoin['temp_live_price']);
                $actualSpentUsdt = $safeBuyUsdt;
                $buyAvgPrice = $minCoin['temp_live_price'];
                $buyTime = date('Y-m-d H:i:s');
                
                $buyFee = 0.00;
                $buyFeeCoin = $minCoin['coin_name'];

                if (isset($buyInfoData['code']) && $buyInfoData['code'] === '00000') {
                    $orderDetail = isset($buyInfoData['data'][0]) ? $buyInfoData['data'][0] : $buyInfoData['data'];
                    if (isset($orderDetail['baseVolume']) && floatval($orderDetail['baseVolume']) > 0) $actualBoughtQty = floatval($orderDetail['baseVolume']);
                    if (isset($orderDetail['quoteVolume']) && floatval($orderDetail['quoteVolume']) > 0) $actualSpentUsdt = floatval($orderDetail['quoteVolume']);
                    if (isset($orderDetail['priceAvg']) && floatval($orderDetail['priceAvg']) > 0) $buyAvgPrice = floatval($orderDetail['priceAvg']);
                    if (isset($orderDetail['uTime'])) $buyTime = date('Y-m-d H:i:s', intval($orderDetail['uTime']) / 1000);
                    
                    // 💡 UPDATED: Robust Fee Extraction (BUY)
                    if (isset($orderDetail['feeDetail']) && is_array($orderDetail['feeDetail'])) {
                        foreach ($orderDetail['feeDetail'] as $feeItem) {
                            $fVal = floatval($feeItem['totalFee'] ?? $feeItem['fee'] ?? 0);
                            if ($fVal > 0) {
                                $buyFee += $fVal;
                                $buyFeeCoin = $feeItem['feeCoin'] ?? $buyFeeCoin;
                            }
                        }
                    } else {
                        if (isset($orderDetail['baseFee']) && floatval($orderDetail['baseFee']) > 0) {
                            $buyFee = floatval($orderDetail['baseFee']);
                        } elseif (isset($orderDetail['fee'])) {
                            $buyFee = floatval($orderDetail['fee']);
                        }
                    }
                }
                
                $minCoin['amount'] += $actualBoughtQty;
                $minCoin['initial_investment'] += $actualSpentUsdt;
                
                if ($minCoin['amount'] > 0) {
                    $minCoin['buy_price'] = $minCoin['initial_investment'] / $minCoin['amount'];
                }

                $tradeRecord['buy'] = [
                    'direction' => 'Buy',
                    'coin' => $minCoin['coin_name'],
                    'time' => $buyTime,
                    'avg_price' => $buyAvgPrice,
                    'amount_usdt' => $actualSpentUsdt,
                    'volume_coin' => $actualBoughtQty,
                    'fee' => number_format($buyFee, 6, '.', ''),
                    'fee_coin' => $buyFeeCoin
                ];

                if (!isset($historyData[$botId])) { $historyData[$botId] = []; }
                $nextTradeNum = count($historyData[$botId]) + 1;
                $tradeRecord['trade_id'] = "#Trade " . $nextTradeNum;
                
                $historyData[$botId][] = $tradeRecord;
                $isHistoryUpdated = true;
                
                if (!isset($bot['cycle_count'])) {
                    $bot['cycle_count'] = 0;
                }
                $bot['cycle_count'] += 1;
                
                $bot['last_updated'] = date('c');
                $isAnyBotUpdated = true;
                
                logAction("Rebalance Complete for Bot: $botId. Saved as {$tradeRecord['trade_id']}. Cycle Count: {$bot['cycle_count']}");
            } else {
                logAction("FAILED BUY: {$minCoin['coin_name']} - " . json_encode($buyRes));
            }
        } else {
            logAction("FAILED SELL: {$maxCoin['coin_name']} - " . json_encode($sellRes));
        }
    }
}

if ($isAnyBotUpdated) {
    foreach ($botData as &$bot) {
        foreach ($bot['coins_details'] as &$coin) {
            unset($coin['temp_live_price']);
            unset($coin['temp_current_val']);
        }
    }
    file_put_contents($botFile, json_encode($botData, JSON_PRETTY_PRINT), LOCK_EX);
}
if ($isHistoryUpdated) {
    file_put_contents($historyFile, json_encode($historyData, JSON_PRETTY_PRINT), LOCK_EX);
}

echo "Engine Check Completed.\n";
?>