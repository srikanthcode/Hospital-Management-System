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

$sql = "SELECT a.*, d.name AS doctor_name, s.name AS service_name
        FROM appointments a
        JOIN doctors d ON d.id = a.doctor_id
        LEFT JOIN services s ON s.id = a.service_id
        WHERE a.patient_id = $patient_id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = mysqli_query($conn, $sql);

$page_title = "My Appointments";
$active = "pt_appts";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<div class="d-flex justify-content-between mb-2">
  <h5>My Appointments</h5>
  <a href="book_appointment.php" class="btn pink-btn">+ Book New</a>
</div>
<div class="table-responsive">
<table class="table table-bordered">
<thead><tr><th>ID</th><th>Doctor</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
<tbody>
<?php if (mysqli_num_rows($result) > 0) { while ($r = mysqli_fetch_assoc($result)) { ?>
  <tr>
    <td><?php echo $r['id']; ?></td>
    <td><?php echo e($r['doctor_name']); ?></td>
    <td><?php echo e($r['service_name']); ?></td>
    <td><?php echo e($r['appointment_date']); ?></td>
    <td><?php echo e($r['appointment_time']); ?></td>
    <td><span class="badge bg-info"><?php echo e($r['status']); ?></span></td>
  </tr>
<?php } } else { echo '<tr><td colspan="6" class="text-center">No appointments.</td></tr>'; } ?>
</tbody>
</table>
</div>
</div>
<?php include "../includes/layout_footer.php"; ?>
