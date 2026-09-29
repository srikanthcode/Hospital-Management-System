<?php
require_once "../includes/auth.php";
require_role("doctor");
include "../db.php";

$user_id = $_SESSION["user_id"];

$stmt = mysqli_prepare($conn, "SELECT * FROM doctors WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$doctor_id = $doctor["id"] ?? 0;

$today = date("Y-m-d");
$counts = ['patients' => 0, 'today_appts' => 0, 'total_appts' => 0, 'records' => 0, 'new_patients' => 0, 'active_issues' => 0];
if ($doctor_id) {
    $r = mysqli_query($conn, "SELECT COUNT(DISTINCT patient_id) c FROM appointments WHERE doctor_id = $doctor_id");
    $counts['patients'] = mysqli_fetch_assoc($r)['c'] ?? 0;

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM appointments WHERE doctor_id = ? AND appointment_date = ?");
    mysqli_stmt_bind_param($stmt, "is", $doctor_id, $today);
    mysqli_stmt_execute($stmt);
    $counts['today_appts'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'] ?? 0;

    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM appointments WHERE doctor_id = $doctor_id");
    $counts['total_appts'] = mysqli_fetch_assoc($r)['c'] ?? 0;

    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM medical_records WHERE doctor_id = $doctor_id");
    $counts['records'] = mysqli_fetch_assoc($r)['c'] ?? 0;

    // Unassigned patients (new registrations)
    $assigned = mysqli_fetch_all(mysqli_query($conn, "SELECT DISTINCT patient_id FROM appointments WHERE doctor_id = $doctor_id"), MYSQLI_ASSOC);
    $assigned_ids = array_column($assigned, 'patient_id');
    $assigned_sql = $assigned_ids ? 'AND p.id NOT IN (' . implode(',', array_map('intval', $assigned_ids)) . ')' : '';
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM patients p WHERE 1=1 $assigned_sql");
    $counts['new_patients'] = mysqli_fetch_assoc($r)['c'] ?? 0;

    // Active issues
    $r = mysqli_query($conn, "SELECT COUNT(*) c FROM emergency_records WHERE doctor_id = $doctor_id AND status = 'Active'");
    $counts['active_issues'] = mysqli_fetch_assoc($r)['c'] ?? 0;
}

$page_title = "Doctor Dashboard";
$active = "doc_home";
$base = "../";
include "../includes/layout.php";
?>

<div class="card mb-4 p-4">
  <h5>Good Morning, Dr. <?php echo e($doctor['name'] ?? $_SESSION['name']); ?> </h5>
  <p class="text-muted mb-0">Specialization: <?php echo e($doctor['specialization'] ?? '-'); ?> | Department: <?php echo e($doctor['department'] ?? '-'); ?></p>
</div>

<div class="row g-3">
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>My Patients</h6>
      <h2 id="statPatients"><?php echo $counts['patients']; ?></h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Today's Appointments</h6>
      <h2 id="statTodayAppts"><?php echo $counts['today_appts']; ?></h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Total Appointments</h6>
      <h2 id="statTotalAppts"><?php echo $counts['total_appts']; ?></h2>
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
      <h6>New Registrations</h6>
      <h2 id="statNewPatients"><?php echo $counts['new_patients']; ?></h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Active Issues</h6>
      <h2 id="statActiveIssues"><?php echo $counts['active_issues']; ?></h2>
    </div>
  </div>
</div>

<div class="row g-4 mt-3">
  <div class="col-lg-7">
    <div class="card p-4">
      <h5 class="mb-3">Today's Appointments</h5>
      <div id="todayAppointments">
        <div class="text-center text-muted p-3">Loading...</div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card p-4">
      <h5 class="mb-3">Quick Actions</h5>
      <a href="appointments.php" class="btn btn-danger w-100 mb-2">View Appointments</a>
      <a href="patients.php" class="btn pink-btn w-100 mb-2">My Patients</a>
      <a href="medical_records.php" class="btn btn-outline-danger w-100 mb-2">Add Medical Record</a>
      <a href="followups.php" class="btn btn-outline-secondary w-100">Follow-ups</a>
    </div>
  </div>
</div>

<!-- New Registrations Section -->
<div class="row g-4 mt-3">
  <div class="col-12">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">New Patient Registrations</h5>
        <a href="patients.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover table-bordered mb-0" id="newPatientsTable">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Age</th>
              <th>Phone</th>
              <th>Email</th>
              <th>Blood Group</th>
              <th>Registered</th>
            </tr>
          </thead>
          <tbody id="newPatientsBody">
            <tr><td colspan="7" class="text-center">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Patient Issues/Complaints Section -->
<div class="row g-4 mt-3">
  <div class="col-12">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Patient Issues / Complaints</h5>
        <a href="medical_records.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div id="issuesContainer">
        <div class="text-center text-muted p-3">Loading...</div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
    // Initialize Realtime if not already
    if (typeof Realtime !== 'undefined') {
        Realtime.init('../');
    }

    // ----- New Patients Polling -----
    function renderNewPatients(patients) {
        const tbody = document.getElementById('newPatientsBody');
        if (!tbody) return;

        if (!patients || patients.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No new registrations yet.</td></tr>';
            return;
        }

        let html = '';
        patients.forEach(p => {
            const date = p.created_at ? new Date(p.created_at).toLocaleString() : '-';
            html += `
                <tr>
                    <td>${Realtime.esc(p.id)}</td>
                    <td>${Realtime.esc(p.name)}</td>
                    <td>${Realtime.esc(p.age)}</td>
                    <td>${Realtime.esc(p.phone)}</td>
                    <td>${Realtime.esc(p.email)}</td>
                    <td>${Realtime.esc(p.blood_group)}</td>
                    <td>${Realtime.esc(date)}</td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    function loadNewPatients() {
        fetch('../api/doctor_new_patients.php?limit=10&offset=0', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (data && !data.error) {
                    renderNewPatients(data.patients);
                    Realtime.updateText('statNewPatients', data.total);
                }
            })
            .catch(() => {});
    }

    // Initial load
    loadNewPatients();

    // Poll every 15 seconds
    if (typeof Realtime !== 'undefined') {
        Realtime.startPolling('doctor_new_patients', '../api/doctor_new_patients.php?limit=10&offset=0',
            (data) => {
                if (data && !data.error) {
                    renderNewPatients(data.patients);
                    Realtime.updateText('statNewPatients', data.total);
                }
            }, 15000);
    } else {
        // Fallback if Realtime not loaded
        setInterval(loadNewPatients, 15000);
    }

    // ----- Issues/Complaints Polling -----
    function renderIssues(issues) {
        const container = document.getElementById('issuesContainer');
        if (!container) return;

        if (!issues || issues.length === 0) {
            container.innerHTML = '<div class="text-center text-muted p-3">No active issues.</div>';
            return;
        }

        let html = '<div class="table-responsive"><table class="table table-hover table-bordered mb-0"><thead class="table-light"><tr><th>Type</th><th>Patient</th><th>Age</th><th>Title</th><th>Description</th><th>Date</th><th>Status</th></tr></thead><tbody>';
        issues.forEach(i => {
            const date = i.created_at ? new Date(i.created_at).toLocaleString() : '-';
            const badgeClass = i.status === 'Active' ? 'bg-danger' : (i.status === 'Pending' ? 'bg-warning' : 'bg-success');
            html += `
                <tr>
                    <td><span class="badge bg-secondary">${Realtime.esc(i.source)}</span></td>
                    <td>${Realtime.esc(i.patient_name)}</td>
                    <td>${Realtime.esc(i.age)}</td>
                    <td>${Realtime.esc(i.title)}</td>
                    <td>${Realtime.esc(i.description)}</td>
                    <td>${Realtime.esc(date)}</td>
                    <td><span class="badge ${badgeClass}">${Realtime.esc(i.status)}</span></td>
                </tr>`;
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    }

    function loadIssues() {
        fetch('../api/doctor_issues.php?limit=10&offset=0', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (data && !data.error) {
                    renderIssues(data.issues);
                    Realtime.updateText('statActiveIssues', data.total);
                }
            })
            .catch(() => {});
    }

    // Initial load
    loadIssues();

    // Poll every 15 seconds
    if (typeof Realtime !== 'undefined') {
        Realtime.startPolling('doctor_issues', '../api/doctor_issues.php?limit=10&offset=0',
            (data) => {
                if (data && !data.error) {
                    renderIssues(data.issues);
                    Realtime.updateText('statActiveIssues', data.total);
                }
            }, 15000);
    } else {
        setInterval(loadIssues, 15000);
    }
})();
</script>

<?php include "../includes/layout_footer.php"; ?>
