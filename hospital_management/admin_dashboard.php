<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// Initial stats (will be updated by JS)
$doctor_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM doctors"))['total'] ?? 0;
$patient_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM patients"))['total'] ?? 0;
$appointment_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments"))['total'] ?? 0;
$bed_available = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM beds WHERE status='Available'"))['total'] ?? 0;
$emergency_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM emergency_records WHERE status='Active'"))['total'] ?? 0;

$page_title = "Admin Dashboard";
$active = "admin_home";
$base = "./";
include "includes/layout.php";
?>

<div class="row g-3">
  <div class="col-md">
    <div class="stat-card text-center">
      <h6>Doctors</h6>
      <h2 id="statDoctors"><?php echo $doctor_count; ?></h2>
    </div>
  </div>
  <div class="col-md">
    <div class="stat-card text-center">
      <h6>Patients</h6>
      <h2 id="statPatients"><?php echo $patient_count; ?></h2>
    </div>
  </div>
  <div class="col-md">
    <div class="stat-card text-center">
      <h6>Appointments</h6>
      <h2 id="statAppointments"><?php echo $appointment_count; ?></h2>
    </div>
  </div>
  <div class="col-md">
    <div class="stat-card text-center">
      <h6>Available Beds</h6>
      <h2 id="statBeds"><?php echo $bed_available; ?></h2>
    </div>
  </div>
  <div class="col-md">
    <div class="stat-card text-center">
      <h6>Active Emergencies</h6>
      <h2 id="statEmergencies" class="text-danger"><?php echo $emergency_count; ?></h2>
    </div>
  </div>
</div>

<div class="row g-4 mt-3">
  <!-- Today's Appointments -->
  <div class="col-lg-7">
    <div class="card p-4">
      <h5 class="mb-3">Today's Appointments</h5>
      <div id="todayAppointments">
        <div class="text-center text-muted p-3">Loading...</div>
      </div>
    </div>
  </div>

  <!-- Activity Timeline -->
  <div class="col-lg-5">
    <div class="card p-4">
      <h5 class="mb-3">Recent Activity</h5>
      <div class="activity-section" id="activityTimeline">
        <div class="text-center text-muted p-3">Loading...</div>
      </div>
    </div>
  </div>
</div>

<div class="text-center mt-4">
  <a href="index.php" class="btn pink-btn me-2">Back to Home</a>
  <a href="logout.php" class="btn btn-danger">Logout</a>
</div>

<?php include "includes/layout_footer.php"; ?>
