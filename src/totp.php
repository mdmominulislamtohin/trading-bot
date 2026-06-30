<?php
// src/totp.php
// Simple TOTP (RFC6238) helpers with base32 secret generation and verification.

if (!extension_loaded('hash')) {
    error_log('TOTP helper requires hash extension');
}

function base32_encode($input) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = 0; $bitBuffer = 0; $output = '';
    foreach (str_split($input) as $c) {
        $bitBuffer = ($bitBuffer << 8) | ord($c);
        $bits += 8;
        while ($bits >= 5) {
            $bits -= 5;
            $output .= $alphabet[($bitBuffer >> $bits) & 31];
        }
    }
    if ($bits > 0) {
        $output .= $alphabet[($bitBuffer << (5 - $bits)) & 31];
    }
    while (strlen($output) % 8 !== 0) $output .= '=';
    return $output;
}

function base32_decode($input) {
    $input = strtoupper($input);
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $input = preg_replace('/[^A-Z2-7]/', '', $input);
    $bits = 0; $bitBuffer = 0; $output = '';
    foreach (str_split($input) as $c) {
        $bitBuffer = ($bitBuffer << 5) | strpos($alphabet, $c);
        $bits += 5;
        if ($bits >= 8) {
            $bits -= 8;
            $output .= chr(($bitBuffer >> $bits) & 0xFF);
        }
    }
    return $output;
}

function generateSecret($length = 16) {
    $bytes = random_bytes($length);
    return rtrim(base32_encode($bytes), '=');
}

function getTotpCode($secret, $timeSlice = null, $digits = 6) {
    if ($timeSlice === null) $timeSlice = floor(time() / 30);
    $secretKey = base32_decode($secret);
    $time = pack('N*', 0) . pack('N*', $timeSlice);
    $hash = hash_hmac('sha1', $time, $secretKey, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $truncatedHash = substr($hash, $offset, 4);
    $value = unpack('N', $truncatedHash);
    $value = $value[1] & 0x7FFFFFFF;
    $modulo = pow(10, $digits);
    return str_pad($value % $modulo, $digits, '0', STR_PAD_LEFT);
}

function verifyTotp($secret, $code, $window = 1) {
    $code = trim($code);
    for ($i = -$window; $i <= $window; $i++) {
        $calc = getTotpCode($secret, floor(time()/30) + $i);
        if (hash_equals($calc, $code)) return true;
    }
    return false;
}

function getOtpAuthUrl($issuer, $user, $secret) {
    $issuerEnc = rawurlencode($issuer);
    $label = rawurlencode($issuer . ':' . $user);
    return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuerEnc}&algorithm=SHA1&digits=6&period=30";
}
