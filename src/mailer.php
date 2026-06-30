<?php
// src/mailer.php
// Simple mailer helper with optional direct SMTP support (basic AUTH LOGIN, STARTTLS).
// Falls back to PHP mail() if SMTP not configured or SMTP connection fails.

function mailer_send_smtp($host, $port, $user, $pass, $encrypt, $from, $to, $rawMessage, &$errOut = null) {
    $timeout = 10;
    $errno = 0; $errstr = '';
    $remote = $host.':'.$port;
    $sock = @stream_socket_client($remote, $errno, $errstr, $timeout);
    if (!$sock) { $errOut = "Failed connect: $errno $errstr"; return false; }
    stream_set_timeout($sock, $timeout);
    $res = fgets($sock, 512);

    $send = function($line) use ($sock) {
        fwrite($sock, $line . "\r\n");
    };

    $get = function() use ($sock) {
        $s = fgets($sock, 512);
        return $s === false ? '' : trim($s);
    };

    $helloName = gethostname() ?: 'localhost';
    $send("EHLO $helloName");
    $out = $get();
    // If STARTTLS requested
    if (strtolower($encrypt) === 'tls') {
        $send('STARTTLS');
        $line = $get();
        if (strpos($line, '220') === 0) {
            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $errOut = 'Failed to start TLS'; fclose($sock); return false;
            }
            // EHLO again
            $send("EHLO $helloName");
            $out = $get();
        }
    }

    // AUTH LOGIN
    if ($user !== null && $pass !== null && $user !== '') {
        $send('AUTH LOGIN');
        $line = $get();
        if (strpos($line, '334') !== 0) { $errOut = 'SMTP AUTH not accepted'; fclose($sock); return false; }
        $send(base64_encode($user));
        $line = $get();
        $send(base64_encode($pass));
        $line = $get();
        if (strpos($line, '235') !== 0) { $errOut = 'SMTP AUTH failed: '.$line; fclose($sock); return false; }
    }

    // Parse From/To from headers
    $matches = [];
    preg_match('/^From: .*<([^>]+)>/mi', $rawMessage, $matches);
    $mailFrom = $matches[1] ?? $from;
    preg_match('/^To: .*<([^>]+)>/mi', $rawMessage, $matches);
    $rcptTo = $matches[1] ?? $to;

    $send("MAIL FROM: <{$mailFrom}>"); $get();
    $send("RCPT TO: <{$rcptTo}>"); $get();
    $send('DATA'); $get();
    // send data, end with CRLF.CRLF
    $lines = preg_split("/\r\n|\n|\r/", $rawMessage);
    foreach ($lines as $l) {
        // Escape leading dots
        if (isset($l[0]) && $l[0] === '.') $l = '.' . $l;
        $send($l);
    }
    $send('.');
    $final = $get();
    $send('QUIT');
    fclose($sock);
    if (strpos($final, '250') === 0 || strpos($final, '354') === false) {
        // 250 ok after data
        return true;
    }
    $errOut = 'SMTP final response: ' . $final;
    return false;
}

function send_email(string $to, string $subject, string $htmlBody, string $altBody = ''): array {
    // returns ['success'=>bool,'error'=>string|null]
    $from = getenv('MAIL_FROM') ?: ('no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $fromName = getenv('MAIL_FROM_NAME') ?: ''; 

    $headers = [];
    $boundary = 'bndr_'.bin2hex(random_bytes(6));
    $headers[] = 'From: '.($fromName ? ($fromName . ' <'.$from.'>') : $from);
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/alternative; boundary="'.$boundary.'"';

    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $body .= ($altBody ?: strip_tags($htmlBody)) . "\r\n";
    $body .= "--$boundary\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $body .= $htmlBody . "\r\n";
    $body .= "--$boundary--\r\n";

    $rawMessage = 'From: ' . ($fromName ? ($fromName . ' <'.$from.'>') : $from) . "\r\n";
    $rawMessage .= 'To: ' . $to . "\r\n";
    $rawMessage .= 'Subject: ' . $subject . "\r\n";
    foreach ($headers as $h) $rawMessage .= $h . "\r\n";
    $rawMessage .= "\r\n" . $body;

    $smtpHost = getenv('SMTP_HOST');
    if ($smtpHost) {
        $smtpPort = getenv('SMTP_PORT') ?: 25;
        $smtpUser = getenv('SMTP_USER') ?: '';
        $smtpPass = getenv('SMTP_PASS') ?: '';
        $smtpEncrypt = getenv('SMTP_ENCRYPT') ?: 'none';
        $err = null;
        $ok = mailer_send_smtp($smtpHost, $smtpPort, $smtpUser, $smtpPass, $smtpEncrypt, $from, $to, $rawMessage, $err);
        if ($ok) return ['success' => true, 'error' => null];
        // fallback to mail()
        $fallback = mail($to, $subject, $body, implode("\r\n", $headers));
        if ($fallback) return ['success' => true, 'error' => null];
        return ['success' => false, 'error' => ($err ?: 'SMTP and mail() both failed')];
    }

    // No SMTP configured, use mail()
    $ok = mail($to, $subject, $body, implode("\r\n", $headers));
    if ($ok) return ['success' => true, 'error' => null];
    return ['success' => false, 'error' => 'mail() failed and SMTP not configured'];
}
