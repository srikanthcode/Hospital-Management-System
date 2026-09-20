<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

$search = $_GET['search'] ?? '';

$where = "1=1";
if ($search) { $s = mysqli_real_escape_string($conn,$search); $where .= " AND (n.name ILIKE '%$s%' OR n.department ILIKE '%$s%' OR n.shift ILIKE '%$s%')"; }

$result = mysqli_query($conn, "SELECT n.* FROM nurses n WHERE $where ORDER BY n.id DESC");

$page_title = "Nurse Reports";
$active = "reports";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<h5>Nurse Reports</h5>
<form method="get" class="mb-3">
  <div class="input-group">
    <input type="text" class="form-control" name="search" placeholder="Search nurse name, department, shift..." value="<?php echo e($search); ?>">
    <button class="btn btn-outline-danger">Search</button>
    <a href="nurse_reports.php" class="btn btn-outline-secondary">Reset</a>
    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
  </div>
</form>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Shift</th><th>Department</th><th>Duty Assignment</th><th>Patient Care</th></tr></thead>
<tbody>
<?php if (mysqli_num_rows($result) > 0) { while ($n = mysqli_fetch_assoc($result)) { ?>
  <tr>
    <td><?php echo $n['id']; ?></td>
    <td><?php echo e($n['name']); ?></td>
    <td><?php echo e($n['phone']); ?></td>
    <td><?php echo e($n['email']); ?></td>
    <td><?php echo e($n['shift']); ?></td>
    <td><?php echo e($n['department']); ?></td>
    <td><?php echo e($n['duty_assignment']); ?></td>
    <td><?php echo e($n['patient_care']); ?></td>
  </tr>
<?php } } else { echo '<tr><td colspan="8" class="text-center">No nurses found.</td></tr>'; } ?>
</tbody>
</table>
</div>
<p class="text-muted">Total nurses: <?php echo mysqli_num_rows($result); ?></p>
</div>
<div class="text-center mt-3"><a href="index.php" class="btn pink-btn">Back to Reports</a></div>
<?php include "../includes/layout_footer.php"; ?>
