<?php
require_once __DIR__ . '/includes/otp_mail.php';
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = trim($_POST['app_password'] ?? '');
    if (strlen($pass) !== 16 || !preg_match('/^[a-z]{4} [a-z]{4} [a-z]{4} [a-z]{4}$/i', $pass)) {
        $message = 'App Password must be 16 characters (4 groups of 4 letters). Example: abcd efgh ijkl mnop';
        $message_type = 'danger';
    } else {
        $pass = str_replace(' ', '', $pass);
        // Write to mail_config.php
        $file = __DIR__ . '/includes/mail_config.php';
        $content = file_get_contents($file);
        $content = preg_replace("/'pass'\s*=>\s*getenv\('SMTP_PASS'\)\s*\?\:\s*'[^']*'/", "'pass'       => getenv('SMTP_PASS') ?: '" . $pass . "'", $content);
        file_put_contents($file, $content);

        // Test send
        $cfg = otp_mail_config();
        $res = otp_smtp_send($cfg['user'], 'Test OTP from Lotus Hospital', '<p>Your App Password works! Real OTP emails will now arrive in your Gmail inbox.</p>', $cfg);
        if ($res['ok']) {
            $message = 'SUCCESS! Test email sent to ' . $cfg['user'] . '. Check your Gmail inbox. OTP emails will now go to real inbox.';
            $message_type = 'success';
        } else {
            $message = 'Password saved but test failed: ' . $res['message'];
            $message_type = 'warning';
        }
    }
}

$cfg = otp_mail_config();
$passSet = strlen($cfg['pass']) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Email Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{background:#FFF5F8;padding:40px 0}</style>
</head>
<body>
<div class="container" style="max-width:650px">
    <div class="card p-4 shadow">
        <h3 class="text-center mb-3" style="color:#E91E63">OTP Email Setup</h3>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="alert alert-info">
            <strong>Current status:</strong>
            <?php if ($passSet): ?>
                <span class="text-success">App Password is SET (<?php echo strlen($cfg['pass']); ?> chars)</span>
            <?php else: ?>
                <span class="text-danger">No App Password — OTP only shows on screen</span>
            <?php endif; ?>
        </div>

        <h5>How to get your Gmail App Password (2 minutes):</h5>
        <ol>
            <li>Go to <a href="https://myaccount.google.com/security" target="_blank">Google Security</a></li>
            <li>Turn ON <strong>2-Step Verification</strong></li>
            <li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">App Passwords</a></li>
            <li>Type name: <code>Hospital OTP</code> → click <strong>Create</strong></li>
            <li>Copy the <strong>16 character password</strong> (like <code>abcd efgh ijkl mnop</code>)</li>
            <li>Paste it below and click Save & Test</li>
        </ol>

        <form method="post">
            <div class="mb-3">
                <label class="form-label fw-bold">16-character App Password</label>
                <input type="text" name="app_password" class="form-control form-control-lg"
                       placeholder="abcd efgh ijkl mnop" maxlength="19" autocomplete="off">
            </div>
            <button class="btn btn-lg w-100 text-white" style="background:#E91E63">Save & Send Test Email</button>
        </form>

        <div class="text-center mt-3">
            <a href="patient_register.php" class="btn btn-outline-secondary btn-sm">Back to Registration</a>
        </div>
    </div>
</div>
</body>
</html>
