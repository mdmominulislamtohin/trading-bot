<?php
// tools/send_test_email.php
if (php_sapi_name() !== 'cli') { echo "Run from CLI: php tools/send_test_email.php --to=you@domain.tld\n"; exit(1); }
$options = getopt('', ['to:']);
$to = $options['to'] ?? null;
if (!$to) { echo "Usage: php tools/send_test_email.php --to=you@domain.tld\n"; exit(1); }
require_once __DIR__ . '/../src/mailer.php';
$subject = 'Test email from Trading Bot';
$body = '<p>This is a test email sent at '.date('c').'</p>';
$res = send_email($to, $subject, $body, strip_tags($body));
if ($res['success']) echo "Email sent OK to {$to}\n";
else echo "Email failed: " . ($res['error'] ?? 'unknown') . "\n";
