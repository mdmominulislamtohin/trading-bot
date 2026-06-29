<?php
// bitget/trading/smart-portfolio-strategy/sps-force-start.php

header('Content-Type: application/json');

$botFile = 'bot-list-data.txt';
$input = json_decode(file_get_contents('php://input'), true);
$botId = $input['bot_id'] ?? null;

if (!$botId || !file_exists($botFile)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request or File missing.']);
    exit;
}

$botData = json_decode(file_get_contents($botFile), true);

// ১. Spot API call kore current available balance check kora
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
    if (isset($result['status']) && $result['status'] === 'success') {
        foreach ($result['data'] as $asset) {
            $bitgetAssets[$asset['coin']] = floatval($asset['available']);
        }
    }
}

// ২. Bot khuje restart kora
$isStarted = false;
$errorMsg = "";

foreach ($botData as &$bot) {
    if ($bot['bot_id'] == $botId) {
        // Shob coin er available balance abar check kora
        foreach ($bot['coins_details'] as $coin) {
            $coinName = $coin['coin_name'];
            $reqAmt = floatval($coin['amount']);
            $availableAmt = $bitgetAssets[$coinName] ?? 0;

            if (round($availableAmt, 6) < round($reqAmt, 6)) {
                $errorMsg = "Cannot start! $coinName still has insufficient available balance.";
                break 2; // Loop theke ber hoye jabe
            }
        }

        // Jodi validation pass hoy
        $bot['status'] = 'Running';
        unset($bot['stop_reason']); // Warning/Reason muche fela
        $isStarted = true;
        break;
    }
}

if ($isStarted) {
    file_put_contents($botFile, json_encode($botData, JSON_PRETTY_PRINT));
    echo json_encode(['status' => 'success', 'message' => 'Bot successfully restarted!']);
} else {
    echo json_encode(['status' => 'error', 'message' => $errorMsg ?: 'Bot not found or validation failed.']);
}
