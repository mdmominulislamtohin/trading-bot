<?php
// bitget/trading/smart-portfolio-strategy/sps-termination.php

header('Content-Type: application/json');
require_once '../../settings/api-settings.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['bot_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request. Bot ID missing.']);
    exit;
}

$botId = $input['bot_id'];
$termType = isset($input['type']) ? $input['type'] : 'market_sell'; // 'market_sell' or 'manual'
$botFile = 'bot-list-data.txt';

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

if ($termType === 'manual') {
    // --- MANUAL TERMINATION (Keep Coins) ---
    // শুধু লাইভ প্রাইস এনে সেভ করবে, কোনো ট্রেড করবে না
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
            foreach ($tickersData['data'] as $t) { $livePrices[$t['symbol']] = floatval($t['lastPr']); }
        }
    }

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
    // --- MARKET SELL TERMINATION (Auto Sell) ---
    $placedOrders = [];
    foreach ($targetBot['coins_details'] as $coin) {
        $symbol = $coin['coin_name'] . "USDT";
        $amountToSell = $coin['amount']; 
        
        $endpoint = '/api/v2/spot/trade/place-order';
        $bodyArray = ["symbol" => $symbol, "side" => "sell", "orderType" => "market", "force" => "normal", "size" => (string)round($amountToSell, 6)];
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
            $orderErrors[] = "Failed to sell {$coin['coin_name']}: " . (isset($resData['msg']) ? $resData['msg'] : 'Error');
        }
    }

    if (count($placedOrders) > 0) sleep(2);

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

// ৪. বটের স্ট্যাটাস আপডেট করা
$targetBot['status'] = 'Terminated';
$targetBot['terminated_at'] = date('c');
$targetBot['final_value'] = $totalReturnedUSDT;
$targetBot['total_pnl'] = $totalReturnedUSDT - $targetBot['initial_investment'];
$targetBot['pnl_percentage'] = ($targetBot['total_pnl'] / $targetBot['initial_investment']) * 100;

file_put_contents($botFile, json_encode($botData, JSON_PRETTY_PRINT));

if (count($orderErrors) > 0) {
    echo json_encode(['status' => 'warning', 'message' => "Bot Terminated, but some market sells failed.", 'errors' => $orderErrors]);
} else {
    $msgType = $termType === 'manual' ? "Bot terminated. Coins are kept in your wallet." : "All assets sold at market price.";
    echo json_encode(['status' => 'success', 'message' => "Strategy stopped! $msgType", 'final_pnl' => $targetBot['total_pnl']]);
}
?>
