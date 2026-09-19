<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: manage_appointments.php"); exit(); }
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        if ($action === 'delete') {
            mysqli_query($conn, "DELETE FROM appointments WHERE id = $id");
            flash_set('success','Appointment deleted.');
        } elseif ($action === 'status') {
            $status = trim($_POST['status'] ?? '');
            if (!in_array($status, ['Pending','Confirmed','Completed','Cancelled'])) {
                flash_set('danger','Invalid status.');
                header("Location: manage_appointments.php"); exit();
            }
            $stmt = mysqli_prepare($conn, "UPDATE appointments SET status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt,"si",$status,$id);
            mysqli_stmt_execute($stmt);
            flash_set('success','Status updated to '.$status.'.');
        }
    }
    header("Location: manage_appointments.php"); exit();
}

$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_date = $_GET['date'] ?? '';

$where = "1=1";
if ($search) {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND (p.name LIKE '%$s%' OR d.name LIKE '%$s%')";
}
if ($filter_status) {
    $fs = mysqli_real_escape_string($conn, $filter_status);
    $where .= " AND a.status = '$fs'";
}
if ($filter_date) {
    $fd = mysqli_real_escape_string($conn, $filter_date);
    $where .= " AND a.appointment_date = '$fd'";
}

$result = mysqli_query($conn, "SELECT a.*, p.name AS patient_name, d.name AS doctor_name, s.name AS service_name
    FROM appointments a
    JOIN patients p ON p.id = a.patient_id
    JOIN doctors d ON d.id = a.doctor_id
    LEFT JOIN services s ON s.id = a.service_id
    WHERE $where
    ORDER BY a.appointment_date DESC, a.appointment_time DESC");

$page_title = "Manage Appointments";
$active = "appointments";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<h5>Manage Appointments</h5>
<form method="get" class="row g-1 mb-3">
  <div class="col-md-3">
    <input type="text" class="form-control" name="search" placeholder="Search patient/doctor..." value="<?php echo e($search); ?>">
  </div>
  <div class="col-md-2">
    <select class="form-select" name="status">
      <option value="">All Status</option>
      <?php foreach(['Pending','Confirmed','Completed','Cancelled'] as $st): ?>
        <option value="<?php echo $st; ?>" <?php echo $filter_status===$st?'selected':''; ?>><?php echo $st; ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <input type="date" class="form-control" name="date" value="<?php echo e($filter_date); ?>">
  </div>
  <div class="col-auto"><button class="btn btn-outline-danger">Filter</button></div>
  <div class="col-auto"><a href="manage_appointments.php" class="btn btn-outline-secondary">Reset</a></div>
</form>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php if (mysqli_num_rows($result) > 0) { while ($a = mysqli_fetch_assoc($result)) { ?>
  <tr>
    <td><?php echo $a['id']; ?></td>
    <td><?php echo e($a['patient_name']); ?></td>
    <td><?php echo e($a['doctor_name']); ?></td>
    <td><?php echo e($a['service_name']); ?></td>
    <td><?php echo e($a['appointment_date']); ?></td>
    <td><?php echo e($a['appointment_time']); ?></td>
    <td><span class="badge bg-<?php echo $a['status']==='Confirmed'?'success':($a['status']==='Completed'?'secondary':($a['status']==='Cancelled'?'danger':'warning')); ?>"><?php echo e($a['status']); ?></span></td>
    <td>
      <form method="post" class="d-inline">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="status">
        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
        <select name="status" class="form-select form-select-sm d-inline" style="width:auto" onchange="this.form.submit()">
          <?php foreach(['Pending','Confirmed','Completed','Cancelled'] as $st): ?>
            <option value="<?php echo $st; ?>" <?php echo $a['status']===$st?'selected':''; ?>><?php echo $st; ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <form method="post" class="d-inline">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?');">Del</button>
      </form>
    </td>
  </tr>
<?php } } else { echo '<tr><td colspan="8" class="text-center">No appointments.</td></tr>'; } ?>
</tbody>
</table>
</div>
</div>
<div class="text-center mt-3"><a href="../admin_dashboard.php" class="btn pink-btn">Back to Dashboard</a></div>
<?php include "../includes/layout_footer.php"; ?>
