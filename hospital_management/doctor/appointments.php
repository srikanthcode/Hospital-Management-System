<?php
require_once "../includes/auth.php";
require_role("doctor");
include "../db.php";

$user_id = $_SESSION["user_id"];
$stmt = mysqli_prepare($conn, "SELECT id,name FROM doctors WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$doctor_id = $doctor["id"] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: appointments.php"); exit(); }
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        if ($action === 'confirm') {
            $stmt = mysqli_prepare($conn, "UPDATE appointments SET status='Confirmed' WHERE id=? AND doctor_id=?");
            mysqli_stmt_bind_param($stmt,"ii",$id,$doctor_id);
            mysqli_stmt_execute($stmt);
            flash_set('success','Appointment confirmed.');
        } elseif ($action === 'complete') {
            $stmt = mysqli_prepare($conn, "UPDATE appointments SET status='Completed' WHERE id=? AND doctor_id=?");
            mysqli_stmt_bind_param($stmt,"ii",$id,$doctor_id);
            mysqli_stmt_execute($stmt);
            flash_set('success','Appointment marked as completed.');
        } elseif ($action === 'cancel') {
            $stmt = mysqli_prepare($conn, "UPDATE appointments SET status='Cancelled' WHERE id=? AND doctor_id=?");
            mysqli_stmt_bind_param($stmt,"ii",$id,$doctor_id);
            mysqli_stmt_execute($stmt);
            flash_set('warning','Appointment cancelled.');
        }
    }
    header("Location: appointments.php");
    exit();
}

$sql = "SELECT a.*, p.name AS patient_name
        FROM appointments a
        JOIN patients p ON p.id = a.patient_id
        WHERE a.doctor_id = $doctor_id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = mysqli_query($conn, $sql);

$page_title = "My Appointments";
$active = "doc_appointments";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<h5>Appointments</h5>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Patient</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php if (mysqli_num_rows($result) > 0) { while ($a = mysqli_fetch_assoc($result)) { ?>
  <tr>
    <td><?php echo $a['id']; ?></td>
    <td><?php echo e($a['patient_name']); ?></td>
    <td><?php echo e($a['appointment_date']); ?></td>
    <td><?php echo e($a['appointment_time']); ?></td>
    <td>
      <?php $st = $a['status']; $cls = $st==='Confirmed'?'success':($st==='Completed'?'secondary':($st==='Cancelled'?'danger':'warning')); ?>
      <span class="badge bg-<?php echo $cls; ?>"><?php echo e($st); ?></span>
    </td>
    <td>
      <?php if ($a['status']==='Pending' || $a['status']==='Confirmed'): ?>
      <form method="post" class="d-inline">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
        <button name="action" value="confirm" class="btn btn-sm btn-success">Confirm</button>
        <button name="action" value="complete" class="btn btn-sm btn-secondary">Complete</button>
        <button name="action" value="cancel" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this appointment?');">Cancel</button>
      </form>
      <?php else: ?>
        <em class="text-muted">No actions</em>
      <?php endif; ?>
    </td>
  </tr>
<?php } } else { echo '<tr><td colspan="6" class="text-center">No appointments.</td></tr>'; } ?>
</tbody>
</table>
</div>
</div>
<?php include "../includes/layout_footer.php"; ?>
