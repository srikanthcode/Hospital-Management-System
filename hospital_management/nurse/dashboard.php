<?php
require_once "../includes/auth.php";
require_role("nurse");
include "../db.php";

$user_id = $_SESSION["user_id"];
$stmt = mysqli_prepare($conn, "SELECT n.* FROM nurses n WHERE n.user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$nurse = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$nurse_id = $nurse["id"] ?? 0;

$page_title = "Nurse Dashboard";
$active = "nurse_home";
$base = "../";
include "../includes/layout.php";
?>

<div class="card mb-4 p-4">
  <h5>Welcome, Nurse <?php echo e($nurse['name'] ?? $_SESSION['name']); ?></h5>
  <p class="text-muted mb-0">Your shift today: <strong><?php echo e($nurse['shift'] ?? '-'); ?></strong></p>
</div>

<div class="row g-3">
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Shift</h6>
      <h2 id="statShift" style="font-size:18px"><?php echo e($nurse['shift'] ?? '-'); ?></h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Department</h6>
      <h2 id="statDepartment" style="font-size:18px"><?php echo e($nurse['department'] ?? '-'); ?></h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Duty Assignment</h6>
      <h2 id="statDuty" style="font-size:18px"><?php echo e($nurse['duty_assignment'] ?? '-'); ?></h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Patient Care</h6>
      <h2 id="statPatients" style="font-size:18px"><?php echo e($nurse['patient_care'] ?? '-'); ?></h2>
    </div>
  </div>
</div>

<div class="row g-4 mt-3">
  <div class="col-lg-6">
    <div class="card p-4">
      <h5 class="mb-3">Today's Duties</h5>
      <div id="todayAppointments">
        <div class="text-center text-muted p-3">Loading...</div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-4">
      <h5 class="mb-3">Emergency Contacts</h5>
      <p class="text-muted mb-1">Hospital Enquiry: +91 9876543210</p>
      <p class="text-muted mb-0">Ambulance: +91 9876543211</p>
    </div>
  </div>
</div>

<?php include "../includes/layout_footer.php"; ?>
