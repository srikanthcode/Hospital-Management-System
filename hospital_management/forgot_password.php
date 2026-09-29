<?php
session_start();
include "db.php";
include "includes/auth.php";
require_once __DIR__ . '/includes/otp_mail.php';

$message = '';
$message_type = 'danger';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email   = strtolower(trim($_POST['otp_email'] ?? ''));
    $pass    = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    // The Gmail OTP must have been verified in THIS session for THIS address.
    if (!otp_session_check('reset', $email)) {
        $message = 'Your Gmail verification is missing or expired. Please verify the code again.';
    } elseif (strlen($pass) < 8) {
        $message = 'New password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) {
        $message = 'New password must contain at least one letter and one number.';
    } elseif ($pass !== $confirm) {
        $message = 'The two passwords do not match.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        try {
            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE lower(email) = lower(?)");
            mysqli_stmt_bind_param($stmt, "ss", $hash, $email);
            mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
        } catch (Exception $e) {
            $affected = 0;
        }

        if ($affected <= 0) {
            $message = 'No account matches this Gmail address. Please register first.';
        } else {
            otp_invalidate($conn, $email, 'reset');
            otp_session_clear('reset');
            header('Location: login.php?reset=1');
            exit();
        }
    }
}

$otp_purpose   = 'reset';
$otp_title     = 'Forgot password';
$otp_subtitle  = 'Enter the Gmail address registered with your account. We generate a real time 6 digit OTP and mail it to you.';
$otp_step3_lbl = 'New password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Lotus Women's Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg main-navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="assets/images/logo3.jpeg" alt="Lotus Women's Hospital" class="brand-logo">
            <div class="brand-text">
                <span class="brand-name">Lotus Women's Hospital</span>
                <span class="brand-tagline">Women's Healthcare</span>
            </div>
        </a>
    </div>
</nav>

<div class="login-container">
    <div class="login-box" style="max-width:520px; text-align:left;">

        <h2 style="text-align:center;">Forgot password</h2>
        <p class="login-subtitle" style="text-align:center;">
            Gmail OTP verification &middot; no username or phone number required
        </p>

        <?php if ($message !== '') { ?>
            <div class="alert alert-<?php echo e($message_type); ?>"><?php echo e($message); ?></div>
        <?php } ?>

        <?php include "includes/otp_widgets.php"; ?>

        <!-- STEP 3 : choose the new password -->
        <section id="otpStep3" hidden>
            <p class="otp-subtitle">Gmail verified for <strong id="otpDoneMail"></strong>. Choose a new password.</p>
            <form method="post" id="resetForm" autocomplete="off">
                <input type="hidden" name="otp_email" id="otpEmailValue" value="">

                <div class="mb-3">
                    <label class="form-label">New password</label>
                    <div class="pwd-wrap">
                        <input type="password" class="form-control" name="password" id="newPassword"
                               minlength="8" required placeholder="At least 8 characters">
                        <button type="button" class="pwd-toggle" data-target="newPassword">Show</button>
                    </div>
                    <div class="form-text">Use at least 8 characters with a letter and a number.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm new password</label>
                    <div class="pwd-wrap">
                        <input type="password" class="form-control" name="confirm_password" id="confirmPassword"
                               minlength="8" required placeholder="Repeat the password">
                        <button type="button" class="pwd-toggle" data-target="confirmPassword">Show</button>
                    </div>
                </div>

                <button type="submit" class="btn pink-btn w-100">Reset password &amp; login</button>
            </form>
        </section>

        <div class="login-links" style="text-align:center;">
            <a href="login.php">&larr; Back to login</a>
        </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2026 Lotus Women's Hospital. All Rights Reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/otp-auth.js"></script>
<script>
  // Show / hide password fields + keep the verified address visible on step 3.
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.pwd-toggle');
    if (!btn) return;
    var input = document.getElementById(btn.dataset.target);
    if (!input) return;
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? 'Hide' : 'Show';
  });

  document.getElementById('resetForm').addEventListener('submit', function (e) {
    var mail = document.getElementById('otpEmailValue').value;
    var done = document.getElementById('otpDoneMail');
    if (done) done.textContent = mail;
  });
</script>
</body>
</html>
