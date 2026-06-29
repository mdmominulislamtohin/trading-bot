<?php
// bitget/trading/smart-portfolio-strategy/sps-create-process.php

header('Content-Type: application/json');
require_once '../../settings/api-settings.php'; // আপনার API ক্রেডেনশিয়াল

// POST ডেটা রিসিভ করা
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['bot_name']) || empty($input['composition'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data received.']);
    exit;
}

$botName = $input['bot_name'];
$totalInvestment = floatval($input['initial_investment']);
$rebalancePct = floatval($input['rebalancing_percentage']);
$composition = $input['composition'];

$botId = "SPS-" . time();
$coinsDetails = [];
$orderErrors = [];
$placedOrders = [];

// ১. প্রথমে সব কয়েনের Market Buy Order প্লেস করা (সুপার ফাস্ট লুপ)
foreach ($composition as $item) {
    $coin = $item['coin'];
    $pct = floatval($item['percentage']);
    $symbol = $coin . "USDT";
    
    // এই কয়েনের জন্য কত ডলার (USDT) বরাদ্দ করা হয়েছে
    $allocatedUSDT = $totalInvestment * ($pct / 100);
    
    $endpoint = '/api/v2/spot/trade/place-order';
    $url = $baseUrl . $endpoint;
    
    $bodyArray = [
        "symbol" => $symbol,
        "side" => "buy",
        "orderType" => "market",
        "force" => "normal",
        "size" => (string)round($allocatedUSDT, 4) // USDT amount
    ];
    
    $body = json_encode($bodyArray);
    $timestamp = (string)(round(microtime(true) * 1000));
    $message = $timestamp . 'POST' . $endpoint . $body;
    $signature = base64_encode(hash_hmac('sha256', $message, $secretKey, true));

    $headers = [
        "ACCESS-KEY: " . $apiKey,
        "ACCESS-SIGN: " . $signature,
        "ACCESS-TIMESTAMP: " . $timestamp,
        "ACCESS-PASSPHRASE: " . $passphrase,
        "Content-Type: application/json",
        "locale: en-US"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $orderResponse = curl_exec($ch);
    curl_close($ch);

    $resData = json_decode($orderResponse, true);

    // ট্রেড রিকোয়েস্ট সফল হলে Order ID কালেক্ট করে রাখা
    if (isset($resData['code']) && $resData['code'] === '00000' && isset($resData['data']['orderId'])) {
        $placedOrders[] = [
            'coin' => $coin,
            'symbol' => $symbol,
            'orderId' => $resData['data']['orderId'],
            'allocatedUSDT' => $allocatedUSDT
        ];
    } else {
        $errorMsg = isset($resData['msg']) ? $resData['msg'] : 'Unknown Error';
        $orderErrors[] = "$coin Order Failed: $errorMsg";
    }
}

// ২. এক্সচেঞ্জকে অর্ডার ফিল করার জন্য সময় দেওয়া
if (count($placedOrders) > 0) {
    sleep(2); // ২ সেকেন্ড অপেক্ষা, যাতে মার্কেট অর্ডার পুরোপুরি এক্সিকিউট হয়ে যায়
}

// ৩. এক্সচেঞ্জ থেকে এক্সাক্ট (Exact) ডেটা তুলে আনা
foreach ($placedOrders as $po) {
    $coin = $po['coin'];
    $orderId = $po['orderId'];
    $allocatedUSDT = $po['allocatedUSDT'];
    
    // Get Order Info API 
    $infoEndpoint = '/api/v2/spot/trade/orderInfo?orderId=' . $orderId;
    $infoUrl = $baseUrl . $infoEndpoint;
    $infoTimestamp = (string)(round(microtime(true) * 1000));
    $infoMessage = $infoTimestamp . 'GET' . $infoEndpoint;
    $infoSignature = base64_encode(hash_hmac('sha256', $infoMessage, $secretKey, true));

    $infoHeaders = [
        "ACCESS-KEY: " . $apiKey,
        "ACCESS-SIGN: " . $infoSignature,
        "ACCESS-TIMESTAMP: " . $infoTimestamp,
        "ACCESS-PASSPHRASE: " . $passphrase,
        "Content-Type: application/json",
        "locale: en-US"
    ];

    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $infoUrl);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, $infoHeaders);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
    $infoResponse = curl_exec($ch2);
    curl_close($ch2);

    $infoData = json_decode($infoResponse, true);
    
    if (isset($infoData['code']) && $infoData['code'] === '00000') {
        $orderDetail = isset($infoData['data'][0]) ? $infoData['data'][0] : $infoData['data'];
        
        $executedQty = isset($orderDetail['baseVolume']) ? floatval($orderDetail['baseVolume']) : 0;
        $avgPrice = isset($orderDetail['priceAvg']) ? floatval($orderDetail['priceAvg']) : 0;
        
        if ($executedQty <= 0 && $avgPrice > 0) {
            $executedQty = $allocatedUSDT / $avgPrice;
        }

        $coinsDetails[] = [
            "coin_name" => $coin,
            "amount" => $executedQty,           
            "buy_price" => $avgPrice,           
            "initial_investment" => $allocatedUSDT,
            "order_id" => $orderId
        ];
    } else {
        $orderErrors[] = "Order placed, but failed to fetch exact execution details for $coin.";
    }
}

// ৪. সবকিছু bot-list-data.txt এ সেভ করা
if (count($coinsDetails) > 0) {
    $botFile = 'bot-list-data.txt';
    $existingData = [];
    
    if (file_exists($botFile)) {
        $fileContent = file_get_contents($botFile);
        $existingData = json_decode($fileContent, true) ?: [];
    }

    $newBot = [
        "bot_id" => $botId,
        "bot_name" => $botName,
        "status" => "Running",
        "initial_investment" => $totalInvestment,
        "rebalancing_percentage" => $rebalancePct,
        "created_at" => date('c'), // <-- নতুন যুক্ত করা হলো (Bot Creation Date & Time)
        "last_updated" => date('c'),
        "coins_details" => $coinsDetails
    ];

    $existingData[] = $newBot;
    file_put_contents($botFile, json_encode($existingData, JSON_PRETTY_PRINT));

    if (count($orderErrors) > 0) {
        echo json_encode(['status' => 'warning', 'message' => "Bot created partially. Exact details fetched from Exchange.", 'errors' => $orderErrors]);
    } else {
        echo json_encode(['status' => 'success', 'message' => "Strategy created! Exact real-time order amounts have been synced.", 'bot_id' => $botId]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => "Failed to execute or verify any orders.", 'errors' => $orderErrors]);
}
?>
