<?php
/**
 * Real-time OTP API (register / forgot password).
 *
 *   POST action=send    { email, purpose }           -> starts the OTP clock
 *   POST action=resend  { email, purpose }           -> new code, same rules
 *   POST action=verify  { email, purpose, code }     -> binds code to session
 *
 * Never returns the code once it has actually been emailed: the plain code is
 * only part of `preview` when no mail server is configured (local development,
 * on-screen Gmail preview).
 */
include '../db.php';
require_once '../includes/api_helper.php';
require_once '../includes/otp_mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_json(['error' => 'Method not allowed'], 405);
}

api_require_csrf();

$raw = json_decode(file_get_contents('php://input'), true);
if (!is_array($raw)) {
    $raw = [];
}
$fn = function ($key) use ($raw) {
    if (array_key_exists($key, $raw)) {
        return $raw[$key];
    }
    return $_POST[$key] ?? '';
};

$action  = trim((string)($raw['action'] ?? $_POST['action'] ?? ''));
$email   = strtolower(trim((string)$fn('email')));
$purpose = trim((string)$fn('purpose'));
$code    = trim((string)$fn('code'));

if ($action === 'resend') {
    $action = 'send';
}

if ($action === '') {
    api_json(['error' => 'Missing action.'], 400);
}

// Status check doesn't need full validation
if ($action === 'status') {
    if (!otp_valid_purpose($purpose)) {
        api_json(['error' => 'Unknown verification purpose.'], 400);
    }
    if (!otp_valid_email($email)) {
        api_json(['error' => 'Please enter a valid email address.'], 400);
    }
    $email = otp_normalize_email($email);
    
    // Get latest delivery status from database
    try {
        $stmt = mysqli_prepare($conn, "SELECT created_at, consumed, expires_at FROM otp_codes WHERE email = ? AND purpose = ? ORDER BY id DESC LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ss", $email, $purpose);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    } catch (Exception $e) {
        $row = null;
    }
    
    $status = 'unknown';
    $message = 'Checking delivery status...';
    
    if ($row) {
        $created = new DateTime($row['created_at']);
        $expires = new DateTime($row['expires_at']);
        $now = new DateTime();
        
        if ((int)$row['consumed'] === 1) {
            $status = 'verified';
            $message = 'Code has been verified';
        } elseif ($now > $expires) {
            $status = 'expired';
            $message = 'Code expired';
        } else {
            $status = 'pending';
            $message = 'Code sent, waiting for delivery confirmation';
        }
    }
    
    api_json([
        'success' => true,
        'email' => $email,
        'purpose' => $purpose,
        'delivery' => [
            'status' => $status,
            'message' => $message,
        ],
    ]);
}

// Test email configuration
if ($action === 'test-config') {
    $cfg = otp_mail_config();
    $nodeReady = false;
    $nodeDetails = [];
    
    $configPath = __DIR__ . '/../email-config.json';
    if (is_file($configPath)) {
        $config = json_decode(file_get_contents($configPath), true);
        if (is_array($config)) {
            if (!empty($config['gmail']['user']) && !empty($config['gmail']['pass']) && $config['gmail']['pass'] !== 'YOUR_16_CHAR_APP_PASSWORD_HERE') {
                $nodeReady = true;
                $nodeDetails[] = 'Gmail: configured';
            } else {
                $nodeDetails[] = 'Gmail: missing or placeholder password';
            }
            if (!empty($config['smtp']['user']) && !empty($config['smtp']['pass'])) {
                $nodeReady = true;
                $nodeDetails[] = 'SMTP: configured';
            }
            if (!empty($config['sendgrid']['apiKey'])) {
                $nodeReady = true;
                $nodeDetails[] = 'SendGrid: configured';
            }
            if (!empty($config['mailgun']['user']) && !empty($config['mailgun']['pass'])) {
                $nodeReady = true;
                $nodeDetails[] = 'Mailgun: configured';
            }
        }
    } else {
        $nodeDetails[] = 'email-config.json: not found';
    }
    
    $phpReady = $cfg['host'] !== '' && $cfg['user'] !== '' && $cfg['pass'] !== '';
    $phpDetails = $phpReady ? 'PHP mail/SMTP: configured' : 'PHP mail/SMTP: not configured';
    
    api_json([
        'success' => true,
        'use_node' => $cfg['use_node'],
        'node_ready' => $nodeReady,
        'node_details' => $nodeDetails,
        'php_ready' => $phpReady,
        'php_details' => $phpDetails,
        'overall_ready' => $nodeReady || $phpReady,
    ]);
}

if (!otp_valid_purpose($purpose)) {
    api_json(['error' => 'Unknown verification purpose.'], 400);
}
if (!otp_valid_email($email)) {
    api_json(['error' => 'Please enter a valid email address, for example you@gmail.com.'], 400);
}
$email = otp_normalize_email($email);
if (!otp_email_allowed($email, $purpose)) {
    api_json(['error' => otp_email_error($email, $purpose)], 400);
}

/* ---------------------------------------------------------------- */
/* send / resend                                                     */
/* ---------------------------------------------------------------- */
if ($action === 'send') {

    // The account must be in the right state for this purpose.
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE lower(email) = lower(?) LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($purpose === 'register' && $user) {
        api_json(['error' => 'This Gmail address is already registered. Please log in or use "Forgot password".'], 400);
    }
    if ($purpose === 'reset' && !$user) {
        api_json(['error' => 'No account exists for this Gmail address. Please register first.'], 400);
    }

    $limited = otp_rate_limit_error($conn, $email, $purpose);
    if ($limited !== null) {
        api_json(['error' => $limited], 429);
    }

    if ($purpose === 'register') {
        $subject = 'Your Lotus Hospital registration code';
        $headline = 'Confirm your Gmail address';
        $text = 'Use this code to verify your Gmail address and finish registering. It expires in '
            . OTP_TTL_MINUTES . ' minutes.';
    } else {
        $subject = 'Your Lotus Hospital password reset code';
        $headline = 'Reset your password';
        $text = 'Use this code to reset your password. It expires in '
            . OTP_TTL_MINUTES . ' minutes. If you did not ask for it, ignore this email.';
    }

    try {
        $result = otp_send_email($conn, $email, $purpose, $subject, $headline, $text);
    } catch (Exception $e) {
        error_log('OTP send error: ' . $e->getMessage());
        api_json(['error' => 'Could not generate the code: ' . $e->getMessage()], 500);
    }

    otp_session_clear($purpose);

    $response = [
        'success'    => true,
        'email'      => $email,
        'purpose'    => $purpose,
        'expires_in' => (int)$result['expires_in'],
        'delivery'   => [
            'status'  => $result['status'],
            'message' => $result['message'],
        ],
        'preview'    => $result['preview'],
        'debug'      => [
            'mail_configured' => otp_mail_ready(),
            'use_node'        => (bool)(getenv('OTP_USE_NODE') ?: true),
        ],
    ];

    api_json($response);
}

/* ---------------------------------------------------------------- */
/* verify                                                            */
/* ---------------------------------------------------------------- */
if ($action === 'verify') {

    $check = otp_verify_code($conn, $email, $purpose, $code);
    if (!$check['ok']) {
        api_json(['error' => $check['error']], 400);
    }

    otp_session_mark($purpose, $email);

    api_json([
        'success'   => true,
        'verified'  => true,
        'email'     => $email,
        'purpose'   => $purpose,
        'message'   => $purpose === 'register'
            ? 'Gmail verified. Complete your details below.'
            : 'Code verified. Choose a new password below.',
    ]);
}

api_json(['error' => 'Unknown action.'], 400);
