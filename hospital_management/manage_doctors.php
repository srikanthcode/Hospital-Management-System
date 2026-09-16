<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

$query = "SELECT * FROM doctors ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Doctors - Lotus Women's Hospital</title>

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

<div class="container py-5">

    <div class="text-center mb-4">
        <h2>Manage Doctors</h2>
        <p>Registered doctors in Lotus Women's Hospital</p>
    </div>

    <div class="table-responsive">

        <table class="table table-bordered table-striped">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Specialization</th>
                    <th>Qualification</th>
                    <th>Experience</th>
                    <th>Phone</th>
                </tr>
            </thead>

            <tbody>

                <?php if (mysqli_num_rows($result) > 0) { ?>

                    <?php while ($doctor = mysqli_fetch_assoc($result)) { ?>

                        <tr>
                            <td><?php echo $doctor["id"]; ?></td>

                            <td>
                                <?php echo htmlspecialchars($doctor["name"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($doctor["specialization"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($doctor["qualification"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($doctor["experience"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($doctor["phone"]); ?>
                            </td>
                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>
                        <td colspan="6" class="text-center">
                            No doctors registered yet.
                        </td>
                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

    <div class="text-center mt-4">

        <a href="admin_dashboard.php" class="btn pink-btn">
            ← Back to Dashboard
        </a>

    </div>

</div>

<footer class="footer">
    <p>© 2026 Lotus Women's Hospital. All Rights Reserved.</p>
</footer>

</body>
</html>