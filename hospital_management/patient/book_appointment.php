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

$doctors = mysqli_query($conn, "SELECT id, name, specialization FROM doctors ORDER BY name");
$services = mysqli_query($conn, "SELECT id, name FROM services ORDER BY name");

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: book_appointment.php"); exit(); }
    $doctor_id = (int)($_POST['doctor_id'] ?? 0);
    $service_id = (int)($_POST['service_id'] ?? 0);
    $date = $_POST['appointment_date'] ?? '';
    $time = $_POST['appointment_time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if ($doctor_id && $date && $time) {
        // Prevent double-booking the same doctor at exact date+time
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=? AND status IN ('Pending','Confirmed')");
        mysqli_stmt_bind_param($stmt,"iss",$doctor_id,$date,$time);
        mysqli_stmt_execute($stmt);
        $c = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
        if ($c > 0) {
            flash_set('danger','That slot is already booked. Please choose another time.');
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO appointments (patient_id, doctor_id, service_id, appointment_date, appointment_time, status, notes) VALUES (?,?,?,?,?,'Pending',?)");
            $sid = $service_id > 0 ? $service_id : null;
            mysqli_stmt_bind_param($stmt,"iiisss",$patient_id,$doctor_id,$sid,$date,$time,$notes);
            if (mysqli_stmt_execute($stmt)) flash_set('success','Appointment booked successfully!');
            else flash_set('danger','Failed to book.');
        }
    } else {
        flash_set('danger','Please fill all required fields.');
    }
    header("Location: book_appointment.php");
    exit();
}

$page_title = "Book Appointment";
$active = "pt_book";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-4">
<h5>Book Appointment</h5>
<form method="post">
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
  <div class="row g-2">
    <div class="col-md-6 mb-2">
      <label class="form-label">Doctor *</label>
      <select name="doctor_id" class="form-select" required>
        <option value="">-- Select --</option>
        <?php while ($d = mysqli_fetch_assoc($doctors)) { echo '<option value="'.$d['id'].'">'.e($d['name'].' - '.$d['specialization']).'</option>'; } ?>
      </select>
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label">Service</label>
      <select name="service_id" class="form-select">
        <option value="">-- Optional --</option>
        <?php while ($s = mysqli_fetch_assoc($services)) { echo '<option value="'.$s['id'].'">'.e($s['name']).'</option>'; } ?>
      </select>
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label">Date *</label>
      <input type="date" name="appointment_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
    </div>
    <div class="col-md-6 mb-2">
      <label class="form-label">Time *</label>
      <input type="time" name="appointment_time" class="form-control" required>
    </div>
    <div class="col-12 mb-2">
      <label class="form-label">Notes</label>
      <textarea name="notes" class="form-control" rows="2"></textarea>
    </div>
  </div>
  <button class="btn pink-btn">Book Appointment</button>
  <a href="appointments.php" class="btn btn-secondary">My Appointments</a>
</form>
</div>
<?php include "../includes/layout_footer.php"; ?>
