<?php
// bitget/trading/smart-buy-sell/sbs-termination.php

header('Content-Type: application/json');
require_once '../../settings/api-settings.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['bot_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request. Bot ID missing.']);
    exit;
}

// সেফ ট্রাংকেশন ফাংশন (Precision ফিক্স করার জন্য)
function safeTruncate($val, $precision = 4) {
    $multiplier = pow(10, $precision);
    return floor($val * $multiplier) / $multiplier;
}

$botId = $input['bot_id'];
$termType = isset($input['type']) ? $input['type'] : 'market_sell'; 
$botFile = 'sbs-bot-data.txt';
$historyFile = 'sbs-history.json'; // 💡 History file path added

if (!file_exists($botFile)) {
    echo json_encode(['status' => 'error', 'message' => 'Bot data file not found.']);
    exit;
}

$botData = json_decode(file_get_contents($botFile), true);
$targetBotIndex = null;

foreach ($botData as $index => $bot) {
    if ($bot['bot_id'] === $botId) {
        $targetBotIndex = $index;
        break;
    }
}

if ($targetBotIndex === null || $botData[$targetBotIndex]['status'] === 'Terminated') {
    echo json_encode(['status' => 'error', 'message' => 'Bot not found or already terminated.']);
    exit;
}

$targetBot = &$botData[$targetBotIndex];
$totalReturnedUSDT = 0;
$orderErrors = [];

// লাইভ প্রাইস সব ক্ষেত্রেই লাগবে (Fallback এবং Manual উভয়ের জন্য)
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
}

if ($termType === 'manual') {
    // --- MANUAL TERMINATION ---
    foreach ($targetBot['coins_details'] as &$c) {
        $symbol = $c['coin_name'] . "USDT";
        $livePrice = isset($livePrices[$symbol]) ? $livePrices[$symbol] : $c['buy_price'];
        $val = $c['amount'] * $livePrice;
        
        $c['sell_price'] = $livePrice;
        $c['returned_usdt'] = $val;
        $c['sell_order_id'] = 'MANUAL_KEPT';
        
        $totalReturnedUSDT += $val;
    }

} else {
    // --- MARKET SELL TERMINATION ---
    $placedOrders = [];
    foreach ($targetBot['coins_details'] as &$coin) {
        $symbol = $coin['coin_name'] . "USDT";
        $amountToSell = safeTruncate($coin['amount'], 4); // Precision Fix
        
        if ($amountToSell <= 0) continue;

        $endpoint = '/api/v2/spot/trade/place-order';
        $bodyArray = [
            "symbol" => $symbol, 
            "side" => "sell", 
            "orderType" => "market", 
            "force" => "normal", 
            "size" => (string)$amountToSell 
        ];
        $body = json_encode($bodyArray);
        
        $timestamp = (string)(round(microtime(true) * 1000));
        $message = $timestamp . 'POST' . $endpoint . $body;
        $signature = base64_encode(hash_hmac('sha256', $message, $secretKey, true));

        $headers = ["ACCESS-KEY: " . $apiKey, "ACCESS-SIGN: " . $signature, "ACCESS-TIMESTAMP: " . $timestamp, "ACCESS-PASSPHRASE: " . $passphrase, "Content-Type: application/json", "locale: en-US"];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resData = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($resData['code']) && $resData['code'] === '00000') {
            $placedOrders[] = ['coin_name' => $coin['coin_name'], 'orderId' => $resData['data']['orderId']];
        } else {
            // FALLBACK: যদি সেল রিজেক্ট হয় (যেমন ভ্যালু খুব কম থাকলে), লস যেন না দেখায়
            $orderErrors[] = "Failed to sell {$coin['coin_name']}: " . (isset($resData['msg']) ? $resData['msg'] : 'Error');
            
            $fallbackPrice = isset($livePrices[$symbol]) ? $livePrices[$symbol] : $coin['buy_price'];
            $fallbackVal = $coin['amount'] * $fallbackPrice;
            $totalReturnedUSDT += $fallbackVal;

            $coin['sell_price'] = $fallbackPrice;
            $coin['returned_usdt'] = $fallbackVal;
            $coin['sell_order_id'] = 'FAILED_KEPT'; // সেল না হলেও ব্যালেন্স স্পটেই থেকে যাবে
        }
    }

    if (count($placedOrders) > 0) {
        sleep(2); 

        foreach ($placedOrders as $po) {
            $coinName = $po['coin_name'];
            $orderId = $po['orderId'];
            
            $infoEndpoint = '/api/v2/spot/trade/orderInfo?orderId=' . $orderId;
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

            if (isset($infoData['code']) && $infoData['code'] === '00000') {
                $orderDetail = isset($infoData['data'][0]) ? $infoData['data'][0] : $infoData['data'];
                $sellPrice = isset($orderDetail['priceAvg']) ? floatval($orderDetail['priceAvg']) : 0;
                $returnedUSDT = isset($orderDetail['quoteVolume']) ? floatval($orderDetail['quoteVolume']) : 0; 
                
                $totalReturnedUSDT += $returnedUSDT;

                foreach ($targetBot['coins_details'] as &$c) {
                    if ($c['coin_name'] === $coinName) {
                        $c['sell_price'] = $sellPrice;
                        $c['returned_usdt'] = $returnedUSDT;
                        $c['sell_order_id'] = $orderId;
                        break;
                    }
                }
            }
        }
    }
}

// স্ট্যাটাস এবং PNL আপডেট
$targetBot['status'] = 'Terminated';
$targetBot['terminated_at'] = date('c');
$targetBot['final_value'] = $totalReturnedUSDT;
$targetBot['total_pnl'] = $totalReturnedUSDT - $targetBot['initial_investment'];
$targetBot['pnl_percentage'] = ($targetBot['initial_investment'] > 0) ? ($targetBot['total_pnl'] / $targetBot['initial_investment']) * 100 : 0;

file_put_contents($botFile, json_encode($botData, JSON_PRETTY_PRINT));

// 💡 Update History File
$existingHistory = [];
if (file_exists($historyFile)) {
    $historyData = file_get_contents($historyFile);
    if ($historyData) {
        $existingHistory = json_decode($historyData, true) ?: [];
    }
}

if (!isset($existingHistory[$botId])) {
    $existingHistory[$botId] = [];
}

$terminationEvent = [
    "trade_id" => "Strategy Terminated",
    "timestamp" => $targetBot['terminated_at'],
    "event_type" => "termination",
    "termination_type" => $termType,
    "final_value" => $targetBot['final_value'],
    "total_pnl" => $targetBot['total_pnl'],
    "pnl_percentage" => $targetBot['pnl_percentage'],
    "details" => $targetBot['coins_details']
];

$existingHistory[$botId][] = $terminationEvent;

file_put_contents($historyFile, json_encode($existingHistory, JSON_PRETTY_PRINT), LOCK_EX);

if (count($orderErrors) > 0) {
    echo json_encode(['status' => 'warning', 'message' => "Bot Terminated, but some market sells failed (due to API limits). Unsold coins are kept in Spot.", 'errors' => $orderErrors, 'final_pnl' => $targetBot['total_pnl']]);
} else {
    $msgType = $termType === 'manual' ? "Coins are kept safely in your spot wallet." : "All assets sold at market price.";
    echo json_encode(['status' => 'success', 'message' => "SBS Strategy Terminated! $msgType", 'final_pnl' => $targetBot['total_pnl']]);
}
?>
