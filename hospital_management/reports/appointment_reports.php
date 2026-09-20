<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

$filter = $_GET['filter'] ?? '';
$status = $_GET['status'] ?? '';
$doctor_id = (int)($_GET['doctor_id'] ?? 0);
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where = "1=1";

if ($status) { $fs = mysqli_real_escape_string($conn,$status); $where .= " AND a.status='$fs'"; }
if ($doctor_id) { $where .= " AND a.doctor_id=$doctor_id"; }
if ($date_from) { $df = mysqli_real_escape_string($conn,$date_from); $where .= " AND a.appointment_date >= '$df'"; }
if ($date_to) { $dt = mysqli_real_escape_string($conn,$date_to); $where .= " AND a.appointment_date <= '$dt'"; }

if ($filter === 'daily') {
    $where .= " AND a.appointment_date = CURRENT_DATE";
} elseif ($filter === 'weekly') {
    $where .= " AND a.appointment_date >= CURRENT_DATE - INTERVAL '7 days'";
} elseif ($filter === 'monthly') {
    $where .= " AND a.appointment_date >= CURRENT_DATE - INTERVAL '30 days'";
}

$result = mysqli_query($conn, "SELECT a.*, p.name AS patient_name, d.name AS doctor_name, s.name AS service_name
    FROM appointments a
    JOIN patients p ON p.id = a.patient_id
    JOIN doctors d ON d.id = a.doctor_id
    LEFT JOIN services s ON s.id = a.service_id
    WHERE $where
    ORDER BY a.appointment_date DESC, a.appointment_time DESC");

$doctors = mysqli_query($conn, "SELECT id,name FROM doctors ORDER BY name");

// Stats
$stats = ['total'=>0,'pending'=>0,'confirmed'=>0,'completed'=>0,'cancelled'=>0];
$all = mysqli_query($conn, "SELECT COUNT(*) c, status FROM appointments WHERE 1=1 GROUP BY status");
while ($r = mysqli_fetch_assoc($all)) { $stats['total'] += $r['c']; $stats[strtolower($r['status'])] = $r['c']; }

$page_title = "Appointment Reports";
$active = "reports";
$base = "../";
include "../includes/layout.php";
?>
<div class="row g-3 mb-3">
  <div class="col-md-2"><div class="stat-card text-center"><h6>Total</h6><h2><?php echo $stats['total']; ?></h2></div></div>
  <div class="col-md-2"><div class="stat-card text-center"><h6>Pending</h6><h2 class="text-warning"><?php echo $stats['pending']; ?></h2></div></div>
  <div class="col-md-2"><div class="stat-card text-center"><h6>Confirmed</h6><h2 class="text-success"><?php echo $stats['confirmed']; ?></h2></div></div>
  <div class="col-md-3"><div class="stat-card text-center"><h6>Completed</h6><h2 class="text-secondary"><?php echo $stats['completed']; ?></h2></div></div>
  <div class="col-md-3"><div class="stat-card text-center"><h6>Cancelled</h6><h2 class="text-danger"><?php echo $stats['cancelled']; ?></h2></div></div>
</div>

<div class="card p-3">
<h5>Appointment Reports</h5>
<form method="get" class="row g-1 mb-3">
  <div class="col-md-2">
    <select class="form-select" name="filter">
      <option value="">All Time</option>
      <option value="daily" <?php echo $filter==='daily'?'selected':''; ?>>Today</option>
      <option value="weekly" <?php echo $filter==='weekly'?'selected':''; ?>>This Week</option>
      <option value="monthly" <?php echo $filter==='monthly'?'selected':''; ?>>This Month</option>
    </select>
  </div>
  <div class="col-md-2">
    <select class="form-select" name="status">
      <option value="">All Status</option>
      <?php foreach(['Pending','Confirmed','Completed','Cancelled'] as $s): ?>
        <option value="<?php echo $s; ?>" <?php echo $status===$s?'selected':''; ?>><?php echo $s; ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <select class="form-select" name="doctor_id">
      <option value="">All Doctors</option>
      <?php while ($d = mysqli_fetch_assoc($doctors)) { echo '<option value="'.$d['id'].'" '.($doctor_id==$d['id']?'selected':'').'>'.e($d['name']).'</option>'; } ?>
    </select>
  </div>
  <div class="col-md-2"><input type="date" class="form-control" name="date_from" value="<?php echo e($date_from); ?>"></div>
  <div class="col-md-2"><input type="date" class="form-control" name="date_to" value="<?php echo e($date_to); ?>"></div>
  <div class="col-auto"><button class="btn btn-outline-danger">Filter</button></div>
  <div class="col-auto"><a href="appointment_reports.php" class="btn btn-outline-secondary">Reset</a></div>
  <div class="col-auto"><button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button></div>
</form>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
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
  </tr>
<?php } } else { echo '<tr><td colspan="7" class="text-center">No records.</td></tr>'; } ?>
</tbody>
</table>
</div>
<p class="text-muted">Showing <?php echo mysqli_num_rows($result); ?> appointment(s)</p>
</div>
<div class="text-center mt-3"><a href="index.php" class="btn pink-btn">Back to Reports</a></div>
<?php include "../includes/layout_footer.php"; ?>
