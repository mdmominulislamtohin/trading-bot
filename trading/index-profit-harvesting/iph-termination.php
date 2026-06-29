<?php
// bitget/trading/index-profit-harvesting/iph-termination.php
set_time_limit(0);
header('Content-Type: application/json');

require_once '../../settings/api-settings.php';

$botFile = 'iph-bot-data.txt';
$historyFile = 'iph-history.json';

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

if (!$inputData || !isset($inputData['bot_id']) || !isset($inputData['type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request payload.']);
    exit;
}

$botId = $inputData['bot_id'];
$termType = $inputData['type']; // 'market_sell' or 'manual'

if (!file_exists($botFile)) {
    echo json_encode(['status' => 'error', 'message' => 'Bot database not found.']);
    exit;
}

$bots = json_decode(file_get_contents($botFile), true) ?: [];
$botIndex = -1;

foreach ($bots as $index => $b) {
    if ($b['bot_id'] === $botId) {
        $botIndex = $index;
        break;
    }
}

if ($botIndex === -1) {
    echo json_encode(['status' => 'error', 'message' => 'Bot not found.']);
    exit;
}

$bot = &$bots[$botIndex];

if ($bot['status'] === 'Terminated') {
    echo json_encode(['status' => 'error', 'message' => 'Bot is already terminated.']);
    exit;
}

// 2. Fetch Live Prices
$tickersRes = bitgetApiCall('/api/v2/spot/market/tickers', 'GET');
$livePrices = [];
if (isset($tickersRes['code']) && $tickersRes['code'] === '00000' && isset($tickersRes['data'])) {
    foreach ($tickersRes['data'] as $t) {
        $livePrices[$t['symbol']] = floatval($t['lastPr']);
    }
}

// 3. Fetch Decimal Limits (sizePlace) for Safe Selling
$sizePlaces = [];
if ($termType === 'market_sell') {
    $symbolsRes = bitgetApiCall('/api/v2/spot/public/symbols', 'GET');
    if (isset($symbolsRes['code']) && $symbolsRes['code'] === '00000' && isset($symbolsRes['data'])) {
        foreach ($symbolsRes['data'] as $s) {
            // Using sizePlace as per Bitget V2 API update
            $sp = isset($s['sizePlace']) ? intval($s['sizePlace']) : (isset($s['quantityScale']) ? intval($s['quantityScale']) : 4);
            $sizePlaces[$s['symbol']] = $sp;
        }
    }
}

$totalReturnedUsdt = 0;
$executionErrors = [];
$historyDetails = [];

// 4. Process Coins
foreach ($bot['coins_details'] as &$coin) {
    $symbol = $coin['coin_name'] . 'USDT';
    $amount = floatval($coin['amount']);
    $livePrice = isset($livePrices[$symbol]) ? $livePrices[$symbol] : (isset($coin['buy_price']) ? $coin['buy_price'] : 0);
    
    $sellPrice = $livePrice;
    $returnedUsdt = $amount * $livePrice; 

    if ($termType === 'market_sell' && $amount > 0) {
        $sp = isset($sizePlaces[$symbol]) ? $sizePlaces[$symbol] : 4;
        
        // Strict Decimal Truncation based on sizePlace
        $factor = pow(10, $sp);
        $sellQty = floor($amount * $factor) / $factor;
        $sellQtyStr = number_format($sellQty, $sp, '.', '');

        if ($sellQty > 0) {
            $orderRes = bitgetApiCall('/api/v2/spot/trade/place-order', 'POST', [
                "symbol" => $symbol,
                "side" => "sell",
                "orderType" => "market",
                "force" => "normal",
                "size" => $sellQtyStr
            ]);

            if (isset($orderRes['code']) && $orderRes['code'] === '00000') {
                $orderId = $orderRes['data']['orderId'];
                sleep(1); // Wait for fill
                
                $orderInfo = bitgetApiCall('/api/v2/spot/trade/orderInfo?orderId=' . $orderId, 'GET');
                if (isset($orderInfo['code']) && $orderInfo['code'] === '00000') {
                    $detail = isset($orderInfo['data'][0]) ? $orderInfo['data'][0] : $orderInfo['data'];
                    if (isset($detail['priceAvg']) && floatval($detail['priceAvg']) > 0) {
                        $sellPrice = floatval($detail['priceAvg']);
                    }
                    if (isset($detail['quoteVolume']) && floatval($detail['quoteVolume']) > 0) {
                        // Deduct standard 0.1% spot fee roughly if exact fee not parsed perfectly
                        $grossUsdt = floatval($detail['quoteVolume']);
                        $returnedUsdt = $grossUsdt * 0.999; 
                    }
                }
            } else {
                $executionErrors[] = "Sell failed for {$coin['coin_name']}. Reverted to manual holding.";
            }
        }
    }

    $coin['sell_price'] = $sellPrice;
    $coin['returned_usdt'] = $returnedUsdt;
    $totalReturnedUsdt += $returnedUsdt;

    $historyDetails[] = [
        "coin" => $coin['coin_name'],
        "direction" => ($termType === 'market_sell' && empty($executionErrors)) ? "Sell (Market)" : "Hold (Manual)",
        "amount_usdt" => number_format($returnedUsdt, 2, '.', ''),
        "avg_price" => number_format($sellPrice, 6, '.', '')
    ];
}

// 5. Final Calculations
$reserveUsdt = isset($bot['reserve_usdt']) ? floatval($bot['reserve_usdt']) : 0;
$initialInvestment = floatval($bot['initial_investment']);

// Total retrieved value includes what we got back from coins PLUS what was saved in the cash buffer
$finalTotalValue = $totalReturnedUsdt + $reserveUsdt; 

$totalPnl = $finalTotalValue - $initialInvestment;
$pnlPercentage = ($initialInvestment > 0) ? (($totalPnl / $initialInvestment) * 100) : 0;

$bot['status'] = 'Terminated';
$bot['terminated_at'] = date('c');
$bot['total_pnl'] = round($totalPnl, 2);
$bot['pnl_percentage'] = round($pnlPercentage, 2);

// Save Bot Data
file_put_contents($botFile, json_encode($bots, JSON_PRETTY_PRINT), LOCK_EX);

// 6. Save History Event
$histories = json_decode(file_get_contents($historyFile), true) ?: [];
if (!isset($histories[$botId])) {
    $histories[$botId] = [];
}

$histories[$botId][] = [
    "trade_id" => "Termination",
    "timestamp" => date('c'),
    "event_type" => "termination",
    "termination_type" => $termType,
    "total_returned" => round($totalReturnedUsdt, 2),
    "final_pnl" => round($totalPnl, 2),
    "details" => $historyDetails
];

file_put_contents($historyFile, json_encode($histories, JSON_PRETTY_PRINT), LOCK_EX);

// 7. Response
if (count($executionErrors) > 0) {
    echo json_encode([
        'status' => 'warning', 
        'message' => 'Terminated with warnings. Some coins could not be sold.',
        'errors' => $executionErrors
    ]);
} else {
    $msg = $termType === 'market_sell' ? 'All assets sold and bot terminated successfully.' : 'Bot terminated successfully. Assets are kept in your spot wallet.';
    echo json_encode([
        'status' => 'success', 
        'message' => $msg
    ]);
}
?>
