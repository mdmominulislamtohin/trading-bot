<?php
// src/totp.php
// Minimal TOTP (RFC6238) implementation. Works without composer.

function base32_decode($b32) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper($b32);
    $l = strlen($b32);
    $n = 0;
    $j = 0;
    $binary = '';

    for ($i = 0; $i < $l; $i++) {
        $n = $n << 5;
        $n = $n + strpos($alphabet, $b32[$i]);
        $j += 5;
        if ($j >= 8) {
            $j -= 8;
            $binary .= chr(($n & (0xFF << $j)) >> $j);
        }
    }
    return $binary;
}

function totp_now($secret, $digits = 6, $period = 30) {
    $key = base32_decode($secret);
    $time = floor(time() / $period);
    $timeBytes = pack('N*', 0) . pack('N*', $time);
    $hash = hash_hmac('sha1', $timeBytes, $key, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $truncated = substr($hash, $offset, 4);
    $code = unpack('N', $truncated)[1] & 0x7FFFFFFF;
    return str_pad($code % pow(10, $digits), $digits, '0', STR_PAD_LEFT);
}

function verify_totp($secret, $code, $window = 1, $digits = 6, $period =30) {
    for ($i = -$window; $i <= $window; $i++) {
        $t = floor((time() + ($i * $period)) / $period);
        $timeBytes = pack('N*', 0) . pack('N*', $t);
        $key = base32_decode($secret);
        $hash = hash_hmac('sha1', $timeBytes, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated = substr($hash, $offset, 4);
        $calc = unpack('N', $truncated)[1] & 0x7FFFFFFF;
        $candidate = str_pad($calc % pow(10, $digits), $digits, '0', STR_PAD_LEFT);
        if (hash_equals($candidate, $code)) return true;
    }
    return false;
}
