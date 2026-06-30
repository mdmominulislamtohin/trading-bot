<?php
// install/helpers.php
// Helper functions for the web installer.

function check_php_extensions(): array {
    $required = ['pdo_mysql', 'json', 'openssl'];
    $optional = ['gmp', 'sodium'];
    $status = ['required' => [], 'optional' => []];
    foreach ($required as $ext) {
        $status['required'][$ext] = extension_loaded($ext);
    }
    foreach ($optional as $ext) {
        $status['optional'][$ext] = extension_loaded($ext);
    }
    return $status;
}

function generate_app_master_key(): string {
    return bin2hex(random_bytes(32));
}

function write_env_file(string $path, array $values): bool {
    $lines = [];
    foreach ($values as $k => $v) {
        // escape any newlines
        $v = str_replace("\n", "\\n", $v);
        $lines[] = "{$k}={$v}";
    }
    $content = implode("\n", $lines) . "\n";
    $res = file_put_contents($path, $content);
    if ($res === false) return false;
    @chmod($path, 0600);
    return true;
}

function run_sql_file(PDO $pdo, string $sqlPath): array {
    $sql = file_get_contents($sqlPath);
    if ($sql === false) return ['success' => false, 'error' => 'Cannot read SQL file'];
    // Split by semicolon; naive but works for our migration file
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $errors = [];
    foreach ($statements as $stmt) {
        if ($stmt === '') continue;
        try {
            $pdo->exec($stmt);
        } catch (PDOException $ex) {
            $errors[] = $ex->getMessage();
        }
    }
    return ['success' => count($errors) === 0, 'errors' => $errors];
}

function generate_base32_secret($length = 16): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $s = '';
    for ($i = 0; $i < $length; $i++) $s .= $alphabet[random_int(0, 31)];
    return $s;
}

function set_env_runtime(array $values) {
    foreach ($values as $k => $v) {
        putenv("{$k}={$v}");
        $_ENV[$k] = $v;
        $_SERVER[$k] = $v;
    }
}

