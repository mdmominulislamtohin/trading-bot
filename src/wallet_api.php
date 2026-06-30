<?php
// src/wallet_api.php
// Optional helpers to fetch on-chain balances if API keys are configured.

function get_wallet_balance(string $chain, string $address) {
    // Returns balance in native token as string or null if not available
    $chain = strtolower($chain);
    if ($chain === 'bsc') {
        $api = getenv('BSCSCAN_API_KEY');
        if (!$api) return null;
        $url = "https://api.bscscan.com/api?module=account&action=balance&address={$address}&tag=latest&apikey={$api}";
        $res = @file_get_contents($url);
        if (!$res) return null;
        $json = json_decode($res, true);
        if (!isset($json['result'])) return null;
        // BSC/Ethereum balance returned in wei; convert to human readable (BN handling not implemented here)
        return $json['result'];
    }
    if ($chain === 'arbitrum') {
        $api = getenv('ARBISCAN_API_KEY');
        if (!$api) return null;
        $url = "https://api.arbiscan.io/api?module=account&action=balance&address={$address}&tag=latest&apikey={$api}";
        $res = @file_get_contents($url);
        if (!$res) return null;
        $json = json_decode($res, true);
        if (!isset($json['result'])) return null;
        return $json['result'];
    }
    return null;
}
