<?php
/**
 * Gmail SMTP settings — OTP emails go to real Gmail inbox.
 *
 * SETUP (2 minutes):
 *   1. Go to https://myaccount.google.com/security
 *   2. Turn ON "2-Step Verification"
 *   3. Go to https://myaccount.google.com/apppasswords
 *   4. Create a name like "Hospital OTP" → copy the 16-char password
 *   5. Paste it below in 'pass'
 *
 * Environment variables (Render) override values below.
 */
return [
    'host'       => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
    'port'       => (int)(getenv('SMTP_PORT') ?: 587),
    'user'       => getenv('SMTP_USER') ?: 'srikanthz1011@gmail.com',
    'pass'       => getenv('SMTP_PASS') ?: '', // <-- PASTE 16-char Gmail App Password here
    'from'       => getenv('SMTP_FROM') ?: '',
    'from_name'  => getenv('SMTP_FROM_NAME') ?: "Lotus Women's Hospital",
    'use_node'   => getenv('OTP_USE_NODE') ?: false, // PHP SMTP is faster & more reliable
];
