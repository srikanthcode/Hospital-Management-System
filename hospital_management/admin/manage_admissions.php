<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: manage_admissions.php"); exit(); }
    $action = $_POST['action'] ?? '';

    if ($action === 'admit') {
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $bed_id = (int)($_POST['bed_id'] ?? 0);
        $doctor_id = (int)($_POST['doctor_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if ($patient_id && $bed_id) {
            // Check bed available
            $r = mysqli_query($conn, "SELECT status FROM beds WHERE id = $bed_id");
            $bed = mysqli_fetch_assoc($r);
            if ($bed && $bed['status'] === 'Available') {
                $stmt = mysqli_prepare($conn, "INSERT INTO admissions (patient_id, bed_id, doctor_id, reason, admission_date, status) VALUES (?,?,?,?,CURRENT_TIMESTAMP,'Admitted')");
                mysqli_stmt_bind_param($stmt,"iiis",$patient_id,$bed_id,$doctor_id,$reason);
                if (mysqli_stmt_execute($stmt)) {
                    $stmt = mysqli_prepare($conn, "UPDATE beds SET status='Occupied' WHERE id=?");
                    mysqli_stmt_bind_param($stmt,"i",$bed_id);
                    mysqli_stmt_execute($stmt);
                    flash_set('success','Patient admitted and bed assigned.');
                } else {
                    flash_set('danger','Failed to admit patient.');
                }
            } else {
                flash_set('danger','Bed is not available.');
            }
        }
    } elseif ($action === 'discharge') {
        $adm_id = (int)($_POST['admission_id'] ?? 0);
        $r = mysqli_query($conn, "SELECT bed_id FROM admissions WHERE id=$adm_id");
        $a = mysqli_fetch_assoc($r);
        if ($a) {
            $stmt = mysqli_prepare($conn, "UPDATE admissions SET status='Discharged', discharge_date=CURRENT_TIMESTAMP WHERE id=?");
            mysqli_stmt_bind_param($stmt,"i",$adm_id);
            mysqli_stmt_execute($stmt);
            if ($a['bed_id']) {
                $stmt = mysqli_prepare($conn, "UPDATE beds SET status='Available' WHERE id=?");
                mysqli_stmt_bind_param($stmt,"i",$a['bed_id']);
                mysqli_stmt_execute($stmt);
            }
            flash_set('success','Patient discharged. Bed marked available.');
        }
    }
    header("Location: manage_admissions.php"); exit();
}

$patients = mysqli_query($conn, "SELECT id,name FROM patients ORDER BY name");
$doctors  = mysqli_query($conn, "SELECT id,name FROM doctors ORDER BY name");
$beds     = mysqli_query($conn, "SELECT b.id, b.bed_number, w.ward_name, b.status FROM beds b LEFT JOIN wards w ON w.id=b.ward_id WHERE b.status='Available' ORDER BY w.ward_name, b.bed_number");
$admissions = mysqli_query($conn, "SELECT a.*, p.name AS patient_name, b.bed_number, w.ward_name, d.name AS doctor_name
    FROM admissions a
    JOIN patients p ON p.id = a.patient_id
    LEFT JOIN beds b ON b.id = a.bed_id
    LEFT JOIN wards w ON w.id = b.ward_id
    LEFT JOIN doctors d ON d.id = a.doctor_id
    ORDER BY a.id DESC");

$page_title = "Patient Admissions";
$active = "admissions";
$base = "../";
include "../includes/layout.php";
?>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card p-3">
      <h6>Currently Admitted</h6>
      <h2 class="text-danger"><?php echo mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM admissions WHERE status='Admitted'"))['c']; ?></h2>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3">
      <h6>Total Discharged</h6>
      <h2 class="text-success"><?php echo mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM admissions WHERE status='Discharged'"))['c']; ?></h2>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-5">
    <div class="card p-3">
      <h5>Admit Patient</h5>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="admit">
        <div class="mb-2"><label class="form-label">Patient</label>
          <select name="patient_id" class="form-select" required>
            <option value="">-- Select --</option>
            <?php while ($p = mysqli_fetch_assoc($patients)) { echo '<option value="'.$p['id'].'">'.e($p['name']).'</option>'; } ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Available Bed</label>
          <select name="bed_id" class="form-select" required>
            <option value="">-- Select --</option>
            <?php while ($b = mysqli_fetch_assoc($beds)) { echo '<option value="'.$b['id'].'">'.e($b['ward_name'].' - '.$b['bed_number']).'</option>'; } ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Doctor</label>
          <select name="doctor_id" class="form-select">
            <option value="">-- Select --</option>
            <?php while ($d = mysqli_fetch_assoc($doctors)) { echo '<option value="'.$d['id'].'">'.e($d['name']).'</option>'; } ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Reason</label>
          <textarea name="reason" class="form-control" rows="2"></textarea>
        </div>
        <button class="btn pink-btn">Admit</button>
      </form>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card p-3">
      <h5>Admission Records</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead><tr><th>ID</th><th>Patient</th><th>Ward</th><th>Bed</th><th>Doctor</th><th>Admitted</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
          <?php while ($a = mysqli_fetch_assoc($admissions)) { ?>
            <tr>
              <td><?php echo $a['id']; ?></td>
              <td><?php echo e($a['patient_name']); ?></td>
              <td><?php echo e($a['ward_name']); ?></td>
              <td><?php echo e($a['bed_number']); ?></td>
              <td><?php echo e($a['doctor_name']); ?></td>
              <td><?php echo e($a['admission_date']); ?></td>
              <td><span class="badge bg-<?php echo $a['status']==='Admitted'?'danger':'success'; ?>"><?php echo e($a['status']); ?></span></td>
              <td>
                <?php if ($a['status']==='Admitted'): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                  <input type="hidden" name="action" value="discharge">
                  <input type="hidden" name="admission_id" value="<?php echo $a['id']; ?>">
                  <button class="btn btn-sm btn-success" onclick="return confirm('Discharge this patient?');">Discharge</button>
                </form>
                <?php else: ?> - <?php endif; ?>
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
