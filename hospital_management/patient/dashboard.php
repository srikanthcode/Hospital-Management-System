<?php
require_once "../includes/auth.php";
require_role("patient");
include "../db.php";

$user_id = $_SESSION["user_id"];
$stmt = mysqli_prepare($conn, "SELECT p.* FROM patients p WHERE p.user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$patient_id = $patient["id"] ?? 0;

$counts = ['appts' => 0, 'records' => 0, 'followups' => 0];
if ($patient_id) {
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM appointments WHERE patient_id = $patient_id");
    $counts['appts'] = mysqli_fetch_assoc($r)['c'] ?? 0;
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM medical_records WHERE patient_id = $patient_id");
    $counts['records'] = mysqli_fetch_assoc($r)['c'] ?? 0;
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM follow_ups WHERE patient_id = $patient_id");
    $counts['followups'] = mysqli_fetch_assoc($r)['c'] ?? 0;
}

$page_title = "Patient Dashboard";
$active = "pt_home";
$base = "../";
include "../includes/layout.php";
?>

<div class="row g-3">
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>My Appointments</h6>
      <h2 id="statAppts"><?php echo $counts['appts']; ?></h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Medical Records</h6>
      <h2 id="statRecords"><?php echo $counts['records']; ?></h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Follow-ups</h6>
      <h2 id="statFollowups"><?php echo $counts['followups']; ?></h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Profile</h6>
      <h2>1</h2>
    </div>
  </div>
</div>

<div class="row g-4 mt-3">
  <div class="col-lg-7">
    <!-- Next Appointment Card -->
    <div class="card p-4">
      <h5 class="mb-3">Next Appointment</h5>
      <div id="nextAppointment">
        <div class="text-center text-muted p-3">Loading...</div>
      </div>
    </div>

    <!-- Today's Appointments -->
    <div class="card p-4 mt-3">
      <h5 class="mb-3">Today's Appointments</h5>
      <div id="todayAppointments">
        <div class="text-center text-muted p-3">Loading...</div>
      </div>
    </div>

    <!-- Live prescriptions from the doctor -->
    <div class="card p-4 mt-3">
      <h5 class="mb-3">My Prescriptions <span class="badge bg-success">Live</span></h5>
      <div id="livePrescriptions">
        <div class="text-center text-muted p-3">Loading...</div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card p-4">
      <h5>Welcome, <?php echo e($patient['name'] ?? $_SESSION['name']); ?></h5>
      <p class="text-muted">Book appointments, view records and manage your health.</p>
      <a href="book_appointment.php" class="btn pink-btn w-100 mb-2">Book Appointment</a>
      <a href="appointments.php" class="btn btn-outline-danger w-100 mb-2">My Appointments</a>
      <a href="medical_records.php" class="btn btn-outline-secondary w-100">Medical Records</a>
    </div>
  </div>
</div>

<script>
(function() {
    function renderPrescriptions(records) {
        const box = document.getElementById('livePrescriptions');
        if (!box) return;
        if (!records || records.length === 0) {
            box.innerHTML = '<div class="text-center text-muted p-3">No prescriptions yet.</div>';
            return;
        }
        let html = '';
        records.slice(0, 4).forEach(r => {
            const date = r.record_date || (r.created_at ? new Date(r.created_at).toLocaleDateString() : '-');
            html += `
            <div class="border rounded p-2 mb-2">
              <div class="d-flex justify-content-between">
                <strong>${Realtime.esc(r.diagnosis || 'Prescription')}</strong>
                <small class="text-muted">${Realtime.esc(date)}</small>
              </div>
              <div class="small">${Realtime.esc(r.doctor_name ? 'Dr. ' + r.doctor_name : '')}</div>
              <div class="small text-muted">${Realtime.esc(r.prescription || r.treatment || '')}</div>
              ${r.follow_up_date ? '<div class="small text-danger">Follow-up: ' + Realtime.esc(r.follow_up_date) + '</div>' : ''}
            </div>`;
        });
        box.innerHTML = html;
    }

    function loadPrescriptions() {
        fetch('../api/patient_medical_records.php?limit=4', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => { if (d && !d.error) renderPrescriptions(d.records); })
            .catch(() => {});
    }

    loadPrescriptions();
    Realtime.startPolling('patient_live_rx', '../api/patient_medical_records.php?limit=4',
        (d) => { if (d && !d.error) renderPrescriptions(d.records); }, 5000);
})();
</script>

<?php include "../includes/layout_footer.php"; ?>
