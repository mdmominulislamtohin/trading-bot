<?php
// src/wallet/EvmWallet.php
// Updated to use vendor Secp256k1 fallback when OpenSSL derivation not available.
require_once __DIR__ . '/vendor/secp256k1.php';

class EvmWallet {
    public static function generatePrivateKey(): string {
        $priv = random_bytes(32);
        return bin2hex($priv);
    }

    public static function privateKeyToPublicKeyBin(string $hexPriv): string {
        // Try vendor GMP implementation
        try {
            return Secp256k1::pubKeyFromPrivateHex($hexPriv);
        } catch (Exception $e) {
            throw new Exception('Failed to derive public key: ' . $e->getMessage());
        }
    }

    public static function privateKeyToAddress(string $hexPriv): string {
        $pubBin = self::privateKeyToPublicKeyBin($hexPriv);
        // pubBin is x||y (64 bytes)
        if (strlen($pubBin) !== 64) {
            // if vendor returns 64-byte binary, ok; otherwise try trimming
        }
        $addr = Secp256k1::addressFromPublicKeyBin($pubBin);
        return strtolower($addr);
    }

    public static function createAddressForChain(string $hexPriv, string $chain = 'bsc'): array {
        $address = self::privateKeyToAddress($hexPriv);
        return ['address' => $address, 'privkey' => $hexPriv, 'chain' => $chain];
    }
}
