<?php
// tools/test_csrf.php
// Usage: php tools/test_csrf.php --url="http://localhost" --endpoint="/auth/register.php"
if (php_sapi_name() !== 'cli') { echo "CLI only\n"; exit(1); }
$options = getopt('', ['url:', 'endpoint::']);
$base = rtrim($options['url'] ?? getenv('TEST_BASE_URL') ?? '', '/');
$endpoint = $options['endpoint'] ?? '/auth/register.php';
if (!$base) { echo "Provide --url or set TEST_BASE_URL env var\n"; exit(1); }
$cookie = sys_get_temp_dir() . '/csrf_test_cookie.txt';

function curl_get($url, $cookie) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return [$res,$info];
}

function curl_post($url, $postFields, $cookie, $includeHeaders=false) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    if ($includeHeaders) curl_setopt($ch, CURLOPT_HEADER, true);
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return [$res,$info];
}

echo "Fetching form to obtain CSRF token...\n";
list($html,$info) = curl_get($base . $endpoint, $cookie);
if ($info['http_code'] >= 400) { echo "GET failed: HTTP {$info['http_code']}\n"; exit(1); }
// extract token
$token = null;
if (preg_match('/name=["\']?_csrf["\']?\s+value=["\']([^"']+)["\']/i', $html, $m)) {
    $token = $m[1];
} elseif (preg_match('/name="_csrf" value="([^"]+)"/i', $html, $m)) {
    $token = $m[1];
}
if (!$token) { echo "Could not find _csrf token in form HTML.\n"; exit(1); }
echo "Token found: $token\n";

// Attempt POST without token
echo "Posting WITHOUT token...\n";
$post = ['name'=>'csrf-test','email'=>'csrf-test@example.com','password'=>'secret123'];
list($r1,$i1) = curl_post($base . $endpoint, $post, $cookie);
echo "HTTP: {$i1['http_code']}\n";
echo "Response snippet: ".substr(trim($r1),0,200)."\n";

// Attempt POST with token
echo "Posting WITH token...\n";
$post['_csrf'] = $token;
list($r2,$i2) = curl_post($base . $endpoint, $post, $cookie);
echo "HTTP: {$i2['http_code']}\n";
echo "Response snippet: ".substr(trim($r2),0,200)."\n";

// Cleanup
@unlink($cookie);
