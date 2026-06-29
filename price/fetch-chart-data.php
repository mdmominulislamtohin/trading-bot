<?php
// fetch-chart-data.php

// ক্রন জবের জন্য টাইমআউট লিমিট আনলিমিটেড করে দেওয়া হলো
set_time_limit(0);

// ডেটা সেভ করার জন্য JSON ফাইলের পাথ
$jsonFilePath = __DIR__ . '/chart-data.json';

// আপনার টার্গেট কয়েন লিস্ট (যেগুলো ইনডেক্স পেজে আছে)
$targetCoins = ['BTC', 'ETH', 'LTC', 'XRP', 'SOL', 'ADA', 'LINK', 'AVAX', 'DOGE', 'BNB', 'SUI', 'PAXG', 'SLVON'];

// ক্যান্ডেলের টাইমফ্রেম (1h = ১ ঘণ্টার ক্যান্ডেল, আপনি চাইলে '15m', '4h', '1day' দিতে পারেন)
$granularity = '1h'; 
$limit = 200; // একসাথে কতগুলো ক্যান্ডেল আনবে (Bitget-এর লিমিট সাধারণত ১০০০ পর্যন্ত হয়)

// আগে থেকে সেভ করা কোনো JSON ডেটা থাকলে সেটি রিড করা
$existingData = [];
if (file_exists($jsonFilePath)) {
    $fileContent = file_get_contents($jsonFilePath);
    if ($fileContent) {
        $existingData = json_decode($fileContent, true);
    }
}

$updatedSymbols = 0;

foreach ($targetCoins as $coin) {
    $symbol = $coin . 'USDT';
    
    // Bitget V2 Candlestick API Endpoint
    $url = "https://api.bitget.com/api/v2/spot/market/candles?symbol={$symbol}&granularity={$granularity}&limit={$limit}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        
        if (isset($data['code']) && $data['code'] === '00000' && isset($data['data'])) {
            $newCandles = $data['data'];
            
            // কয়েনের ডেটা আগে থেকে না থাকলে খালি অ্যারে তৈরি করা
            if (!isset($existingData[$symbol])) {
                $existingData[$symbol] = [];
            }
            
            // ডুপ্লিকেট ডেটা এড়ানোর জন্য টাইমস্ট্যাম্প অনুযায়ী ডেটা সাজানো
            $timestampMap = [];
            
            // ১. পুরোনো ডেটা ম্যাপে ঢোকানো
            foreach ($existingData[$symbol] as $candle) {
                $timestampMap[$candle[0]] = $candle; // $candle[0] হলো টাইমস্ট্যাম্প (Timestamp)
            }
            
            // ২. নতুন ফেচ করা ডেটা ম্যাপে আপডেট করা (এতে পুরোনো ডেটার সাথে নতুন ডেটা ওভাররাইট হয়ে লেটেস্ট থাকবে)
            foreach ($newCandles as $candle) {
                $timestampMap[$candle[0]] = $candle; 
            }
            
            // ৩. ম্যাপ থেকে আবার অ্যারে তৈরি করা এবং টাইমস্ট্যাম্প অনুযায়ী (পুরোনো থেকে নতুন) সর্ট করা
            $mergedCandles = array_values($timestampMap);
            usort($mergedCandles, function($a, $b) {
                return $a[0] - $b[0]; // Ascending order
            });
            
            $existingData[$symbol] = $mergedCandles;
            $updatedSymbols++;
        }
    }
    
    // API রেট-লিমিট (Rate Limit) এড়ানোর জন্য প্রতিটি রিকোয়েস্টের মাঝে হালকা বিরতি (০.২ সেকেন্ড)
    usleep(200000); 
}

// আপডেট করা ডেটা পুনরায় JSON ফাইলে সেভ করা
if ($updatedSymbols > 0) {
    $jsonData = json_encode($existingData);
    file_put_contents($jsonFilePath, $jsonData);
    echo json_encode([
        'status' => 'success',
        'message' => "Successfully updated data for {$updatedSymbols} symbols at " . date('Y-m-d H:i:s')
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch any new data from Bitget API.'
    ]);
}
?>
