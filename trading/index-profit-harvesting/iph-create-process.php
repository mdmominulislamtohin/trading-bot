<?php
// bitget/trading/index-profit-harvesting/iph-create-process.php
set_time_limit(0);
header('Content-Type: application/json');

// API Settings File Include
require_once '../../settings/api-settings.php'; 

// Data Files
$botFile = 'iph-bot-data.txt';
$historyFile = 'iph-history.json';

// Helper Function: Bitget V2 API Call
function bitgetApiCall($endpoint, $method = 'GET', $bodyArray = null) {
    global $apiKey, $secretKey, $passphrase, $baseUrl;

    $timestamp = (string)(round(microtime(true) * 1000));
    $bodyStr = '';
    
    if ($method === 'POST' && $bodyArray) {
        $bodyStr = json_encode($bodyArray);
    }

    $message = $timestamp . $method . $endpoint . $bodyStr;
    $signature = base64_encode(hash_hmac('sha256', $message, $secretKey, true));

    $headers = [
        "ACCESS-KEY: " . $apiKey,
        "ACCESS-SIGN: " . $signature,
        "ACCESS-TIMESTAMP: " . $timestamp,
        "ACCESS-PASSPHRASE: " . $passphrase,
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    $url = $baseUrl . $endpoint;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyStr);
    }

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// 1. Get Payload
$inputData = json_decode(file_get_contents('php://input'), true);

if (!$inputData || !isset($inputData['strategy_name']) || empty($inputData['coins'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid payload.']);
    exit;
}

$botId = 'IPH-' . time() . rand(100, 999);
$strategyName = $inputData['strategy_name'];
$takeProfitPct = floatval($inputData['take_profit_pct']);
$dropBuyPct = floatval($inputData['drop_buy_pct']);
$totalStrategyValue = floatval($inputData['total_strategy_value']);
$coinsInput = $inputData['coins'];

// 2. Fetch Live Prices (To calculate holding values properly)
$tickersRes = bitgetApiCall('/api/v2/spot/market/tickers', 'GET');
$livePrices = [];
if (isset($tickersRes['code']) && $tickersRes['code'] === '00000' && isset($tickersRes['data'])) {
    foreach ($tickersRes['data'] as $t) {
        $livePrices[$t['symbol']] = floatval($t['lastPr']);
    }
}

$processedCoins = [];
$actualTotalInvestment = 0;
$executionErrors = [];

// 3. Process Each Coin (Buy New & Add Holdings)
foreach ($coinsInput as $coin) {
    $coinName = $coin['coin_name'];
    $useQty = floatval($coin['use_holding_qty']);
    $buyUsdt = floatval($coin['buy_usdt']);
    $symbol = $coinName . 'USDT';
    
    $livePrice = isset($livePrices[$symbol]) ? $livePrices[$symbol] : 0;
    
    $actualBoughtQty = 0;
    $actualSpentUsdt = 0;
    $avgBuyPrice = $livePrice; // Default to live price

    // --- Execute Market BUY Order if buy_usdt > 0 ---
    if ($buyUsdt > 0) {
        $buyUsdtStr = number_format(floor($buyUsdt * 100) / 100, 2, '.', ''); // Safe 2 decimal truncation
        
        $placeOrderRes = bitgetApiCall('/api/v2/spot/trade/place-order', 'POST', [
            "symbol" => $symbol,
            "side" => "buy",
            "orderType" => "market",
            "force" => "normal",
            "size" => $buyUsdtStr
        ]);

        if (isset($placeOrderRes['code']) && $placeOrderRes['code'] === '00000') {
            $orderId = $placeOrderRes['data']['orderId'];
            sleep(2); // Wait for order to fill

            // Get exact fill details
            $orderInfoRes = bitgetApiCall('/api/v2/spot/trade/orderInfo?orderId=' . $orderId, 'GET');
            
            if (isset($orderInfoRes['code']) && $orderInfoRes['code'] === '00000') {
                $orderDetail = isset($orderInfoRes['data'][0]) ? $orderInfoRes['data'][0] : $orderInfoRes['data'];
                if (isset($orderDetail['baseVolume']) && floatval($orderDetail['baseVolume']) > 0) {
                    $actualBoughtQty = floatval($orderDetail['baseVolume']);
                }
                if (isset($orderDetail['quoteVolume']) && floatval($orderDetail['quoteVolume']) > 0) {
                    $actualSpentUsdt = floatval($orderDetail['quoteVolume']);
                }
                if (isset($orderDetail['priceAvg']) && floatval($orderDetail['priceAvg']) > 0) {
                    $avgBuyPrice = floatval($orderDetail['priceAvg']);
                }
            } else {
                // Fallback if orderInfo fails
                $actualSpentUsdt = $buyUsdt;
                $actualBoughtQty = $buyUsdt / $livePrice;
            }
        } else {
            $executionErrors[] = "Failed to buy $coinName: " . json_encode($placeOrderRes);
            // If buy fails, we still try to use the holding if there is any
        }
    }

    // Combine Holdings + Bought
    $finalAmount = $useQty + $actualBoughtQty;
    
    // Value of holdings is calculated based on current live price
    $holdingValueUsdt = $useQty * $livePrice; 
    
    $totalInvestmentForCoin = $holdingValueUsdt + $actualSpentUsdt;
    $actualTotalInvestment += $totalInvestmentForCoin;

    // Calculate absolute average buy price
    $finalAvgPrice = $finalAmount > 0 ? ($totalInvestmentForCoin / $finalAmount) : $livePrice;

    $processedCoins[] = [
        "coin_name" => $coinName,
        "amount" => $finalAmount,
        "buy_price" => $finalAvgPrice,
        "initial_investment" => $totalInvestmentForCoin
    ];
}

if (count($processedCoins) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to process any coins.']);
    exit;
}

// 4. Construct Bot Data Array
$botRecord = [
    "bot_id" => $botId,
    "bot_name" => $strategyName,
    "status" => "Running",
    "created_at" => date('c'),
    "last_updated" => date('c'),
    "take_profit_pct" => $takeProfitPct,
    "drop_buy_pct" => $dropBuyPct,
    "initial_investment" => $actualTotalInvestment,
    "base_target_value" => $actualTotalInvestment, // 💡 This is the anchor point ($200)
    "reserve_usdt" => 0.00, // 💡 Cash Buffer starts at 0
    "total_pnl" => 0,
    "pnl_percentage" => 0,
    "coins_details" => $processedCoins
];

// 5. Save Bot to Database
$existingBots = [];
if (file_exists($botFile)) {
    $fileData = file_get_contents($botFile);
    if ($fileData) {
        $existingBots = json_decode($fileData, true) ?: [];
    }
}
$existingBots[] = $botRecord;
file_put_contents($botFile, json_encode($existingBots, JSON_PRETTY_PRINT), LOCK_EX);

// 6. Save Initial History Record (Matching the design)
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
        "total_invested" => $actualTotalInvestment,
        "details" => $processedCoins
    ]
];

file_put_contents($historyFile, json_encode($existingHistory, JSON_PRETTY_PRINT), LOCK_EX);

// 7. Final Response
if (count($executionErrors) > 0) {
    echo json_encode([
        'status' => 'warning', 
        'message' => 'Bot created, but some buy orders failed.', 
        'errors' => $executionErrors
    ]);
} else {
    echo json_encode([
        'status' => 'success', 
        'message' => 'IPH Strategy created successfully!'
    ]);
}
?>
