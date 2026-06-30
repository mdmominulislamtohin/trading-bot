<?php
// src/bignum.php
// Big-number helpers using bcmath. Canonical unit = smallest unit (wei-like integer string)

if (!extension_loaded('bcmath')) {
    error_log('bignum helper requires ext-bcmath. Please enable it.');
}

/** Convert human decimal string (e.g. "0.1") to integer string in smallest unit (wei).
 * @param string $amount human decimal string
 * @param int $decimals number of decimals (default 18)
 * @return string integer string (no decimal point)
 */
function toWei(string $amount, int $decimals = 18): string {
    if (!extension_loaded('bcmath')) throw new Exception('bcmath PHP extension required');
    $a = trim(str_replace(',', '', $amount));
    if ($a === '') return '0';
    // Ensure scale for intermediate operations
    $scale = max(0, $decimals);
    $factor = bcpow('10', (string)$decimals, 0);
    // Multiply and return integer string (scale 0)
    $wei = bcmul($a, $factor, 0);
    // strip leading + and zeros? keep as string
    if ($wei === '') $wei = '0';
    return $wei;
}

/** Convert integer string in smallest unit (wei) to human decimal string.
 * @param string $wei integer string
 * @param int $decimals number of decimals (default 18)
 * @param int $precision scale for output (default 18)
 * @return string human readable decimal string
 */
function fromWei(string $wei, int $decimals = 18, int $precision = 18): string {
    if (!extension_loaded('bcmath')) throw new Exception('bcmath PHP extension required');
    $w = preg_replace('/[^0-9\-]/', '', trim($wei));
    if ($w === '') return '0';
    $factor = bcpow('10', (string)$decimals, 0);
    $human = bcdiv($w, $factor, $precision);
    // Trim trailing zeros and decimal point
    $human = rtrim($human, '0');
    $human = rtrim($human, '.');
    if ($human === '') $human = '0';
    return $human;
}

function safeAdd(string $a, string $b, int $scale = 0): string {
    if (!extension_loaded('bcmath')) throw new Exception('bcmath PHP extension required');
    return bcadd($a, $b, $scale);
}
function safeSub(string $a, string $b, int $scale = 0): string {
    if (!extension_loaded('bcmath')) throw new Exception('bcmath PHP extension required');
    return bcsub($a, $b, $scale);
}
