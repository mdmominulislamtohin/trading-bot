<?php
// assets/spot-api.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ১. সেটিংস ফাইলটি যুক্ত করা (যেহেতু এটি assets ফোল্ডারে, তাই ../ দিয়ে এক ফোল্ডার ব্যাকে গিয়ে settings-এ ঢুকতে হবে)
require_once '../settings/api-settings.php';

// ২. Bitget API Endpoint
$method = 'GET';
$requestPath = '/api/v2/spot/account/assets';
$url = $baseUrl . $requestPath;

// ৩. Authentication-এর জন্য Timestamp তৈরি করা (মিলিসেকেন্ডে)
$timestamp = (string)(round(microtime(true) * 1000));

// ৪. Signature তৈরি করা (Bitget-এর নিয়ম অনুযায়ী: timestamp + method + requestPath)
$body = ""; // GET রিকোয়েস্টে বডি ফাঁকা থাকে
$message = $timestamp . $method . $requestPath . $body;
$signature = base64_encode(hash_hmac('sha256', $message, $secretKey, true));

// ৫. Headers সেট করা (এখানে আপনার API Key, Passphrase এবং তৈরি করা Signature পাঠাতে হবে)
$headers = [
    "ACCESS-KEY: " . $apiKey,
    "ACCESS-SIGN: " . $signature,
    "ACCESS-TIMESTAMP: " . $timestamp,
    "ACCESS-PASSPHRASE: " . $passphrase,
    "Content-Type: application/json",
    "locale: en-US"
];

// ৬. cURL রিকোয়েস্ট
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

// ৭. ডেটা প্রসেসিং
if ($response) {
    $result = json_decode($response, true);
    
    // API যদি সফলভাবে রেসপন্স দেয়
    if (isset($result['code']) && $result['code'] === '00000' && isset($result['data'])) {
        
        $myAssets = [];
        
        // শুধুমাত্র যেসব কয়েনে ব্যালেন্স আছে, সেগুলো ফিল্টার করা
        foreach ($result['data'] as $asset) {
            $available = floatval($asset['available']);
            $frozen = floatval($asset['frozen']); // অর্ডারে আটকে থাকা ব্যালেন্স
            
            if ($available > 0 || $frozen > 0) {
                // আমরা কয়েনের আইকনের লিংকও সাথে পাঠিয়ে দিচ্ছি, যেন ফ্রন্টএন্ডে সুন্দর দেখায়
                $asset['icon'] = "https://assets.coincap.io/assets/icons/" . strtolower($asset['coin']) . "@2x.png";
                $myAssets[] = $asset;
            }
        }
        
        echo json_encode([
            'status' => 'success',
            'data' => $myAssets
        ]);
        exit;
    } else {
        // API Key ভুল থাকলে বা অন্য কোনো এরর হলে
        echo json_encode([
            'status' => 'error',
            'message' => isset($result['msg']) ? $result['msg'] : 'Bitget API Error'
        ]);
        exit;
    }
}

echo json_encode([
    'status' => 'error',
    'message' => 'Failed to connect to server'
]);
?>
