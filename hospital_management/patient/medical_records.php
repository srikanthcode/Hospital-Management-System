<?php
require_once "../includes/auth.php";
require_role("patient");
include "../db.php";

$user_id = $_SESSION["user_id"];
$stmt = mysqli_prepare($conn, "SELECT id FROM patients WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$patient_id = $patient["id"] ?? 0;

$result = mysqli_query($conn, "SELECT mr.*, d.name AS doctor_name FROM medical_records mr
    LEFT JOIN doctors d ON d.id = mr.doctor_id
    WHERE mr.patient_id = $patient_id ORDER BY mr.id DESC");

$page_title = "My Medical Records";
$active = "pt_records";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<h5>My Medical Records</h5>
<div class="table-responsive">
<table class="table table-bordered">
<thead><tr><th>ID</th><th>Date</th><th>Doctor</th><th>Diagnosis</th><th>Treatment</th><th>Prescription</th></tr></thead>
<tbody>
<?php if (mysqli_num_rows($result) > 0) { while ($r = mysqli_fetch_assoc($result)) { ?>
  <tr>
    <td><?php echo $r['id']; ?></td>
    <td><?php echo e($r['record_date']); ?></td>
    <td><?php echo e($r['doctor_name']); ?></td>
    <td><?php echo e($r['diagnosis']); ?></td>
    <td><?php echo e($r['treatment']); ?></td>
    <td><?php echo e($r['prescription']); ?></td>
  </tr>
<?php } } else { echo '<tr><td colspan="6" class="text-center">No records.</td></tr>'; } ?>
</tbody>
</table>
</div>
</div>
<?php include "../includes/layout_footer.php"; ?>
