<?php
session_start();
include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["role"] = $user["role"];

            if ($user["role"] == "admin") {
                header("Location: admin_dashboard.php");
                exit();
            } elseif ($user["role"] == "doctor") {
                header("Location: doctor/dashboard.php");
                exit();
            } elseif ($user["role"] == "nurse") {
                header("Location: nurse/dashboard.php");
                exit();
            } else {
                header("Location: patient/dashboard.php");
                exit();
            }

        } else {
            $message = "Invalid email or password.";
        }

    } else {
        $message = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Lotus Women's Hospital</title>

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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#doctors">Doctors</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#contact">Contact</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="login-container">

    <div class="login-box">

        <h2>Login</h2>

        <p class="login-subtitle">
            Welcome to Lotus Women's Hospital
        </p>

        <?php if ($message != "") { ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <form method="POST" action="">

            <div class="mb-3">
                <label class="form-label">Email</label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter your email"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Enter your password"
                    required>
            </div>

            <button type="submit" class="btn pink-btn w-100">
                Login
            </button>

        </form>

        <div class="login-links">
            <a href="index.php">← Back to Home</a>
        </div>

        <div class="register-section">
            <p class="register-text">Don't have an account?</p>
            <div class="register-buttons">
                <a href="patient_register.php" class="btn btn-register btn-patient">Register as Patient</a>
                <a href="doctor_register.php" class="btn btn-register btn-doctor">Register as Doctor</a>
                <a href="nurse_register.php" class="btn btn-register btn-nurse">Register as Nurse</a>
            </div>
        </div>

    </div>

</div>

<footer class="footer">
    <p>© 2026 Lotus Women's Hospital. All Rights Reserved.</p>
</footer>

</body>
</html>