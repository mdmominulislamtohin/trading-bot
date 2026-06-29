<?php
// get-chart-data.php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

$symbol = isset($_GET['symbol']) ? strtoupper($_GET['symbol']) : 'BTCUSDT';
$tf = isset($_GET['tf']) ? $_GET['tf'] : '1H'; // ডিফল্ট টাইমফ্রেম 1H

$jsonFilePath = __DIR__ . '/chart-data.json';

// টাইমফ্রেমকে Bitget-এর ফরম্যাটে কনভার্ট করা
$tfMap = [
    '5m' => '5min', '15m' => '15min', '30m' => '30min',
    '1H' => '1h', '4H' => '4h', '1D' => '1day',
    '1W' => '1week', '1M' => '1M'
];
$granularity = isset($tfMap[$tf]) ? $tfMap[$tf] : '1h';

if (!file_exists($jsonFilePath)) {
    @file_put_contents($jsonFilePath, json_encode([]));
}

$allData = [];
if (file_exists($jsonFilePath)) {
    $content = file_get_contents($jsonFilePath);
    $parsed = json_decode($content, true);
    if (is_array($parsed)) $allData = $parsed;
}

// ১০০০ ক্যান্ডেল ফেচ করা (যাতে দ্রুত ৬ মাসের ডেটা জমে যায়)
$url = "https://api.bitget.com/api/v2/spot/market/candles?symbol={$symbol}&granularity={$granularity}&limit=1000";
// পার্সেন্টেজ এবং লাইভ প্রাইসের জন্য Ticker API
$tickerUrl = "https://api.bitget.com/api/v2/spot/market/tickers?symbol={$symbol}";

$ch1 = curl_init($url);
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch1, CURLOPT_TIMEOUT, 15);
$response1 = curl_exec($ch1);
curl_close($ch1);

$ch2 = curl_init($tickerUrl);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_TIMEOUT, 5);
$response2 = curl_exec($ch2);
curl_close($ch2);

$tickerData = null;
if ($response2) {
    $tData = json_decode($response2, true);
    if (isset($tData['data'][0])) {
        $tickerData = $tData['data'][0];
    }
}

if ($response1) {
    $data = json_decode($response1, true);
    if (isset($data['code']) && $data['code'] === '00000' && isset($data['data'])) {
        $newCandles = $data['data'];
        $timestampMap = [];
        
        // পুরনো ডেটা ম্যাপে অ্যাড করা
        if (isset($allData[$symbol][$tf]) && is_array($allData[$symbol][$tf])) {
            foreach ($allData[$symbol][$tf] as $candle) {
                $timestampMap[$candle[0]] = $candle; 
            }
        }
        
        // নতুন ডেটা দিয়ে পুরনো ডেটা ওভাররাইট/আপডেট করা
        foreach ($newCandles as $candle) {
            $timestampMap[$candle[0]] = $candle; 
        }
        
        // সর্টিং করা (পুরনো থেকে নতুন)
        $mergedCandles = array_values($timestampMap);
        usort($mergedCandles, function($a, $b) { return $a[0] - $b[0]; });
        
        // JSON-এ টাইমফ্রেম অনুযায়ী ডেটা সেভ করা
        $allData[$symbol][$tf] = $mergedCandles;
        @file_put_contents($jsonFilePath, json_encode($allData));
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success', 
            'symbol' => $symbol, 
            'tf' => $tf,
            'ticker' => $tickerData,
            'data' => $allData[$symbol][$tf]
        ]);
        exit;
    }
}

ob_end_clean();
header('Content-Type: application/json');

// API ফেইল করলে অফলাইন ডেটা দেখানো
if (isset($allData[$symbol][$tf]) && !empty($allData[$symbol][$tf])) {
    echo json_encode(['status' => 'success', 'symbol' => $symbol, 'tf' => $tf, 'ticker' => $tickerData, 'data' => $allData[$symbol][$tf]]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch API and no local data found.']);
}
?>
