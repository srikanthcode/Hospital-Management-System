<?php
include "db.php";
include "includes/auth.php";

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $specialization = trim($_POST['specialization'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $experience = trim($_POST['experience'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name && $email && $password) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            $message = "Email already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name,email,password,role) VALUES (?,?,?, 'doctor')");
            mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hash);
            if (mysqli_stmt_execute($stmt)) {
                $uid = mysqli_insert_id($conn);
                $stmt = mysqli_prepare($conn, "INSERT INTO doctors (user_id,name,specialization,qualification,experience,phone,email,department,address) VALUES (?,?,?,?,?,?,?,?,?)");
                mysqli_stmt_bind_param($stmt, "issssssss", $uid, $name, $specialization, $qualification, $experience, $phone, $email, $department, $address);
                mysqli_stmt_execute($stmt);
                $message = "Doctor registered successfully. Please login.";
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
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-7">
      <div class="card p-4">
        <h3 class="pink-heading">Doctor Registration</h3>
        <?php if ($message): ?>
          <div class="alert alert-info"><?php echo e($message); ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="row g-2">
            <div class="col-md-6 mb-2"><label class="form-label">Name *</label>
              <input class="form-control" name="name" required>
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
            <div class="col-md-6 mb-2"><label class="form-label">Specialization</label>
              <input class="form-control" name="specialization" placeholder="e.g. Gynaecology">
            </div>
            <div class="col-md-6 mb-2"><label class="form-label">Qualification</label>
              <input class="form-control" name="qualification" placeholder="e.g. MBBS, MD">
            </div>
            <div class="col-md-6 mb-2"><label class="form-label">Experience</label>
              <input class="form-control" name="experience" placeholder="e.g. 5 years">
            </div>
            <div class="col-md-6 mb-2"><label class="form-label">Department</label>
              <input class="form-control" name="department">
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
