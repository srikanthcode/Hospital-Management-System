<?php
session_start();
include"db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - Lotus Women's Hospital</title>

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

    <div class="text-center">
        <h2>Admin Dashboard</h2>

        <p>
            Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!
        </p>
    </div>

    <div class="row g-4 mt-4">

       <?php
       $doctor_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM doctors");
       $doctor_data = mysqli_fetch_assoc($doctor_query);
       $doctor_count = $doctor_data["total"];
       ?>
       
       <div class="col-md-4">
               <div class="card p-4 text-center">
                <h4>Doctors</h4>
                <h2><?php echo $doctor_count; ?></h2>
                <p>Total Registered Doctors</p>
                <a href="manage_doctors.php" class="btn pink-btn">
                    Manage Doctors
                </a>
               </div>
       </div>
        <?php
        $patient_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM patients");
        $patient_data = mysqli_fetch_assoc($patient_query);
        $patient_count = $patient_data["total"];
        ?>
        <div class="col-md-4">
            <div class="card p-4 text-center">
                <h4>Patients</h4>
                <h2><?php echo $patient_count; ?></h2>
                 <p>Total Registered Patients</p>
                 <a href="manage_patients.php" class="btn pink-btn">
                    Manage Patients
                </a>
            </div>
        </div>
        <?php
        $appointment_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments");
        $appointment_data = mysqli_fetch_assoc($appointment_query);
        $appointment_count = $appointment_data["total"];
        ?>
        <div class="col-md-4">
            <div class="card p-4 text-center">
                <h4>Appointments</h4>
                 <h2><?php echo $appointment_count; ?></h2>
                 <p>Total Appointments</p>
                 <a href="manage_appointments.php" class="btn pink-btn">
                     Manage Appointments
                </a>
            </div>
        </div>

    </div>
    <div class="text-center mt-5">

    <a href="index.php" class="btn pink-btn me-2">
        Back to Home
    </a>

    <a href="logout.php" class="btn btn-danger">
        Logout
    </a>
    </div>

   
</div>

<footer class="footer">
    <p>© 2026 Lotus Women's Hospital. All Rights Reserved.</p>
</footer>

</body>
</html>