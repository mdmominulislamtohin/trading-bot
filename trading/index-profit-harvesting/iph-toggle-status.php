<?php
// bitget/trading/index-profit-harvesting/iph-toggle-status.php
header('Content-Type: application/json');

$botFile = 'iph-bot-data.txt';

// 1. Get JSON Payload
$inputData = json_decode(file_get_contents('php://input'), true);

if (!$inputData || !isset($inputData['bot_id']) || !isset($inputData['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request payload.']);
    exit;
}

$botId = $inputData['bot_id'];
$newStatus = $inputData['status'];

// Validate allowed statuses
$allowedStatuses = ['Running', 'Paused'];
if (!in_array($newStatus, $allowedStatuses)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status requested.']);
    exit;
}

if (!file_exists($botFile)) {
    echo json_encode(['status' => 'error', 'message' => 'Bot database not found.']);
    exit;
}

// 2. Read existing data
$fileData = file_get_contents($botFile);
$bots = json_decode($fileData, true);

if (!$bots || !is_array($bots)) {
    echo json_encode(['status' => 'error', 'message' => 'Database is corrupted or empty.']);
    exit;
}

$botFound = false;

// 3. Find and Update the specific bot
foreach ($bots as &$bot) {
    if ($bot['bot_id'] === $botId) {
        // Prevent toggling a terminated bot
        if (isset($bot['status']) && $bot['status'] === 'Terminated') {
            echo json_encode(['status' => 'error', 'message' => 'Action denied! This bot is already terminated.']);
            exit;
        }
        
        $bot['status'] = $newStatus;
        $bot['last_updated'] = date('c');
        $botFound = true;
        break; // Exit loop once found
    }
}
unset($bot); // Break reference

// 4. Save and Respond
if ($botFound) {
    // Write back to file with lock to prevent race conditions
    if (file_put_contents($botFile, json_encode($bots, JSON_PRETTY_PRINT), LOCK_EX) !== false) {
        echo json_encode([
            'status' => 'success', 
            'message' => "Bot engine successfully {$newStatus}."
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to write changes to database.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Bot ID not found in database.']);
}
?>
