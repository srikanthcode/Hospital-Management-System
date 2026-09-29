<?php
include "db.php";
include "includes/auth.php";

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $name     = trim($_POST['name'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm_password'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $qualification  = trim($_POST['qualification'] ?? '');
    $experience     = trim($_POST['experience'] ?? '');
    $department     = trim($_POST['department'] ?? '');
    $address        = trim($_POST['address'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } elseif (strlen($name) < 2) {
        $message = 'Please enter your full name.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $message = 'Password must contain at least one letter and one number.';
    } elseif ($password !== $confirm) {
        $message = 'The two passwords do not match.';
    } else {
        try {
            $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE lower(email) = lower(?) LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        } catch (Exception $e) {
            $exists = null;
        }

        if ($exists) {
            $message = 'This email is already registered. Please log in.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = mysqli_prepare($conn, "INSERT INTO users (name,email,password,role) VALUES (?,?,?, 'doctor')");
                mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hash);
                mysqli_stmt_execute($stmt);
                $uid = mysqli_insert_id($conn);

                $stmt = mysqli_prepare($conn, "INSERT INTO doctors (user_id,name,specialization,qualification,experience,phone,email,department,address) VALUES (?,?,?,?,?,?,?,?,?)");
                mysqli_stmt_bind_param($stmt, "issssssss", $uid, $name, $specialization, $qualification, $experience, $phone, $email, $department, $address);
                mysqli_stmt_execute($stmt);

                header('Location: login.php?registered=doctor');
                exit();
            } catch (Exception $e) {
                $message = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration - Lotus Women's Hospital</title>
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

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
      <div class="card p-4 border-0 shadow-sm" style="border-radius:16px">
        <h4 class="pink-heading text-center mb-1" style="font-size:20px;font-weight:800">Doctor Registration</h4>
        <p class="text-center text-muted mb-3" style="font-size:12.5px">Create your professional account</p>

        <?php if ($message !== ''): ?>
          <div class="alert alert-danger py-2" style="font-size:13px"><?php echo e($message); ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
          <div class="row g-2">
            <div class="col-md-6 mb-2">
              <label class="form-label" style="font-size:13px;font-weight:600">Full name *</label>
              <input class="form-control" name="name" required minlength="2" style="font-size:14px;padding:8px 12px">
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label" style="font-size:13px;font-weight:600">Email *</label>
              <input type="email" class="form-control" name="email" required placeholder="you@gmail.com" style="font-size:14px;padding:8px 12px">
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label" style="font-size:13px;font-weight:600">Password *</label>
              <div class="pwd-wrap">
                <input type="password" class="form-control" name="password" id="regPassword"
                       minlength="8" required placeholder="8+ characters, letter + number" style="font-size:14px;padding:8px 12px">
                <button type="button" class="pwd-toggle" data-target="regPassword">Show</button>
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label" style="font-size:13px;font-weight:600">Confirm password *</label>
              <div class="pwd-wrap">
                <input type="password" class="form-control" name="confirm_password" id="regConfirm"
                       minlength="8" required placeholder="Repeat password" style="font-size:14px;padding:8px 12px">
                <button type="button" class="pwd-toggle" data-target="regConfirm">Show</button>
              </div>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label" style="font-size:13px;font-weight:600">Phone</label>
              <input class="form-control" name="phone" placeholder="+91..." style="font-size:14px;padding:8px 12px">
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label" style="font-size:13px;font-weight:600">Specialization</label>
              <input class="form-control" name="specialization" placeholder="e.g. Gynaecology" style="font-size:14px;padding:8px 12px">
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label" style="font-size:13px;font-weight:600">Qualification</label>
              <input class="form-control" name="qualification" placeholder="e.g. MBBS, MD" style="font-size:14px;padding:8px 12px">
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label" style="font-size:13px;font-weight:600">Experience</label>
              <input class="form-control" name="experience" placeholder="e.g. 5 years" style="font-size:14px;padding:8px 12px">
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label" style="font-size:13px;font-weight:600">Department</label>
              <input class="form-control" name="department" style="font-size:14px;padding:8px 12px">
            </div>
            <div class="col-12 mb-3">
              <label class="form-label" style="font-size:13px;font-weight:600">Address</label>
              <textarea class="form-control" name="address" rows="2" style="font-size:14px;padding:8px 12px"></textarea>
            </div>
          </div>
          <button class="btn pink-btn w-100" style="padding:10px;font-size:15px;font-weight:700;border-radius:10px">Create doctor account</button>
        </form>

        <div class="text-center mt-3">
          <a href="login.php" style="font-size:13px;color:#E91E63;font-weight:600">Already have an account? Login</a>
        </div>
      </div>
    </div>
  </div>
</div>

<footer class="footer">
    <p>&copy; 2026 Lotus Women's Hospital. All Rights Reserved.</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.pwd-toggle');
    if (!btn) return;
    var input = document.getElementById(btn.dataset.target);
    if (!input) return;
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? 'Hide' : 'Show';
  });
</script>
</body>
</html>
