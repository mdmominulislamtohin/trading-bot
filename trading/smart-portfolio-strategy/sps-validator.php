<?php
// bitget/trading/smart-portfolio-strategy/sps-validator.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$botFile = 'bot-list-data.txt';

if (!file_exists($botFile)) {
    echo json_encode(['status' => 'error', 'message' => 'Bot data file not found.']);
    exit;
}

$botData = json_decode(file_get_contents($botFile), true);

if (!$botData) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data in bot file.']);
    exit;
}

// ১. নিজস্ব spot-api.php কে কল করা
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseDir = dirname(dirname($scriptDir));

$spotApiUrl = $protocol . $host . $baseDir . '/assets/spot-api.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $spotApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$bitgetAssets = [];

if ($response) {
    $result = json_decode($response, true);
    
    if (isset($result['status']) && $result['status'] === 'success' && isset($result['data'])) {
        foreach ($result['data'] as $asset) {
            // Available এবং Frozen আলাদাভাবে সেভ করে রাখা হচ্ছে
            $bitgetAssets[$asset['coin']] = [
                'available' => floatval($asset['available']),
                'frozen' => floatval($asset['frozen'])
            ];
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to get data from local Spot API.']);
        exit;
    }
} else {
     echo json_encode(['status' => 'error', 'message' => 'Failed to connect to local Spot API.']);
     exit;
}

// ২. অ্যাডভান্সড ভ্যালিডেশন লজিক
$isUpdated = false;
$stoppedCount = 0;

foreach ($botData as &$bot) {
    if ($bot['status'] === 'Running') {
        
        foreach ($bot['coins_details'] as $coin) {
            $coinName = $coin['coin_name'];
            $requiredAmount = floatval($coin['amount']);
            
            // রুল ১: কয়েনটি স্পট ওয়ালেটে আদৌ আছে কি না?
            if (!isset($bitgetAssets[$coinName])) {
                $bot['status'] = 'Stopped';
                $bot['stop_reason'] = "Missing Asset: $coinName is totally missing from your Spot Wallet.";
                $isUpdated = true;
                $stoppedCount++;
                break; 
            }
            
            $availableAmount = $bitgetAssets[$coinName]['available'];
            $frozenAmount = $bitgetAssets[$coinName]['frozen'];
            $totalAmount = $availableAmount + $frozenAmount;
            
            // রুল ২: Available ব্যালেন্স কি বটের জন্য প্রয়োজনীয় অ্যামাউন্টের চেয়ে কম?
            if (round($availableAmount, 6) < round($requiredAmount, 6)) {
                $bot['status'] = 'Stopped';
                
                // রুল ৩: চেক করা, ব্যালেন্স কি পুরোপুরি শর্ট, নাকি Locked/Frozen অবস্থায় আছে?
                if (round($totalAmount, 6) >= round($requiredAmount, 6)) {
                    $bot['stop_reason'] = "Funds Locked: You have $coinName, but the required amount is currently Locked/Frozen in open orders.";
                } else {
                    $bot['stop_reason'] = "Insufficient Available Balance: $coinName required " . round($requiredAmount, 6) . " but found only " . round($availableAmount, 6) . " available.";
                }
                
                $isUpdated = true;
                $stoppedCount++;
                break; // লুপ ব্রেক (একটি এরর পেলেই বট স্টপ হবে)
            }
        }
    }
}

// ৩. ফাইল সেভ ও রেসপন্স
if ($isUpdated) {
    file_put_contents($botFile, json_encode($botData, JSON_PRETTY_PRINT));
    echo json_encode(['status' => 'success', 'message' => "Validation Complete. $stoppedCount bot(s) stopped due to Missing or Locked assets."]);
} else {
    echo json_encode(['status' => 'success', 'message' => "Validation Complete. All assets are fully Available and securely running."]);
}
?>
