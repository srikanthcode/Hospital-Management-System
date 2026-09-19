<?php
require_once "../includes/auth.php";
require_role("doctor");
include "../db.php";

$user_id = $_SESSION["user_id"];

$stmt = mysqli_prepare($conn, "SELECT * FROM doctors WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$doctor_id = $doctor["id"] ?? 0;

$today = date("Y-m-d");
$counts = ['patients' => 0, 'today_appts' => 0, 'total_appts' => 0, 'records' => 0];
if ($doctor_id) {
    $r = mysqli_query($conn, "SELECT COUNT(DISTINCT patient_id) c FROM appointments WHERE doctor_id = $doctor_id");
    $counts['patients'] = mysqli_fetch_assoc($r)['c'] ?? 0;

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM appointments WHERE doctor_id = ? AND appointment_date = ?");
    mysqli_stmt_bind_param($stmt, "is", $doctor_id, $today);
    mysqli_stmt_execute($stmt);
    $counts['today_appts'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'] ?? 0;

    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM appointments WHERE doctor_id = $doctor_id");
    $counts['total_appts'] = mysqli_fetch_assoc($r)['c'] ?? 0;

    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM medical_records WHERE doctor_id = $doctor_id");
    $counts['records'] = mysqli_fetch_assoc($r)['c'] ?? 0;
}

$page_title = "Doctor Dashboard";
$active = "doc_home";
$base = "../";
include "../includes/layout.php";
?>

<div class="card mb-4 p-4">
  <h5>Good Morning, Dr. <?php echo e($doctor['name'] ?? $_SESSION['name']); ?> </h5>
  <p class="text-muted mb-0">Specialization: <?php echo e($doctor['specialization'] ?? '-'); ?> | Department: <?php echo e($doctor['department'] ?? '-'); ?></p>
</div>

<div class="row g-3">
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>My Patients</h6>
      <h2 id="statPatients"><?php echo $counts['patients']; ?></h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Today's Appointments</h6>
      <h2 id="statTodayAppts"><?php echo $counts['today_appts']; ?></h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Total Appointments</h6>
      <h2 id="statTotalAppts"><?php echo $counts['total_appts']; ?></h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Medical Records</h6>
      <h2 id="statRecords"><?php echo $counts['records']; ?></h2>
    </div>
  </div>
</div>

<div class="row g-4 mt-3">
  <div class="col-lg-7">
    <div class="card p-4">
      <h5 class="mb-3">Today's Appointments</h5>
      <div id="todayAppointments">
        <div class="text-center text-muted p-3">Loading...</div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card p-4">
      <h5 class="mb-3">Quick Actions</h5>
      <a href="appointments.php" class="btn btn-danger w-100 mb-2">View Appointments</a>
      <a href="patients.php" class="btn pink-btn w-100 mb-2">My Patients</a>
      <a href="medical_records.php" class="btn btn-outline-danger w-100 mb-2">Add Medical Record</a>
      <a href="followups.php" class="btn btn-outline-secondary w-100">Follow-ups</a>
    </div>
  </div>
</div>

<?php include "../includes/layout_footer.php"; ?>
