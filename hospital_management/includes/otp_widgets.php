<?php
/**
 * Shared OTP UI (step 1 Gmail -> step 2 code -> step 3 page owned form).
 *
 * Usage:
 *   $otp_purpose = 'register' | 'reset';
 *   $otp_title / $otp_subtitle = optional copy;
 *   include "includes/otp_widgets.php";
 *
 * The page then renders its own <section id="otpStep3" ...> form, which stays
 * hidden until the Gmail OTP has been verified.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/otp_mail.php';

$otp_purpose   = $otp_purpose ?? 'register';
$otp_title     = $otp_title ?? 'Verify your Gmail';
$otp_subtitle  = $otp_subtitle ?? 'We send a 6 digit code to your Gmail address. Nothing else is asked before that.';
$otp_endpoint  = $otp_endpoint ?? 'api/otp.php';
$otp_resend    = (int)($otp_resend ?? 30);
$otp_step3_lbl = $otp_step3_lbl ?? 'Your details';
$otp_mail_set  = strlen((string)(otp_mail_config()['pass'] ?? '')) > 0;
?>

<?php if (!$otp_mail_set): ?>
<div class="alert alert-danger" style="border-left:5px solid #c62828;margin-bottom:14px">
  <strong>Email not configured — OTP shows on screen only.</strong><br>
  To receive OTP in your Gmail inbox, open
  <a href="email_setup.php" style="color:#b71c1c;font-weight:700;text-decoration:underline">email_setup.php</a>
  and paste your 16-character Gmail App Password.
</div>
<?php endif; ?>

<div class="otp-stepper" id="otpStepper">
  <div class="otp-step active" data-step="1"><span>1</span> Gmail</div>
  <div class="otp-step" data-step="2"><span>2</span> Enter code</div>
  <div class="otp-step" data-step="3"><span>3</span> <?php echo e($otp_step3_lbl); ?></div>
</div>

<div class="otp-alert" id="otpAlert" role="alert" aria-live="assertive" hidden></div>

<!-- STEP 1 : only the Gmail address -->
<section id="otpStep1">
  <p class="otp-subtitle"><?php echo e($otp_subtitle); ?></p>

  <label class="form-label" for="otpEmail">Gmail address</label>
  <div class="input-group mb-2">
    <span class="input-group-text">@</span>
    <input type="email" id="otpEmail" class="form-control" placeholder="you@gmail.com"
           autocomplete="email" autocapitalize="off" spellcheck="false" required>
  </div>

  <button type="button" class="btn pink-btn w-100" id="otpSendBtn">Send verification code</button>

  <p class="otp-hint">
    A real time 6 digit OTP is generated for this address and emailed to your
    Gmail inbox. It can take a minute to arrive.
  </p>
  <noscript><div class="otp-alert otp-alert-error">JavaScript is required for the real time OTP flow. Please enable it.</div></noscript>
</section>

<!-- STEP 2 : the code -->
<section id="otpStep2" hidden>
  <p class="otp-sent-to">Code sent to <strong id="otpSentTo"></strong></p>

  <div class="otp-boxes" id="otpBoxes">
    <input class="otp-box" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit 1">
    <input class="otp-box" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit 2">
    <input class="otp-box" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit 3">
    <input class="otp-box" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit 4">
    <input class="otp-box" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit 5">
    <input class="otp-box" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit 6">
  </div>

  <div class="otp-meta">
    <span>Code expires in <strong id="otpCountdown">--:--</strong></span>
    <span class="otp-attempts" id="otpAttemptsNote"></span>
  </div>

  <button type="button" class="btn pink-btn w-100 mb-2" id="otpVerifyBtn">Verify &amp; continue</button>

  <div class="otp-row">
    <button type="button" class="btn btn-outline-secondary btn-sm" id="otpResendBtn" disabled>
      Resend code (<span id="otpResendCount"><?php echo $otp_resend; ?></span>s)
    </button>
    <button type="button" class="btn btn-link btn-sm" id="otpChangeEmail">Change Gmail address</button>
  </div>
</section>

<!-- Gmail style preview of the message that was generated -->
<div class="gmail-overlay" id="gmailOverlay" hidden>
  <div class="gmail-modal" role="dialog" aria-modal="true" aria-labelledby="gmailSubject">
    <div class="gmail-top">
      <button class="gmail-dot" style="background:#f28b82" aria-label="Close" id="gmailCloseX"></button>
      <span class="gmail-dot" style="background:#fbbc04"></span>
      <span class="gmail-dot" style="background:#34a853"></span>
      <span class="gmail-app">Inbox - Gmail preview</span>
    </div>
    <div class="gmail-head">
      <div class="gmail-avatar">L</div>
      <div class="gmail-headtext">
        <div class="gmail-from" id="gmailFromName"></div>
        <div class="gmail-subject" id="gmailSubject"></div>
        <div class="gmail-to" id="gmailTo"></div>
      </div>
      <div class="gmail-date" id="gmailDate"></div>
    </div>
    <div class="gmail-body">
      <div class="gmail-headline" id="gmailHeadline"></div>
      <div class="gmail-text" id="gmailText"></div>
      <div class="gmail-code" id="gmailCode">------</div>
      <div class="gmail-expiry" id="gmailExpiry"></div>
    </div>
    <div class="gmail-foot">
      <span class="gmail-label">Security</span>
      <span id="gmailDelivery"></span>
      <button class="btn btn-sm btn-pink-solid" id="gmailGotIt">Enter the code</button>
    </div>
  </div>
</div>

<script>
window.OTP_CONFIG = <?php echo json_encode([
    'endpoint'       => $otp_endpoint,
    'purpose'        => $otp_purpose,
    'csrf'           => csrf_token(),
    'resendSeconds'  => $otp_resend,
    'title'          => $otp_title,
]); ?>;
</script>
