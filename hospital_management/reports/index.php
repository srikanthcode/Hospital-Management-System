<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

$page_title = "Reports";
$active = "reports";
$base = "../";
include "../includes/layout.php";
?>
<div class="row g-3">
  <div class="col-md-3">
    <a href="patient_reports.php" class="text-decoration-none">
      <div class="card p-4 text-center stat-card"><h5>Patient Reports</h5><p class="text-muted">List, search, admission history</p></div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="appointment_reports.php" class="text-decoration-none">
      <div class="card p-4 text-center stat-card"><h5>Appointment Reports</h5><p class="text-muted">Daily, weekly, monthly, status</p></div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="doctor_reports.php" class="text-decoration-none">
      <div class="card p-4 text-center stat-card"><h5>Doctor Reports</h5><p class="text-muted">List, workload, patient count</p></div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="nurse_reports.php" class="text-decoration-none">
      <div class="card p-4 text-center stat-card"><h5>Nurse Reports</h5><p class="text-muted">List, duty, patient-care</p></div>
    </a>
  </div>
</div>
<div class="row g-3 mt-1">
  <div class="col-md-3">
    <a href="salary_reports.php" class="text-decoration-none">
      <div class="card p-4 text-center stat-card"><h5>Salary Reports</h5><p class="text-muted">Staff salary, payment status</p></div>
    </a>
  </div>
</div>
<?php include "../includes/layout_footer.php"; ?>
