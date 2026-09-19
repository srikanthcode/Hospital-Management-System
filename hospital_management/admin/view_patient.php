<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: manage_patients.php"); exit(); }

$patient = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM patients WHERE id = $id"));
if (!$patient) { header("Location: manage_patients.php"); exit(); }

$records = mysqli_query($conn, "SELECT mr.*, d.name AS doctor_name FROM medical_records mr LEFT JOIN doctors d ON d.id = mr.doctor_id WHERE mr.patient_id = $id ORDER BY mr.id DESC");
$appointments = mysqli_query($conn, "SELECT a.*, d.name AS doctor_name FROM appointments a LEFT JOIN doctors d ON d.id = a.doctor_id WHERE a.patient_id = $id ORDER BY a.appointment_date DESC");
$followups = mysqli_query($conn, "SELECT f.*, d.name AS doctor_name FROM follow_ups f LEFT JOIN doctors d ON d.id = f.doctor_id WHERE f.patient_id = $id ORDER BY f.follow_up_date DESC");

$page_title = "Patient Details";
$active = "patients";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-4 mb-3">
  <h5>Patient: <?php echo e($patient['name']); ?></h5>
  <div class="row">
    <div class="col-md-4"><strong>Age:</strong> <?php echo e($patient['age']); ?></div>
    <div class="col-md-4"><strong>Gender:</strong> <?php echo e($patient['gender']); ?></div>
    <div class="col-md-4"><strong>Blood Group:</strong> <?php echo e($patient['blood_group']); ?></div>
    <div class="col-md-4"><strong>Phone:</strong> <?php echo e($patient['phone']); ?></div>
    <div class="col-md-4"><strong>Email:</strong> <?php echo e($patient['email']); ?></div>
    <div class="col-md-4"><strong>Address:</strong> <?php echo e($patient['address']); ?></div>
  </div>
</div>

<ul class="nav nav-tabs">
  <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#records">Medical Records</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#appts">Appointments</a></li>
  <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#follows">Follow-ups</a></li>
</ul>

<div class="tab-content card card-body">
  <div class="tab-pane fade show active" id="records">
    <table class="table table-bordered table-sm">
      <thead><tr><th>Date</th><th>Doctor</th><th>Diagnosis</th><th>Treatment</th></tr></thead>
      <tbody>
      <?php if (mysqli_num_rows($records) > 0) { while ($r = mysqli_fetch_assoc($records)) { ?>
        <tr><td><?php echo e($r['record_date']); ?></td><td><?php echo e($r['doctor_name']); ?></td><td><?php echo e($r['diagnosis']); ?></td><td><?php echo e($r['treatment']); ?></td></tr>
      <?php } } else { echo '<tr><td colspan="4" class="text-center">No records.</td></tr>'; } ?>
      </tbody>
    </table>
  </div>
  <div class="tab-pane fade" id="appts">
    <table class="table table-bordered table-sm">
      <thead><tr><th>Date</th><th>Time</th><th>Doctor</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (mysqli_num_rows($appointments) > 0) { while ($a = mysqli_fetch_assoc($appointments)) { ?>
        <tr><td><?php echo e($a['appointment_date']); ?></td><td><?php echo e($a['appointment_time']); ?></td><td><?php echo e($a['doctor_name']); ?></td><td><?php echo e($a['status']); ?></td></tr>
      <?php } } else { echo '<tr><td colspan="4" class="text-center">No appointments.</td></tr>'; } ?>
      </tbody>
    </table>
  </div>
  <div class="tab-pane fade" id="follows">
    <table class="table table-bordered table-sm">
      <thead><tr><th>Date</th><th>Doctor</th><th>Remarks</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (mysqli_num_rows($followups) > 0) { while ($f = mysqli_fetch_assoc($followups)) { ?>
        <tr><td><?php echo e($f['follow_up_date']); ?></td><td><?php echo e($f['doctor_name']); ?></td><td><?php echo e($f['remarks']); ?></td><td><?php echo e($f['status']); ?></td></tr>
      <?php } } else { echo '<tr><td colspan="4" class="text-center">No follow-ups.</td></tr>'; } ?>
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3"><a href="manage_patients.php" class="btn pink-btn">Back to Patients</a></div>
<?php include "../includes/layout_footer.php"; ?>
