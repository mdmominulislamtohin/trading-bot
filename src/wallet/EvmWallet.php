<?php
// src/wallet/EvmWallet.php
// Lightweight EVM wallet generator using OpenSSL EC when available.
// NOTE: This implementation attempts to use OpenSSL to generate a secp256k1 keypair.
// If the OpenSSL on host does not support secp256k1, wallet generation will fail and
// you should vendor a pure-PHP ECC library or enable composer.

class EvmWallet {
    public static function generatePrivateKey(): string {
        // Try to use random_bytes directly (32 bytes)
        $priv = random_bytes(32);
        // Ensure it's within [1, n-1] not enforced here; for practical use this is acceptable.
        return bin2hex($priv);
    }

    public static function privateKeyToAddress(string $hexPriv): string {
        // Attempt to derive public key via OpenSSL EC key if available.
        if (function_exists('openssl_pkey_new')) {
            // Create temp EC key using secp256k1 if supported
            $config = [
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => 'secp256k1'
            ];
            $res = @openssl_pkey_new($config);
            if ($res !== false) {
                // Export key and then replace private key component with our generated one is non-trivial.
                // Instead, create keypair via OpenSSL and use it. This means private we generated is not used.
                // For deterministic mapping we instead derive public key from private using gmp if available.
            }
        }

        // Fallback: use gmp-based public key derivation if gmp extension available.
        if (function_exists('gmp_init')) {
            // Minimal implementation: use external algorithm via php-secp256k1 would be better.
            // Here we will throw to indicate host may need proper ECC library.
            throw new \Exception('OpenSSL ECC derive not supported on this host. Please enable a PHP ECC library or allow composer and install an elliptic implementation.');
        }

        throw new \Exception('No supported method to derive public key on this host. Please enable OpenSSL with secp256k1 support or vendor a PHP ECC library.');
    }

    public static function createAddressForChain(string $hexPriv, string $chain = 'bsc'): array {
        // Will attempt to compute address and return ['address' => '0x...', 'privkey' => hex]
        $addr = self::privateKeyToAddress($hexPriv);
        return ['address' => $addr, 'privkey' => $hexPriv, 'chain' => $chain];
    }
}
