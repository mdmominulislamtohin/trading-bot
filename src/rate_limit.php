<?php
// src/rate_limit.php
// Simple DB-backed rate limiter using the rate_limits table.

function rate_limit_get_pdo() {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME').';charset=utf8mb4', getenv('DB_USER'), getenv('DB_PASS'));
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

/**
 * Increment rate counter for a key and return status.
 * Uses INSERT ... ON DUPLICATE KEY UPDATE to atomically reset when window expired.
 *
 * @param string $key unique key (e.g. 'ip:1.2.3.4:login')
 * @param int $max max allowed within window
 * @param int $window seconds window length
 * @return array ['allowed'=>bool,'count'=>int,'max'=>int,'expires_at'=>string]
 */
function rate_limit_increment(string $key, int $max = 60, int $window = 60): array {
    $pdo = rate_limit_get_pdo();
    // Insert or update atomically: if expired reset to 1, else increment
    $sql = "INSERT INTO rate_limits (`key`, `count`, expires_at) VALUES (:k, 1, DATE_ADD(NOW(), INTERVAL :w SECOND))
        ON DUPLICATE KEY UPDATE `count` = IF(expires_at < NOW(), 1, `count` + 1), expires_at = IF(expires_at < NOW(), DATE_ADD(NOW(), INTERVAL :w SECOND), expires_at)";
    $st = $pdo->prepare($sql);
    $st->bindValue(':k', $key);
    $st->bindValue(':w', (int)$window, PDO::PARAM_INT);
    $st->execute();

    // read current value
    $q = $pdo->prepare('SELECT `count`, expires_at FROM rate_limits WHERE `key` = ? LIMIT 1');
    $q->execute([$key]);
    $r = $q->fetch(PDO::FETCH_ASSOC);
    $count = $r ? intval($r['count']) : 0;
    $expires = $r['expires_at'] ?? null;
    $allowed = $count <= $max;
    return ['allowed'=>$allowed,'count'=>$count,'max'=>$max,'expires_at'=>$expires];
}

function rate_limit_check_or_block(string $key, int $max = 60, int $window = 60) {
    $res = rate_limit_increment($key, $max, $window);
    if (!$res['allowed']) {
        http_response_code(429);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Too many requests. Try again later.";
        error_log("Rate limit exceeded for {$key} (count={$res['count']}, max={$res['max']})");
        exit;
    }
    return $res;
}
