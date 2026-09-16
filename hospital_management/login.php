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
                header("Location: doctor_dashboard.php");
                exit();
            } else {
                header("Location: patient_dashboard.php");
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

<header class="hospital-header">
    <div class="logo-area">
        <img src="assets/images/logo3.jpeg" alt="Lotus Women's Hospital Logo">

        <div>
            <h1>Lotus Women's Hospital</h1>
            <p>Gynecology & Pediatrics Management System</p>
        </div>
    </div>
</header>

<div class="login-container">

    <div class="login-box">

        <h2>Login</h2>

        <p class="login-subtitle">
            Welcome to Lotus Women's Hospital
        </p>

        <?php if ($message != "") { ?>
            <div class="alert alert-danger">
                <?php echo $message; ?>
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

    </div>

</div>

<footer class="footer">
    <p>© 2026 Lotus Women's Hospital. All Rights Reserved.</p>
</footer>

</body>
</html>