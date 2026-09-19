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

$result = mysqli_query($conn, "SELECT f.*, d.name AS doctor_name FROM follow_ups f
    LEFT JOIN doctors d ON d.id = f.doctor_id
    WHERE f.patient_id = $patient_id ORDER BY f.follow_up_date DESC");

$page_title = "My Follow-ups";
$active = "pt_followups";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<h5>My Follow-ups</h5>
<div class="table-responsive">
<table class="table table-bordered">
<thead><tr><th>ID</th><th>Date</th><th>Doctor</th><th>Remarks</th><th>Status</th></tr></thead>
<tbody>
<?php if (mysqli_num_rows($result) > 0) { while ($r = mysqli_fetch_assoc($result)) { ?>
  <tr>
    <td><?php echo $r['id']; ?></td>
    <td><?php echo e($r['follow_up_date']); ?></td>
    <td><?php echo e($r['doctor_name']); ?></td>
    <td><?php echo e($r['remarks']); ?></td>
    <td><span class="badge bg-<?php echo $r['status']==='Done'?'success':'warning'; ?>"><?php echo e($r['status']); ?></span></td>
  </tr>
<?php } } else { echo '<tr><td colspan="5" class="text-center">No follow-ups.</td></tr>'; } ?>
</tbody>
</table>
</div>
</div>
<?php include "../includes/layout_footer.php"; ?>
