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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: medical_records.php"); exit(); }
    $patient_id = (int)($_POST['patient_id'] ?? 0);
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    $prescription = trim($_POST['prescription'] ?? '');
    $record_date = !empty($_POST['record_date']) ? $_POST['record_date'] : date('Y-m-d');

    if ($patient_id > 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO medical_records (patient_id, doctor_id, diagnosis, treatment, prescription, record_date) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "iissss", $patient_id, $doctor_id, $diagnosis, $treatment, $prescription, $record_date);
        if (mysqli_stmt_execute($stmt)) flash_set('success','Medical record added.');
        else flash_set('danger','Failed to add record.');
    }
    header("Location: medical_records.php");
    exit();
}

$patients = mysqli_query($conn, "SELECT p.id,p.name FROM patients p
    JOIN appointments a ON a.patient_id = p.id WHERE a.doctor_id = $doctor_id GROUP BY p.id");

$records = mysqli_query($conn, "SELECT mr.*, p.name AS patient_name FROM medical_records mr
    JOIN patients p ON p.id = mr.patient_id
    WHERE mr.doctor_id = $doctor_id ORDER BY mr.id DESC");

$page_title = "Medical Records";
$active = "doc_records";
$base = "../";
include "../includes/layout.php";
?>
<div class="row g-3">
  <div class="col-md-5">
    <div class="card p-3">
      <h5>Add Medical Record</h5>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <div class="mb-2"><label class="form-label">Patient</label>
          <select name="patient_id" class="form-select" required>
            <option value="">-- Select --</option>
            <?php while ($p = mysqli_fetch_assoc($patients)) { echo '<option value="'.$p['id'].'">'.e($p['name']).'</option>'; } ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Diagnosis</label>
          <textarea name="diagnosis" class="form-control" rows="2" required></textarea>
        </div>
        <div class="mb-2"><label class="form-label">Treatment</label>
          <textarea name="treatment" class="form-control" rows="2"></textarea>
        </div>
        <div class="mb-2"><label class="form-label">Prescription</label>
          <textarea name="prescription" class="form-control" rows="2"></textarea>
        </div>
        <div class="mb-2"><label class="form-label">Date</label>
          <input type="date" name="record_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <button class="btn pink-btn">Save Record</button>
      </form>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card p-3">
      <h5>Records Created</h5>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead><tr><th>ID</th><th>Patient</th><th>Date</th><th>Diagnosis</th></tr></thead>
          <tbody>
          <?php if (mysqli_num_rows($records) > 0) { while ($r = mysqli_fetch_assoc($records)) { ?>
            <tr>
              <td><?php echo $r['id']; ?></td>
              <td><?php echo e($r['patient_name']); ?></td>
              <td><?php echo e($r['record_date']); ?></td>
              <td><?php echo e($r['diagnosis']); ?></td>
            </tr>
          <?php } } else { echo '<tr><td colspan="4" class="text-center">No records yet.</td></tr>'; } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include "../includes/layout_footer.php"; ?>
