<?php
/**
 * Gmail OTP engine: generation, storage, verification, delivery.
 *
 * Security notes
 *  - Only a bcrypt hash of the code is stored (otp_codes.code_hash).
 *  - Codes expire after OTP_TTL_MINUTES and are single use.
 *  - At most OTP_MAX_ATTEMPTS wrong guesses per code.
 *  - At most OTP_MAX_SENDS codes per email+purpose inside the resend window.
 *  - The plain code only ever exists in this request's memory, in the Gmail
 *    preview returned to the client when no mail server is configured, and in
 *    the delivered message. Once a real send succeeds the code is stripped
 *    from the API response.
 *
 * Delivery order: SMTP (includes/mail_config.php or SMTP_* env vars)
 *                 -> PHP mail() -> preview only.
 * Whatever happens, the caller receives a status + human readable message so
 * the UI can show a real error instead of failing silently.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('OTP_TTL_MINUTES'))           define('OTP_TTL_MINUTES', 10);
if (!defined('OTP_MAX_ATTEMPTS'))          define('OTP_MAX_ATTEMPTS', 5);
if (!defined('OTP_RESEND_WINDOW_MIN'))     define('OTP_RESEND_WINDOW_MIN', 10);
if (!defined('OTP_MAX_SENDS'))             define('OTP_MAX_SENDS', 3);
if (!defined('OTP_SESSION_TTL'))           define('OTP_SESSION_TTL', 900);

const OTP_PURPOSES = ['register', 'reset'];

/** Domains accepted for a NEW account (the flow is a "Gmail OTP" flow). */
const OTP_GMAIL_DOMAINS = ['gmail.com', 'googlemail.com'];

/* ------------------------------------------------------------------ */
/* Validation helpers                                                  */
/* ------------------------------------------------------------------ */

/**
 * Strict address syntax: single @, no empty/dotted local part, a dotted
 * domain whose TLD is at least two letters. Rejects what plain
 * FILTER_VALIDATE_EMAIL happily accepts, e.g. "a@b.c" or "you@gmail.c".
 */
function otp_valid_email($email)
{
    if (!is_string($email)) {
        return false;
    }
    $email = strtolower(trim($email));
    if ($email === '' || strlen($email) > 254 || substr_count($email, '@') !== 1) {
        return false;
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    list($local, $domain) = explode('@', $email, 2);

    if ($local === '' || strlen($local) > 64) {
        return false;
    }
    if (strpos($local, '..') !== false || $local[0] === '.' || substr($local, -1) === '.') {
        return false;
    }
    if (!preg_match('/^(?:[a-z0-9]|[a-z0-9][a-z0-9._%+\-]*[a-z0-9])$/', $local)) {
        return false;
    }
    // Domain needs at least one dot and a TLD of two or more letters.
    if (!preg_match('/^(?:[a-z0-9]([a-z0-9\-]*[a-z0-9])?\.)+[a-z]{2,}$/', $domain)) {
        return false;
    }
    return true;
}

/** Gmail addresses only (used when creating a new account). */
function otp_is_gmail($email)
{
    $domain = substr(strrchr((string)$email, '@'), 1);
    return in_array(strtolower($domain), OTP_GMAIL_DOMAINS, true);
}

/** Google treats googlemail.com as gmail.com - store one canonical address. */
function otp_normalize_email($email)
{
    $email = strtolower(trim((string)$email));
    $at = strrpos($email, '@');
    if ($at !== false && substr($email, $at) === '@googlemail.com') {
        return substr($email, 0, $at) . '@gmail.com';
    }
    return $email;
}

/** Purpose aware gate: new accounts must be Gmail, resets may be any provider. */
function otp_email_allowed($email, $purpose)
{
    if (!otp_valid_email($email)) {
        return false;
    }
    if ($purpose === 'register' && !otp_is_gmail($email)) {
        return false;
    }
    return true;
}

function otp_email_error($email, $purpose)
{
    if (!otp_valid_email($email)) {
        return 'Please enter a valid email address, for example you@gmail.com.';
    }
    if ($purpose === 'register' && !otp_is_gmail($email)) {
        return 'Registration is only possible with a Gmail address, for example you@gmail.com.';
    }
    return null;
}

function otp_valid_purpose($purpose)
{
    return in_array($purpose, OTP_PURPOSES, true);
}

function otp_purpose_label($purpose)
{
    return $purpose === 'register' ? 'account registration' : 'password reset';
}

/* ------------------------------------------------------------------ */
/* Database                                                            */
/* ------------------------------------------------------------------ */

/** Returns null when a code may be sent, otherwise a user-facing error. */
function otp_rate_limit_error($conn, $email, $purpose)
{
    try {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT COUNT(*) AS c FROM otp_codes
              WHERE email = ? AND purpose = ?
                AND created_at > NOW() - INTERVAL " . OTP_RESEND_WINDOW_MIN . " MINUTE"
        );
        mysqli_stmt_bind_param($stmt, "ss", $email, $purpose);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $count = (int)($row['c'] ?? 0);
    } catch (Exception $e) {
        return null; // never block the user because of a counter problem
    }

    if ($count >= OTP_MAX_SENDS) {
        return 'Too many codes requested for this Gmail address. Please wait '
            . OTP_RESEND_WINDOW_MIN . ' minutes and try again.';
    }
    return null;
}

/** Invalidate every outstanding code for this email + purpose. */
function otp_invalidate($conn, $email, $purpose)
{
    try {
        $stmt = mysqli_prepare($conn, "DELETE FROM otp_codes WHERE email = ? AND purpose = ?");
        mysqli_stmt_bind_param($stmt, "ss", $email, $purpose);
        mysqli_stmt_execute($stmt);
    } catch (Exception $e) {
        // best effort
    }
}

/**
 * Create and store a new code.
 * @return array [code => string, expires_in => int seconds]
 */
function otp_create($conn, $email, $purpose)
{
    $code = (string)random_int(100000, 999999);
    $hash = password_hash($code, PASSWORD_DEFAULT);

    otp_invalidate($conn, $email, $purpose);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO otp_codes (email, purpose, code_hash, attempts, consumed, expires_at)
         VALUES (?,?,?,0,0, DATE_ADD(NOW(), INTERVAL " . OTP_TTL_MINUTES . " MINUTE))"
    );
    mysqli_stmt_bind_param($stmt, "sss", $email, $purpose, $hash);
    mysqli_stmt_execute($stmt);

    return ['code' => $code, 'expires_in' => OTP_TTL_MINUTES * 60];
}

/**
 * Verify a submitted code.
 * @return array [ok => bool, error => string|null]
 */
function otp_verify_code($conn, $email, $purpose, $code)
{
    $code = trim((string)$code);
    if (!preg_match('/^\d{6}$/', $code)) {
        return ['ok' => false, 'error' => 'Enter the 6 digit code from your Gmail.'];
    }

    try {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, code_hash, attempts, consumed, expires_at,
                    TIMESTAMPDIFF(SECOND, NOW(), expires_at) AS secs_left
               FROM otp_codes
              WHERE email = ? AND purpose = ?
              ORDER BY id DESC LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "ss", $email, $purpose);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    } catch (Exception $e) {
        return ['ok' => false, 'error' => 'Could not check the code. Please request a new one.'];
    }

    if (!$row) {
        return ['ok' => false, 'error' => 'No code found for this Gmail address. Please send a new code.'];
    }
    if ((int)$row['consumed'] === 1) {
        return ['ok' => false, 'error' => 'This code was already used. Please send a new code.'];
    }
    if ((float)$row['secs_left'] <= 0) {
        return ['ok' => false, 'error' => 'This code expired. Please send a new code.'];
    }
    if ((int)$row['attempts'] >= OTP_MAX_ATTEMPTS) {
        return ['ok' => false, 'error' => 'Too many wrong attempts. Please send a new code.'];
    }

    if (!password_verify($code, $row['code_hash'])) {
        $newAttempts = (int)$row['attempts'] + 1;
        $left = OTP_MAX_ATTEMPTS - $newAttempts;
        try {
            $upd = mysqli_prepare($conn, "UPDATE otp_codes SET attempts = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "ii", $newAttempts, $row['id']);
            mysqli_stmt_execute($upd);
        } catch (Exception $e) {
            // ignore
        }
        $msg = 'Incorrect code.';
        if ($left > 0) {
            $msg .= ' ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' left.';
        }
        return ['ok' => false, 'error' => $msg];
    }

    try {
        $upd = mysqli_prepare($conn, "UPDATE otp_codes SET consumed = 1 WHERE id = ?");
        mysqli_stmt_bind_param($upd, "i", $row['id']);
        mysqli_stmt_execute($upd);
    } catch (Exception $e) {
        return ['ok' => false, 'error' => 'Could not confirm the code. Please try again.'];
    }

    return ['ok' => true, 'error' => null];
}

/* ------------------------------------------------------------------ */
/* Session flags (bind a verified code to this browser session)        */
/* ------------------------------------------------------------------ */

function otp_session_mark($purpose, $email)
{
    $_SESSION['otp_verified'][$purpose] = [
        'email' => strtolower(trim($email)),
        'ts'    => time(),
    ];
}

function otp_session_check($purpose, $email)
{
    $flag = $_SESSION['otp_verified'][$purpose] ?? null;
    if (!is_array($flag)) {
        return false;
    }
    if (time() - (int)($flag['ts'] ?? 0) > OTP_SESSION_TTL) {
        return false;
    }
    return strtolower(trim($email)) === ($flag['email'] ?? '');
}

function otp_session_clear($purpose)
{
    unset($_SESSION['otp_verified'][$purpose]);
}

/* ------------------------------------------------------------------ */
/* Delivery                                                            */
/* ------------------------------------------------------------------ */

/**
 * SMTP / Node settings. Environment variables always win so the same code works on
 * Render (dashboard env tab) and locally (includes/mail_config.php).
 *
 * Gmail: enable 2-step verification, then create an App Password at
 * Google Account -> Security -> App passwords and put it in SMTP_PASS.
 *
 * To use Node nodemailer instead of PHP SMTP, set use_node = true in mail_config.php
 * and configure email-config.json (Gmail, SMTP, SendGrid, or Mailgun).
 */
function otp_mail_config()
{
    $local = [];
    $file = __DIR__ . '/mail_config.php';
    if (is_file($file)) {
        $loaded = include $file;
        if (is_array($loaded)) {
            $local = $loaded;
        }
    }

    $cfg = [
        'host'       => (string)(getenv('SMTP_HOST') ?: ($local['host'] ?? '')),
        'port'       => (int)(getenv('SMTP_PORT') ?: ($local['port'] ?? 587)),
        'user'       => (string)(getenv('SMTP_USER') ?: ($local['user'] ?? '')),
        'pass'       => (string)(getenv('SMTP_PASS') ?: ($local['pass'] ?? '')),
        'from'       => (string)(getenv('SMTP_FROM') ?: ($local['from'] ?? '')),
        'from_name'  => (string)(getenv('SMTP_FROM_NAME') ?: ($local['from_name'] ?? '')),
        'use_node'   => (bool)(getenv('OTP_USE_NODE') ?: ($local['use_node'] ?? true)),
    ];
    if ($cfg['from'] === '') {
        $cfg['from'] = $cfg['user'];
    }
    if ($cfg['from_name'] === '') {
        $cfg['from_name'] = "Lotus Women's Hospital";
    }
    return $cfg;
}

/** True when a real mailbox can be reached. */
function otp_mail_ready()
{
    $cfg = otp_mail_config();
    if ($cfg['use_node']) {
        $configPath = __DIR__ . '/../email-config.json';
        if (!is_file($configPath)) {
            return false;
        }
        $config = json_decode(file_get_contents($configPath), true);
        if (!is_array($config)) {
            return false;
        }
        // Check Gmail config
        if (!empty($config['gmail']['user']) && !empty($config['gmail']['pass']) && $config['gmail']['pass'] !== 'YOUR_16_CHAR_APP_PASSWORD_HERE') {
            return true;
        }
        // Check SMTP config
        if (!empty($config['smtp']['user']) && !empty($config['smtp']['pass'])) {
            return true;
        }
        // Check SendGrid
        if (!empty($config['sendgrid']['apiKey'])) {
            return true;
        }
        // Check Mailgun
        if (!empty($config['mailgun']['user']) && !empty($config['mailgun']['pass'])) {
            return true;
        }
        return false;
    }
    return $cfg['host'] !== '' && $cfg['user'] !== '' && $cfg['pass'] !== '';
}

/**
 * Send via Node.js nodemailer script.
 * @return array [ok => bool, message => string]
 */
function otp_node_send($to, $subject, $body_html, $body_text, array $cfg)
{
    $script = __DIR__ . '/../send-otp.js';
    if (!is_file($script)) {
        return ['ok' => false, 'message' => 'Node sender script not found'];
    }
    if (!is_readable($script)) {
        return ['ok' => false, 'message' => 'Node sender script not readable'];
    }

    $node = getenv('OTP_NODE_BIN') ?: 'node';
    $escaped = [
        escapeshellarg($to),
        escapeshellarg($subject),
        escapeshellarg($body_html),
        escapeshellarg($body_text),
    ];
    $cmd = $node . ' ' . escapeshellarg($script) . ' ' . implode(' ', $escaped);
    $output = [];
    $return = 0;
    exec($cmd, $output, $return);
    $out = implode("\n", $output);
    if ($return === 0 && str_starts_with(trim($out), 'SENT:')) {
        return ['ok' => true, 'message' => 'Delivered via Node nodemailer'];
    }
    return ['ok' => false, 'message' => 'Node send failed: ' . $out];
}

/**
 * Send via raw SMTP (no library needed). Works with Gmail App Passwords.
 * @return array [ok => bool, message => string]
 */
function otp_smtp_send($to, $subject, $body_html, array $cfg)
{
    $host = $cfg['host'] ?: 'smtp.gmail.com';
    $port = $cfg['port'] ?: 587;
    $user = $cfg['user'];
    $pass = $cfg['pass'];
    $from = $cfg['from'] !== '' ? $cfg['from'] : $user;
    $fromName = $cfg['from_name'];

    if ($user === '' || $pass === '') {
        return ['ok' => false, 'message' => 'SMTP credentials not configured'];
    }

    $errno = 0; $errstr = '';
    $fp = @fsockopen('ssl://' . $host, $port, $errno, $errstr, 15);
    if (!$fp) {
        $fp = @fsockopen($host, $port, $errno, $errstr, 15);
        if (!$fp) {
            return ['ok' => false, 'message' => "Cannot connect to $host:$port - $errstr"];
        }
        $useTls = true;
    } else {
        $useTls = false;
    }

    $read = function () use ($fp) {
        $data = '';
        while ($line = fgets($fp, 515)) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') break;
        }
        return $data;
    };
    $cmd = function ($c) use ($fp, $read) {
        fwrite($fp, $c . "\r\n");
        return $read();
    };

    $resp = $read(); // greeting
    if (strpos($resp, '220') !== 0) { fclose($fp); return ['ok' => false, 'message' => 'SMTP greeting failed']; }

    $cmd('EHLO localhost');
    if ($useTls) {
        $cmd('STARTTLS');
        stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $cmd('EHLO localhost');
    }

    $cmd('AUTH LOGIN');
    $cmd(base64_encode($user));
    $r = $cmd(base64_encode($pass));
    if (strpos($r, '235') !== 0) { fclose($fp); return ['ok' => false, 'message' => 'SMTP auth failed: ' . trim($r)]; }

    $cmd("MAIL FROM:<$from>");
    $cmd("RCPT TO:<$to>");
    $cmd('DATA');

    $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "\r\n";

    // Dot-stuffing: lines starting with '.' must be doubled
    $body = preg_replace('/^\./m', '..', $body_html);
    fwrite($fp, $headers . $body . "\r\n.\r\n");
    $r = $read();
    $cmd('QUIT');
    fclose($fp);

    if (strpos($r, '250') === 0) {
        return ['ok' => true, 'message' => 'Email sent via SMTP to Gmail inbox'];
    }
    return ['ok' => false, 'message' => 'SMTP send failed: ' . trim($r)];
}

/**
 * Send the OTP email — tries every method in order until one works.
 *
 * @return array {
 *   ok: bool, status: 'sent'|'failed'|'preview',
 *   message: string, code: string, expires_in: int, preview: array
 * }
 */
function otp_send_email($conn, $email, $purpose, $subject, $headline, $text)
{
    $created = otp_create($conn, $email, $purpose);
    $code = $created['code'];
    $cfg  = otp_mail_config();

    $preview = [
        'from_name'      => $cfg['from_name'],
        'from_email'     => $cfg['from'] !== '' ? $cfg['from'] : 'no-reply@lotushospital.com',
        'to'             => $email,
        'subject'        => $subject,
        'headline'       => $headline,
        'text'           => $text,
        'code'           => $code,
        'expires_minutes'=> OTP_TTL_MINUTES,
        'date'           => date('D, d M Y H:i:s'),
        'purpose'        => $purpose,
    ];

    $body = otp_build_html_email($preview);
    $textBody = $headline . "\n\n" . $text . "\n\nCode: " . $code . "\n\nThis code expires in " . OTP_TTL_MINUTES . " minutes.";

    $attempts = [];

    // 1) Node nodemailer.
    if ($cfg['use_node']) {
        $res = otp_node_send($email, $subject, $body, $textBody, $cfg);
        $attempts[] = 'node: ' . $res['message'];
        if ($res['ok']) {
            unset($preview['code']);
            return ['ok'=>true, 'status'=>'sent', 'message'=>$res['message'], 'code'=>'',
                    'expires_in'=>$created['expires_in'], 'preview'=>$preview];
        }
    }

    // 2) Raw PHP SMTP (works once SMTP_PASS / mail_config pass is set).
    if (otp_mail_ready()) {
        $res = otp_smtp_send($email, $subject, $body, $cfg);
        $attempts[] = 'smtp: ' . $res['message'];
        if ($res['ok']) {
            unset($preview['code']);
            return ['ok'=>true, 'status'=>'sent', 'message'=>$res['message'], 'code'=>'',
                    'expires_in'=>$created['expires_in'], 'preview'=>$preview];
        }
    }

    // 3) PHP mail().
    $mailErr = 'mail() not attempted';
    if (function_exists('mail')) {
        $headers  = 'From: ' . $cfg['from_name'] . ' <' . $cfg['from'] . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $sent = @mail($email, $subject, $body, $headers);
        $attempts[] = 'mail(): ' . ($sent ? 'ok' : 'failed');
        if ($sent) {
            unset($preview['code']);
            return ['ok'=>true, 'status'=>'sent', 'message'=>'Email sent via PHP mail()',
                    'code'=>'', 'expires_in'=>$created['expires_in'], 'preview'=>$preview];
        }
        $mailErr = 'PHP mail() failed (no local mail server configured).';
    }

    // 4) Preview only — explain exactly what to do.
    $why = 'No mail server is configured yet.';
    if ($cfg['user'] !== '' && $cfg['pass'] === '') {
        $why = 'Gmail App Password not set for ' . $cfg['user'] . '.';
    }
    $hint = ' Open https://myaccount.google.com/apppasswords, create an App Password,'
          . ' paste it into includes/mail_config.php (field "pass") — OTP emails will go to the real inbox.';
    return [
        'ok' => false,
        'status' => 'preview',
        'message' => $why . $hint,
        'code' => $code,
        'expires_in' => $created['expires_in'],
        'preview' => $preview,
    ];
}

/** Plain-text-free HTML body for the OTP mail. */
function otp_build_html_email(array $p)
{
    $esc = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
    return '<div style="font-family:Arial,Helvetica,sans-serif;background:#f1f3f4;padding:24px 12px">'
        . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e0e0e0">'
        . '<div style="background:#E91E63;color:#fff;padding:18px 24px;font-size:18px;font-weight:700">Lotus Women\'s Hospital</div>'
        . '<div style="padding:24px;color:#202124;font-size:14px;line-height:1.6">'
        . '<p style="margin:0 0 12px">' . $esc($p['headline']) . '</p>'
        . '<p style="margin:0 0 16px;color:#5f6368">' . $esc($p['text']) . '</p>'
        . '<div style="text-align:center;margin:22px 0">'
        . '<span style="display:inline-block;background:#fce4ec;border:1px dashed #E91E63;border-radius:10px;'
        . 'padding:14px 26px;font-size:30px;font-weight:800;letter-spacing:10px;color:#E91E63">'
        . $esc($p['code']) . '</span></div>'
        . '<p style="margin:0;color:#5f6368;font-size:13px">This code expires in ' . (int)$p['expires_minutes']
        . ' minutes. If you did not request it, ignore this email - your password stays safe.</p>'
        . '</div>'
        . '<div style="background:#f8f9fa;padding:14px 24px;color:#80868b;font-size:12px">'
        . '&copy; ' . date('Y') . ' Lotus Women\'s Hospital &middot; This is an automated security email.</div>'
        . '</div></div>';
}
