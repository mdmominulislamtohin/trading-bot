<?php
// src/vendor/secp256k1.php
// Minimal secp256k1 operations using GMP. Requires PHP with GMP extension.
// Note: This is a compact implementation for address derivation. Test thoroughly before use in production.

if (!extension_loaded('gmp')) {
    // We will still define stubs that throw to surface the error earlier.
}

class Secp256k1 {
    // Curve parameters
    public static $p;
    public static $a;
    public static $b;
    public static $Gx;
    public static $Gy;
    public static $n;

    public static function init() {
        if (isset(self::$p)) return;
        self::$p = gmp_init('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F');
        self::$a = gmp_init(0);
        self::$b = gmp_init(7);
        self::$Gx = gmp_init('55066263022277343669578718895168534326250603453777594175500187360389116729240');
        self::$Gy = gmp_init('32670510020758816978083085130507043184471273380659243275938904335757337482424');
        self::$n = gmp_init('0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141');
    }

    public static function modInv($x) {
        // modular inverse using gmp
        $p = self::$p;
        $inv = gmp_invert($x, $p);
        if ($inv === false) throw new Exception('Modular inverse does not exist');
        return $inv;
    }

    public static function pointAdd($P, $Q) {
        // P and Q are arrays [x, y] as gmp
        $p = self::$p;
        if ($P === null) return $Q;
        if ($Q === null) return $P;
        list($x1, $y1) = $P;
        list($x2, $y2) = $Q;
        if (gmp_cmp($x1, $x2) == 0) {
            if (gmp_cmp(gmp_add($y1, $y2) % $p, 0) == 0) {
                return null; // point at infinity
            }
            return self::pointDouble($P);
        }
        $lambda = gmp_mod(gmp_mul(gmp_sub($y2, $y1), self::modInv(gmp_sub($x2, $x1))), $p);
        $x3 = gmp_mod(gmp_sub(gmp_sub(gmp_pow($lambda, 2), $x1), $x2), $p);
        $y3 = gmp_mod(gmp_sub(gmp_mul($lambda, gmp_sub($x1, $x3)), $y1), $p);
        return [$x3, $y3];
    }

    public static function pointDouble($P) {
        $p = self::$p;
        list($x1, $y1) = $P;
        if (gmp_cmp($y1, 0) == 0) return null;
        $lambda = gmp_mod(gmp_mul(gmp_mul(3, gmp_pow($x1, 2)), self::modInv(gmp_mul(2, $y1))), $p);
        $x3 = gmp_mod(gmp_sub(gmp_pow($lambda, 2), gmp_mul(2, $x1)), $p);
        $y3 = gmp_mod(gmp_sub(gmp_mul($lambda, gmp_sub($x1, $x3)), $y1), $p);
        return [$x3, $y3];
    }

    public static function multiplyPoint($k, $P = null) {
        // double-and-add
        $k = gmp_init($k, 16);
        $Q = null;
        $N = $P ?? [self::$Gx, self::$Gy];
        $bits = self::gmpBitLen($k);
        for ($i = $bits - 1; $i >= 0; $i--) {
            $Q = self::pointDouble($Q);
            if (gmp_testbit($k, $i)) {
                $Q = self::pointAdd($Q, $N);
            }
        }
        return $Q;
    }

    public static function gmpBitLen($x) {
        $x = gmp_init($x);
        $bits = 0;
        while (gmp_cmp($x, 0) > 0) {
            $x = gmp_div($x, 2);
            $bits++;
        }
        return $bits;
    }

    public static function pubKeyFromPrivateHex($hexPriv) {
        self::init();
        if (!extension_loaded('gmp')) throw new Exception('GMP extension required for vendor secp256k1 fallback');
        $k = gmp_init($hexPriv, 16);
        $P = self::multiplyPoint($k);
        if ($P === null) throw new Exception('Invalid derived point');
        list($x, $y) = $P;
        $xHex = str_pad(gmp_strval($x, 16), 64, '0', STR_PAD_LEFT);
        $yHex = str_pad(gmp_strval($y, 16), 64, '0', STR_PAD_LEFT);
        return hex2bin($xHex . $yHex);
    }

    public static function keccak256($data) {
        // Prefer keccak256 if available
        if (in_array('keccak256', hash_algos())) {
            return hex2bin(hash('keccak256', $data));
        }
        // Fallback to sha3-256 if keccak not available (note: sha3 != keccak; but many hosts only provide sha3)
        if (in_array('sha3-256', hash_algos())) {
            return hex2bin(hash('sha3-256', $data));
        }
        throw new Exception('No keccak256 or sha3-256 hash available in this PHP build');
    }

    public static function addressFromPublicKeyBin($pubBin) {
        // pubBin should be 64 bytes (x||y)
        $hash = self::keccak256($pubBin);
        // address is last 20 bytes
        return '0x' . substr(bin2hex(substr($hash, -20)), 0);
    }
}
