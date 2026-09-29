<?php
session_start();
include "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Username may be the account name ("Administrator") or the email address.
    $login = trim((string)($_POST["username"] ?? $_POST["email"] ?? ""));
    $password = (string)($_POST["password"] ?? "");

    $user = null;
    if ($login !== "") {
        // 1) exact email, 2) account name, 3) email local part ("admin").
        $attempts = [
            "SELECT * FROM users WHERE lower(email) = lower(?) LIMIT 1",
            "SELECT * FROM users WHERE lower(name) = lower(?) LIMIT 1",
            // Only used when it identifies a single account.
            "SELECT * FROM users WHERE lower(email) LIKE CONCAT(lower(?), '@%')",
        ];
        foreach ($attempts as $sql) {
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $login);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (!$result) {
                continue;
            }
            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
                if (count($rows) > 1) {
                    break;
                }
            }
            if (count($rows) === 1) {
                $user = $rows[0];
                break;
            }
        }
    }

    if ($user && password_verify($password, $user["password"])) {

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
        $message = "Invalid username or password.";
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

        <div class="login-brand">
            <img src="assets/images/logo-mark.png" alt="Lotus Women's Hospital logo">
        </div>

        <h2>Login</h2>

        <p class="login-subtitle">
            Welcome to Lotus Women's Hospital
        </p>

        <?php if (isset($_GET['reset'])) { ?>
            <div class="alert alert-success">
                Your password was reset successfully. Please log in with your new password.
            </div>
        <?php } ?>

        <?php if (isset($_GET['registered'])) { ?>
            <div class="alert alert-success">
                <?php
                $who = isset($_GET['registered']) ? trim($_GET['registered']) : '';
                $who = in_array($who, ['patient', 'doctor', 'nurse'], true) ? ucfirst($who) : 'Your';
                echo e($who) . ' account was created. Please log in.';
                ?>
            </div>
        <?php } ?>

        <?php if ($message != "") { ?>
            <div class="alert alert-danger" id="loginError">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <form method="POST" action="" id="loginForm">

            <div class="mb-3">
                <label class="form-label" for="loginUsername">Username or email</label>

                <input
                    type="text"
                    id="loginUsername"
                    name="username"
                    class="form-control"
                    placeholder="e.g. admin or admin@lotushospital.com"
                    autocomplete="username"
                    autocapitalize="off"
                    spellcheck="false"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>

                <div class="pwd-wrap">
                    <input
                        type="password"
                        name="password"
                        id="loginPassword"
                        class="form-control"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required>
                    <button type="button" class="pwd-toggle" data-target="loginPassword">Show</button>
                </div>
            </div>

            <div class="forgot-row">
                <a href="forgot_password.php" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn pink-btn w-100" id="loginBtn">
                Login
            </button>

        </form>

        <div class="login-links">
            <a href="index.php">&larr; Back to Home</a>
        </div>

        <div class="register-section">
            <p class="register-text">Don't have an account?</p>
            <div class="register-buttons">
                <a href="patient_register.php" class="btn-register btn-patient" aria-label="Register as Patient">Patient</a>
                <a href="doctor_register.php" class="btn-register btn-doctor" aria-label="Register as Doctor">Doctor</a>
                <a href="nurse_register.php" class="btn-register btn-nurse" aria-label="Register as Nurse">Nurse</a>
            </div>
            <p class="register-hint">Select your role to create an account</p>
        </div>

    </div>

</div>

<footer class="footer">
    <p>© 2026 Lotus Women's Hospital. All Rights Reserved.</p>
</footer>

<script>
    // Show / hide password
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.pwd-toggle');
        if (!btn) return;
        var input = document.getElementById(btn.dataset.target);
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
    });

    // Loading state so a double click can never submit twice
    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function () {
            var btn = document.getElementById('loginBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="otp-spinner"></span> Logging in...';
            }
        });
    }
</script>

</body>
</html>