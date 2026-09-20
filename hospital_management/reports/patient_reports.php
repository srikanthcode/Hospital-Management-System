<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

$search = $_GET['search'] ?? '';

$where = "1=1";
if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND (p.name ILIKE '%$s%' OR p.phone ILIKE '%$s%' OR p.email ILIKE '%$s%')";
}

$result = mysqli_query($conn, "SELECT p.*,
    (SELECT COUNT(*) FROM appointments WHERE patient_id = p.id) AS total_appointments,
    (SELECT COUNT(*) FROM medical_records WHERE patient_id = p.id) AS total_records,
    (SELECT COUNT(*) FROM admissions WHERE patient_id = p.id) AS total_admissions,
    (SELECT COUNT(*) FROM follow_ups WHERE patient_id = p.id) AS total_followups
    FROM patients p WHERE $where ORDER BY p.id DESC");

$page_title = "Patient Reports";
$active = "reports";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<h5>Patient Reports</h5>
<form method="get" class="mb-3">
  <div class="input-group">
    <input type="text" class="form-control" name="search" placeholder="Search patient name, phone, email..." value="<?php echo e($search); ?>">
    <button class="btn btn-outline-danger">Search</button>
    <a href="patient_reports.php" class="btn btn-outline-secondary">Reset</a>
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
  </div>
</form>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Name</th><th>Age</th><th>Gender</th><th>Phone</th><th>Blood</th><th>Appointments</th><th>Records</th><th>Admissions</th><th>Follow-ups</th></tr></thead>
<tbody>
<?php if (mysqli_num_rows($result) > 0) { while ($p = mysqli_fetch_assoc($result)) { ?>
  <tr>
    <td><?php echo $p['id']; ?></td>
    <td><?php echo e($p['name']); ?></td>
    <td><?php echo e($p['age']); ?></td>
    <td><?php echo e($p['gender']); ?></td>
    <td><?php echo e($p['phone']); ?></td>
    <td><?php echo e($p['blood_group']); ?></td>
    <td><span class="badge bg-info"><?php echo $p['total_appointments']; ?></span></td>
    <td><span class="badge bg-primary"><?php echo $p['total_records']; ?></span></td>
    <td><span class="badge bg-warning"><?php echo $p['total_admissions']; ?></span></td>
    <td><span class="badge bg-secondary"><?php echo $p['total_followups']; ?></span></td>
  </tr>
<?php } } else { echo '<tr><td colspan="10" class="text-center">No records found.</td></tr>'; } ?>
</tbody>
</table>
</div>
<p class="text-muted">Total patients: <?php echo mysqli_num_rows($result); ?></p>
</div>
<div class="text-center mt-3"><a href="index.php" class="btn pink-btn">Back to Reports</a></div>
<?php include "../includes/layout_footer.php"; ?>
