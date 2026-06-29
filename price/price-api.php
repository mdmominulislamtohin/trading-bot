<?php
// price-api.php
error_reporting(0);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$targetCoins = ['BTC', 'ETH', 'LTC', 'XRP', 'SOL', 'ADA', 'LINK', 'AVAX', 'DOGE', 'BNB', 'SUI', 'WLD', 'ARB', 'PAXG', 'SLVON'];
$targetSymbols = array_map(function($coin) {
    return $coin . 'USDT';
}, $targetCoins);

$url = "https://api.bitget.com/api/v2/spot/market/tickers";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $data = json_decode($response, true);
    if (isset($data['code']) && $data['code'] === '00000' && isset($data['data'])) {
        $filteredData = [];
        foreach ($data['data'] as $ticker) {
            if (in_array($ticker['symbol'], $targetSymbols)) {
                $baseCoin = str_replace('USDT', '', $ticker['symbol']);
                $ticker['icon'] = "https://assets.coincap.io/assets/icons/" . strtolower($baseCoin) . "@2x.png";
                $filteredData[] = $ticker;
            }
        }
        echo json_encode(['status' => 'success', 'data' => $filteredData]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Failed to fetch data from Bitget']);
?>
