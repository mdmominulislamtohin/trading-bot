<?php
// bitget/trading/smart-buy-sell/sbs-create-process.php

header('Content-Type: application/json');
require_once '../../settings/api-settings.php'; // API ক্রেডেনশিয়াল

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['coins']) || count($input['coins']) < 2) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request. Minimum 2 coins required.']);
    exit;
}

$strategyName = $input['strategy_name'];
$gapTrigger = floatval($input['gap_trigger']);
$tradeAmount = floatval($input['trade_amount']);
$coins = $input['coins'];

$botId = 'SBS-' . time(); 
$placedOrders = [];
$orderErrors = [];
$coinsDetails = [];
$totalStrategyInvestment = 0;

// ১. হোল্ডিং ভ্যালুয়েশনের জন্য লাইভ প্রাইস আনা
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

// ২. প্রতিটি কয়েন প্রসেস করা (Hybrid Logic)
foreach ($coins as $coinData) {
    $coinName = $coinData['coin_name'];
    $useHoldingQty = floatval($coinData['use_holding_qty']);
    $buyUsdt = floatval($coinData['buy_usdt']);
    $symbol = $coinName . "USDT";
    
    $currentPrice = isset($livePrices[$symbol]) ? $livePrices[$symbol] : 0;
    
    $totalQty = $useHoldingQty;
    $totalCoinValueUsdt = ($useHoldingQty * $currentPrice); // হোল্ডিংয়ের ভ্যালু
    $avgBuyPrice = $currentPrice;

    // যদি ইউজার নতুন করে ডলার দিয়ে কিনতে চায়, তবেই API তে রিকোয়েস্ট যাবে
    if ($buyUsdt >= 1.05) {
        $endpoint = '/api/v2/spot/trade/place-order';
        $bodyArray = [
            "symbol" => $symbol,
            "side" => "buy",
            "orderType" => "market",
            "force" => "normal",
            "size" => (string)round($buyUsdt, 4) 
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
        curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resData = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($resData['code']) && $resData['code'] === '00000') {
            // অর্ডার সাকসেস হলে ইনফো সেভ করে রাখা হচ্ছে
            $placedOrders[] = [
                'coin_name' => $coinName,
                'orderId' => $resData['data']['orderId'],
                'holding_qty' => $useHoldingQty,
                'holding_val' => $totalCoinValueUsdt
            ];
        } else {
            $orderErrors[] = "Failed to buy $coinName: " . (isset($resData['msg']) ? $resData['msg'] : 'Unknown Error');
        }
    } else {
        // যদি কেনা না লাগে (শুধুমাত্র হোল্ডিং ইউজ করে), সরাসরি বটের ডিটেইলসে অ্যাড করে দাও
        if ($useHoldingQty > 0) {
            $coinsDetails[] = [
                'coin_name' => $coinName,
                'amount' => $totalQty,
                'buy_price' => $avgBuyPrice, // হোল্ডিংয়ের ক্ষেত্রে ওই মুহূর্তের লাইভ প্রাইসকে বাই প্রাইস ধরা হয়
                'initial_investment' => $totalCoinValueUsdt
            ];
            $totalStrategyInvestment += $totalCoinValueUsdt;
        }
    }
}

// ৩. যদি কোনো অর্ডার প্লেস হয়ে থাকে, তবে ২ সেকেন্ড অপেক্ষা করে অর্ডার ইনফো আনা
if (count($placedOrders) > 0) {
    sleep(2); // সেটেলমেন্টের জন্য সময়

    foreach ($placedOrders as $order) {
        $coinName = $order['coin_name'];
        $orderId = $order['orderId'];
        $holdingQty = $order['holding_qty'];
        $holdingVal = $order['holding_val'];

        $infoEndpoint = '/api/v2/spot/trade/orderInfo?orderId=' . $orderId;
        $infoTimestamp = (string)(round(microtime(true) * 1000));
        $infoSignature = base64_encode(hash_hmac('sha256', $infoTimestamp . 'GET' . $infoEndpoint, $secretKey, true));
        $infoHeaders = [
            "ACCESS-KEY: " . $apiKey, "ACCESS-SIGN: " . $infoSignature, "ACCESS-TIMESTAMP: " . $infoTimestamp, "ACCESS-PASSPHRASE: " . $passphrase, "Content-Type: application/json"
        ];

        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, $baseUrl . $infoEndpoint);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, $infoHeaders);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        $infoData = json_decode(curl_exec($ch2), true);
        curl_close($ch2);

        if (isset($infoData['code']) && $infoData['code'] === '00000') {
            $orderDetail = isset($infoData['data'][0]) ? $infoData['data'][0] : $infoData['data'];
            
            $boughtPrice = isset($orderDetail['priceAvg']) ? floatval($orderDetail['priceAvg']) : 0;
            $boughtQty = isset($orderDetail['baseVolume']) ? floatval($orderDetail['baseVolume']) : 0;
            $boughtUsdt = isset($orderDetail['quoteVolume']) ? floatval($orderDetail['quoteVolume']) : 0;

            // হোল্ডিং এবং নতুন কেনা কয়েন মিক্স করা (Hybrid Calculation)
            $totalQty = $holdingQty + $boughtQty;
            $totalValue = $holdingVal + $boughtUsdt;
            $avgPrice = $totalQty > 0 ? ($totalValue / $totalQty) : $boughtPrice;

            $coinsDetails[] = [
                'coin_name' => $coinName,
                'amount' => $totalQty,
                'buy_price' => $avgPrice,
                'initial_investment' => $totalValue
            ];
            $totalStrategyInvestment += $totalValue;
        }
    }
}

// যদি কোনো কয়েনই প্রসেস না হয় (না হোল্ডিং, না বাই)
if (count($coinsDetails) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to process any coins. Strategy aborted.', 'errors' => $orderErrors]);
    exit;
}

// ৪. নতুন বটের ডেটা স্ট্রাকচার তৈরি
$newBotData = [
    'bot_id' => $botId,
    'bot_name' => $strategyName,
    'bot_type' => 'SBS_Perfect_Reversion',
    'status' => 'Running',
    'initial_investment' => $totalStrategyInvestment, // হোল্ডিং + বাই মিলে টোটাল ইনভেস্টমেন্ট
    'gap_trigger' => $gapTrigger,
    'trade_amount' => $tradeAmount,
    'created_at' => date('c'),
    'last_updated' => date('c'),
    'coins_details' => $coinsDetails
];

// ৫. ডেটাবেস ফাইলে (TXT/JSON) সেভ করা
$botFile = 'sbs-bot-data.txt';
$existingBots = [];

if (file_exists($botFile)) {
    $existingBots = json_decode(file_get_contents($botFile), true) ?: [];
}

$existingBots[] = $newBotData;
file_put_contents($botFile, json_encode($existingBots, JSON_PRETTY_PRINT));

// ৬. Save Initial History Record (IPH Standard)
$historyFile = 'sbs-history.json';
$existingHistory = [];
if (file_exists($historyFile)) {
    $historyData = file_get_contents($historyFile);
    if ($historyData) {
        $existingHistory = json_decode($historyData, true) ?: [];
    }
}

$existingHistory[$botId] = [
    [
        "trade_id" => "Initial investment",
        "timestamp" => date('c'),
        "event_type" => "setup",
        "total_invested" => $totalStrategyInvestment,
        "details" => $coinsDetails
    ]
];

file_put_contents($historyFile, json_encode($existingHistory, JSON_PRETTY_PRINT), LOCK_EX);

// ৭. রেসপন্স পাঠানো
if (count($orderErrors) > 0) {
    echo json_encode([
        'status' => 'warning', 
        'message' => 'Bot created, but some buy orders failed.', 
        'bot_id' => $botId,
        'errors' => $orderErrors
    ]);
} else {
    echo json_encode([
        'status' => 'success', 
        'message' => 'SBS Strategy successfully configured!',
        'bot_id' => $botId
    ]);
}
?>
