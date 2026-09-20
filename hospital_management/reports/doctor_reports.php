<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

$search = $_GET['search'] ?? '';

$where = "1=1";
if ($search) { $s = mysqli_real_escape_string($conn,$search); $where .= " AND (d.name ILIKE '%$s%' OR d.specialization ILIKE '%$s%')"; }

$result = mysqli_query($conn, "SELECT d.*,
    (SELECT COUNT(DISTINCT a.patient_id) FROM appointments a WHERE a.doctor_id = d.id) AS patient_count,
    (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.id) AS appointment_count,
    (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.id AND a.status='Completed') AS completed_count,
    (SELECT COUNT(*) FROM medical_records mr WHERE mr.doctor_id = d.id) AS records_count
    FROM doctors d WHERE $where ORDER BY d.id DESC");

$page_title = "Doctor Reports";
$active = "reports";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<h5>Doctor Reports</h5>
<form method="get" class="mb-3">
  <div class="input-group">
    <input type="text" class="form-control" name="search" placeholder="Search doctor name, specialization..." value="<?php echo e($search); ?>">
    <button class="btn btn-outline-danger">Search</button>
    <a href="doctor_reports.php" class="btn btn-outline-secondary">Reset</a>
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
  </div>
</form>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Name</th><th>Specialization</th><th>Qualification</th><th>Experience</th><th>Phone</th><th>Unique Patients</th><th>Total Appointments</th><th>Completed</th><th>Records</th></tr></thead>
<tbody>
<?php if (mysqli_num_rows($result) > 0) { while ($d = mysqli_fetch_assoc($result)) { ?>
  <tr>
    <td><?php echo $d['id']; ?></td>
    <td><?php echo e($d['name']); ?></td>
    <td><?php echo e($d['specialization']); ?></td>
    <td><?php echo e($d['qualification']); ?></td>
    <td><?php echo e($d['experience']); ?></td>
    <td><?php echo e($d['phone']); ?></td>
    <td><span class="badge bg-primary"><?php echo $d['patient_count']; ?></span></td>
    <td><span class="badge bg-info"><?php echo $d['appointment_count']; ?></span></td>
    <td><span class="badge bg-success"><?php echo $d['completed_count']; ?></span></td>
    <td><span class="badge bg-secondary"><?php echo $d['records_count']; ?></span></td>
  </tr>
<?php } } else { echo '<tr><td colspan="10" class="text-center">No doctors found.</td></tr>'; } ?>
</tbody>
</table>
</div>
<p class="text-muted">Total doctors: <?php echo mysqli_num_rows($result); ?></p>
</div>
<div class="text-center mt-3"><a href="index.php" class="btn pink-btn">Back to Reports</a></div>
<?php include "../includes/layout_footer.php"; ?>
