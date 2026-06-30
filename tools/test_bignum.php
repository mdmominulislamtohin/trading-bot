<?php
// tools/test_bignum.php - simple CLI test for bignum helpers
if (php_sapi_name() !== 'cli') { echo "CLI only\n"; exit(1); }
require_once __DIR__ . '/../src/bignum.php';
$cases = [
    ['0.1','100000000000000000'],
    ['1','1000000000000000000'],
    ['0','0'],
    ['12345.6789','12345678900000000000000000000000000'],
];
foreach ($cases as $c) {
    $human = $c[0]; $expect = $c[1];
    try {
        $wei = toWei($human, 18);
        $back = fromWei($wei,18,18);
        echo "human={$human} -> wei={$wei} back={$back} (expected start approx={$human})\n";
        if ($wei !== $expect) echo "  NOTE: expected wei={$expect} but got {$wei}\n";
    } catch (Exception $e) { echo "Error: {$e->getMessage()}\n"; }
}
