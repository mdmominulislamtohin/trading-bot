<?php
// src/crypto.php
// Encryption helpers: try libsodium first, fallback to openssl AES-256-GCM.

function get_master_key_raw(): string {
    $k = getenv('APP_MASTER_KEY');
    if (!$k) throw new \Exception('Missing APP_MASTER_KEY in environment');
    return $k;
}

function derive_key_bytes(string $master): string {
    // Derive a 32-byte key from the master secret using hash
    return hash('sha256', $master, true);
}

function encrypt_secret(string $plaintext): string {
    // Return a single string encoding version|iv|ciphertext|tag (base64 parts)
    $master = get_master_key_raw();
    $key = derive_key_bytes($master);

    if (function_exists('sodium_crypto_aead_xchacha20poly1305_ietf_encrypt')) {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $cipher = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($plaintext, '', $nonce, $key);
        return 'v1sodium|' . base64_encode($nonce) . '|' . base64_encode($cipher);
    }

    // Fallback to OpenSSL AES-256-GCM
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($ciphertext === false) throw new \Exception('Encryption failed');
    return 'v1openssl|' . base64_encode($iv) . '|' . base64_encode($ciphertext) . '|' . base64_encode($tag);
}

function decrypt_secret(string $stored): string {
    $master = get_master_key_raw();
    $key = derive_key_bytes($master);

    if (strpos($stored, 'v1sodium|') === 0) {
        list(, $bnonce, $bcipher) = explode('|', $stored, 3);
        $nonce = base64_decode($bnonce);
        $cipher = base64_decode($bcipher);
        $plain = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipher, '', $nonce, $key);
        if ($plain === false) throw new \Exception('Decryption failed');
        return $plain;
    }

    if (strpos($stored, 'v1openssl|') === 0) {
        list(, $biv, $bcipher, $btag) = explode('|', $stored, 4);
        $iv = base64_decode($biv);
        $cipher = base64_decode($bcipher);
        $tag = base64_decode($btag);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plain === false) throw new \Exception('Decryption failed');
        return $plain;
    }

    throw new \Exception('Unknown encryption version');
}
