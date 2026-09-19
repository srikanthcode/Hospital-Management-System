<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: manage_emergency.php"); exit(); }
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $pname = trim($_POST['patient_name'] ?? '');
        $age = (int)($_POST['age'] ?? 0);
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $ambulance_id = (int)($_POST['ambulance_id'] ?? 0);
        $doctor_id = (int)($_POST['doctor_id'] ?? 0);
        $type = trim($_POST['emergency_type'] ?? '');
        $details = trim($_POST['details'] ?? '');

        if ($pname) {
            $stmt = mysqli_prepare($conn, "INSERT INTO emergency_records (patient_name,age,phone,address,ambulance_id,doctor_id,emergency_type,details) VALUES (?,?,?,?,?,?,?,?)");
            $aid = $ambulance_id > 0 ? $ambulance_id : null;
            $did = $doctor_id > 0 ? $doctor_id : null;
            mysqli_stmt_bind_param($stmt,"sissiiss",$pname,$age,$phone,$address,$aid,$did,$type,$details);
            if (mysqli_stmt_execute($stmt)) flash_set('success','Emergency record created.');
            else flash_set('danger','Failed.');
        }
    } elseif ($action === 'resolve') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "UPDATE emergency_records SET status='Resolved' WHERE id=?");
        mysqli_stmt_bind_param($stmt,"i",$id);
        mysqli_stmt_execute($stmt);
        flash_set('success','Emergency resolved.');
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM emergency_records WHERE id = $id");
        flash_set('success','Record deleted.');
    }
    header("Location: manage_emergency.php"); exit();
}

$ambulances = mysqli_query($conn, "SELECT id, vehicle_number FROM ambulance_services WHERE status='Available' ORDER BY vehicle_number");
$doctors = mysqli_query($conn, "SELECT id,name FROM doctors ORDER BY name");
$records = mysqli_query($conn, "SELECT er.*, d.name AS doctor_name FROM emergency_records er LEFT JOIN doctors d ON d.id = er.doctor_id ORDER BY er.id DESC");

$page_title = "Emergency Management";
$active = "emergency";
$base = "../";
include "../includes/layout.php";
?>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card p-3"><h6>Active Emergencies</h6>
      <h2 class="text-danger"><?php echo mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM emergency_records WHERE status='Active'"))['c']; ?></h2>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3"><h6>Resolved Today</h6>
      <h2 class="text-success"><?php echo mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM emergency_records WHERE status='Resolved'"))['c']; ?></h2>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-5">
    <div class="card p-3">
      <h5>Register Emergency</h5>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="add">
        <div class="mb-2"><label class="form-label">Patient Name *</label><input class="form-control" name="patient_name" required></div>
        <div class="mb-2"><label class="form-label">Age</label><input type="number" class="form-control" name="age"></div>
        <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
        <div class="mb-2"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="1"></textarea></div>
        <div class="mb-2"><label class="form-label">Emergency Type</label><input class="form-control" name="emergency_type" placeholder="e.g. Cardiac, Trauma"></div>
        <div class="mb-2"><label class="form-label">Details</label><textarea class="form-control" name="details" rows="2"></textarea></div>
        <div class="mb-2"><label class="form-label">Ambulance</label>
          <select class="form-select" name="ambulance_id"><option value="">-- None --</option>
          <?php while ($a = mysqli_fetch_assoc($ambulances)) { echo '<option value="'.$a['id'].'">'.e($a['vehicle_number']).'</option>'; } ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Doctor</label>
          <select class="form-select" name="doctor_id"><option value="">-- None --</option>
          <?php while ($d = mysqli_fetch_assoc($doctors)) { echo '<option value="'.$d['id'].'">'.e($d['name']).'</option>'; } ?>
          </select>
        </div>
        <button class="btn pink-btn">Save Emergency</button>
      </form>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card p-3">
      <h5>Emergency Records</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead><tr><th>ID</th><th>Patient</th><th>Type</th><th>Phone</th><th>Doctor</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
          <tbody>
          <?php while ($r = mysqli_fetch_assoc($records)) { ?>
            <tr>
              <td><?php echo $r['id']; ?></td>
              <td><?php echo e($r['patient_name']); ?></td>
              <td><?php echo e($r['emergency_type']); ?></td>
              <td><?php echo e($r['phone']); ?></td>
              <td><?php echo e($r['doctor_name']); ?></td>
              <td><span class="badge bg-<?php echo $r['status']==='Active'?'danger':'success'; ?>"><?php echo e($r['status']); ?></span></td>
              <td><?php echo e($r['created_at']); ?></td>
              <td>
                <?php if ($r['status']==='Active'): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                  <input type="hidden" name="action" value="resolve">
                  <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                  <button class="btn btn-sm btn-success">Resolve</button>
                </form>
                <?php endif; ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                  <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?');">Del</button>
                </form>
              </td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="text-center mt-3"><a href="../admin_dashboard.php" class="btn pink-btn">Back to Dashboard</a></div>
<?php include "../includes/layout_footer.php"; ?>
