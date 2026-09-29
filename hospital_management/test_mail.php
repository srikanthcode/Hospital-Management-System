<?php
require __DIR__ . '/includes/otp_mail.php';
$cfg = otp_mail_config();
echo "host=" . $cfg['host'] . "\n";
echo "user=" . $cfg['user'] . "\n";
echo "pass=" . (strlen($cfg['pass']) > 0 ? "SET(" . strlen($cfg['pass']) . " chars)" : "EMPTY") . "\n";
echo "use_node=" . var_export($cfg['use_node'], true) . "\n";
echo "mail_ready=" . var_export(otp_mail_ready(), true) . "\n";

// Test SMTP connection
if (otp_mail_ready()) {
    $res = otp_smtp_send($cfg['user'], "Test", "<p>Test</p>", $cfg);
    echo "smtp_test=" . var_export($res, true) . "\n";
} else {
    echo "SKIPPED: no password set\n";
}
