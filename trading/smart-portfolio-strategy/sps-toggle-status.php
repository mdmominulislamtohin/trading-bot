<?php
// bitget/trading/smart-portfolio-strategy/sps-toggle-status.php

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['bot_id']) || !isset($input['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request.']);
    exit;
}

$botId = $input['bot_id'];
$newStatus = $input['status'];
$botFile = 'bot-list-data.txt';

if (!file_exists($botFile)) {
    echo json_encode(['status' => 'error', 'message' => 'Bot file not found.']);
    exit;
}

$botData = json_decode(file_get_contents($botFile), true);
$updated = false;

foreach ($botData as &$bot) {
    if ($bot['bot_id'] === $botId) {
        $bot['status'] = $newStatus;
        $bot['last_updated'] = date('c');
        $updated = true;
        break;
    }
}

if ($updated) {
    file_put_contents($botFile, json_encode($botData, JSON_PRETTY_PRINT));
    echo json_encode(['status' => 'success', 'message' => "Bot status changed to $newStatus"]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Bot not found.']);
}
?>
