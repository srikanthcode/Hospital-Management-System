<?php
require_once "../includes/auth.php";
require_role("doctor");
include "../db.php";

$user_id = $_SESSION["user_id"];
$stmt = mysqli_prepare($conn, "SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$doctor_id = $doctor["id"] ?? 0;

$sql = "SELECT DISTINCT p.* FROM patients p
        JOIN appointments a ON a.patient_id = p.id
        WHERE a.doctor_id = $doctor_id
        ORDER BY p.id DESC";
$result = mysqli_query($conn, $sql);

$page_title = "My Patients";
$active = "doc_patients";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<h5>Patients Assigned To You</h5>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Name</th><th>Age</th><th>Phone</th><th>Email</th><th>Blood Group</th></tr></thead>
<tbody>
<?php if (mysqli_num_rows($result) > 0) { while ($p = mysqli_fetch_assoc($result)) { ?>
  <tr>
    <td><?php echo $p['id']; ?></td>
    <td><?php echo e($p['name']); ?></td>
    <td><?php echo e($p['age']); ?></td>
    <td><?php echo e($p['phone']); ?></td>
    <td><?php echo e($p['email']); ?></td>
    <td><?php echo e($p['blood_group']); ?></td>
  </tr>
<?php } } else { echo '<tr><td colspan="6" class="text-center">No patients yet.</td></tr>'; } ?>
</tbody>
</table>
</div>
</div>
<?php include "../includes/layout_footer.php"; ?>
