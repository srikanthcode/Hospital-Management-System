<?php
include "db.php";
include "includes/auth.php";

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $age = (int)($_POST['age'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');

    if ($name && $email && $password) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            $message = "Email already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name,email,password,role) VALUES (?,?,?, 'patient')");
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hash);
            if (mysqli_stmt_execute($stmt)) {
                $uid = mysqli_insert_id($conn);
                $stmt = mysqli_prepare($conn, "INSERT INTO patients (user_id,name,age,phone,email,address,blood_group,gender) VALUES (?,?,?,?,?,?,?, 'Female')");
                mysqli_stmt_bind_param($stmt, "isissss", $uid, $name, $age, $phone, $email, $address, $blood_group);
                mysqli_stmt_execute($stmt);
                $message = "Registration successful. Please login.";
            } else {
                $message = "Registration failed.";
            }
        }
    } else {
        $message = "Please fill all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - Lotus Women's Hospital</title>
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
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-7">
      <div class="card p-4">
        <h3 class="pink-heading">Patient Registration</h3>
        <?php if ($message): ?>
          <div class="alert alert-info"><?php echo e($message); ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="row g-2">
            <div class="col-md-6 mb-2"><label class="form-label">Name *</label>
              <input class="form-control" name="name" required>
            </div>
            <div class="col-md-3 mb-2"><label class="form-label">Age</label>
              <input type="number" class="form-control" name="age">
            </div>
            <div class="col-md-3 mb-2"><label class="form-label">Blood Group</label>
              <input class="form-control" name="blood_group">
            </div>
            <div class="col-md-6 mb-2"><label class="form-label">Email *</label>
              <input type="email" class="form-control" name="email" required>
            </div>
            <div class="col-md-6 mb-2"><label class="form-label">Password *</label>
              <input type="password" class="form-control" name="password" required>
            </div>
            <div class="col-md-6 mb-2"><label class="form-label">Phone</label>
              <input class="form-control" name="phone">
            </div>
            <div class="col-12 mb-2"><label class="form-label">Address</label>
              <textarea class="form-control" name="address" rows="2"></textarea>
            </div>
          </div>
          <button class="btn pink-btn">Register</button>
          <a href="login.php" class="btn btn-link">Already have an account? Login</a>
          <a href="index.php" class="btn btn-link">Back to Home</a>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
