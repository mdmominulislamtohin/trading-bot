<?php
// crts-api.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$symbol = "ADAUSDT";
$granularity = "3min"; // 🔥 FIX: Bitget V2 Spot API তে 3m এর বদলে 3min লিখতে হয়!
$jsonFile = 'ada-3m-candel-data.json';

// ১. আগে থেকে থাকা ডেটা লোড করা
$existingData = [];
if (file_exists($jsonFile)) {
    $existingData = json_decode(file_get_contents($jsonFile), true) ?: [];
}

// ২. বিটগেট থেকে ডেটা আনার ফাংশন
function fetchCandles($sym, $gran, $limit) {
    $url = "https://api.bitget.com/api/v2/spot/market/candles?symbol={$sym}&granularity={$gran}&limit={$limit}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($res, true);
    return isset($data['data']) ? $data['data'] : [];
}

// ৩. আপডেট লজিক 
if (empty($existingData)) {
    // প্রথমবার রান করলে একবারে ২০০ ক্যান্ডেল নিয়ে আসবে (API সেফ লিমিট)
    $newCandles = fetchCandles($symbol, $granularity, 200); 
} else {
    // পরবর্তী সময়ে শুধু লেটেস্ট ২০টি চেক করে মিসিং ডেটা যোগ করবে
    $newCandles = fetchCandles($symbol, $granularity, 20);
}

// ৪. ডেটা মার্জ করা (ডুপ্লিকেট রিমুভ করে)
if (!empty($newCandles)) {
    $allDataMap = [];
    foreach ($existingData as $c) { $allDataMap[$c[0]] = $c; }
    foreach ($newCandles as $c) { $allDataMap[$c[0]] = $c; }

    ksort($allDataMap); // টাইম অনুযায়ী সিরিয়াল করা
    $finalData = array_values($allDataMap);

    // মেমোরি সেভ করতে ৫০০ এর বেশি হলে কেটে ফেলা
    if (count($finalData) > 500) {
        $finalData = array_slice($finalData, -500);
    }

    file_put_contents($jsonFile, json_encode($finalData));
} else {
    $finalData = $existingData;
}

// ৫. টিকার ডেটা (Price, 24h High/Low)
$tickerUrl = "https://api.bitget.com/api/v2/spot/market/tickers?symbol={$symbol}";
$ch = curl_init($tickerUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$tickerRes = json_decode(curl_exec($ch), true);
curl_close($ch);

$ticker = isset($tickerRes['data'][0]) ? $tickerRes['data'][0] : null;

// ৬. ফ্রন্টএন্ডে ডেটা পাঠানো
echo json_encode([
    'status' => 'success',
    'ticker' => $ticker,
    'candles' => $finalData,
    'data_count' => count($finalData)
]);
?>
