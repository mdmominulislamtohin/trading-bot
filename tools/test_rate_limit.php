<?php
// tools/test_rate_limit.php
// CLI tool to simulate repeated requests against the rate limiter via DB key

if (php_sapi_name() !== 'cli') { echo "CLI only\n"; exit(1); }
$options = getopt('', ['key::','times::','window::','max::']);
$key = $options['key'] ?? 'test:ip:127.0.0.1:login';
$times = intval($options['times'] ?? 10);
$window = intval($options['window'] ?? (getenv('RATE_LIMIT_WINDOW_SECONDS') ?: 60));
$max = intval($options['max'] ?? (getenv('RATE_LIMIT_MAX_REQUESTS') ?: 5));
require_once __DIR__ . '/../src/rate_limit.php';
for ($i=1;$i<=$times;$i++) {
    $r = rate_limit_increment($key, $max, $window);
    echo "Attempt $i => count={$r['count']} allowed=" . ($r['allowed'] ? 'yes' : 'NO') . " expires={$r['expires_at']}\n";
}
