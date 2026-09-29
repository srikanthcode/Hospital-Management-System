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

<!-- Live Patient Bookings -->
<div class="row g-4 mt-3">
  <div class="col-12">
    <div class="card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Patient Bookings <span class="badge bg-success">Live</span></h5>
        <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div id="bookingsAlert" class="d-none"></div>
      <div class="table-responsive">
        <table class="table table-hover table-bordered mb-0">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Date</th>
              <th>Time</th>
              <th>Patient</th>
              <th>Service</th>
              <th>Notes</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="liveBookings">
            <tr><td colspan="8" class="text-center">Loading...</td></tr>
          </tbody>
        </table>
      </div>
      <small class="text-muted mt-2 d-block">Refreshes every 5 seconds - new patient bookings appear here automatically.</small>
    </div>
  </div>
</div>

<!-- Digital Prescription Modal -->
<div class="modal fade" id="prescribeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Digital Prescription</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="rxAlert" class="d-none"></div>
        <input type="hidden" id="rxAppointmentId">
        <div class="row g-2 mb-2">
          <div class="col-md-6">
            <label class="form-label">Patient</label>
            <input class="form-control" id="rxPatient" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label">Appointment</label>
            <input class="form-control" id="rxSlot" readonly>
          </div>
        </div>
        <div class="mb-2">
          <label class="form-label">Diagnosis *</label>
          <textarea class="form-control" id="rxDiagnosis" rows="2" placeholder="e.g. Gestational hypertension, stage 1"></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label">Prescription / Medicines *</label>
          <textarea class="form-control" id="rxPrescription" rows="3" placeholder="e.g. Tab. Nifedipine 10mg - 1-0-1 after food (7 days)"></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label">Treatment / Advice</label>
          <textarea class="form-control" id="rxTreatment" rows="2" placeholder="Rest, BP monitoring twice daily..."></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label">Notes</label>
          <textarea class="form-control" id="rxNotes" rows="2" placeholder="Additional instructions"></textarea>
        </div>
        <div class="mb-1">
          <label class="form-label">Follow-up date</label>
          <input type="date" class="form-control" id="rxFollowUp">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn pink-btn" id="rxSaveBtn">Save &amp; Send to Patient</button>
      </div>
    </div>
  </div>
</div>

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

    // ----- Live patient bookings + digital prescription -----
    let bookingsSig = '';
    let lastBookings = [];

    function bookingSortKey(a) { return (a.appointment_date || '') + ' ' + (a.appointment_time || ''); }

    function renderBookings(list) {
        const tbody = document.getElementById('liveBookings');
        if (!tbody) return;
        const sig = JSON.stringify(list.map(b => [b.id, b.status, b.patient_name, b.appointment_date, b.appointment_time]));
        if (sig === bookingsSig) return;
        const firstLoad = bookingsSig === '';
        bookingsSig = sig;

        if (!list || list.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No bookings yet - new bookings appear here automatically.</td></tr>';
            return;
        }

        const badge = { Pending: 'warning', Confirmed: 'success', Completed: 'secondary', Cancelled: 'danger' };
        let html = '';
        list.forEach(a => {
            const st = a.status || 'Pending';
            let actions = '';
            if (st === 'Pending') actions += `<button class="btn btn-sm btn-success me-1" data-act="Confirmed" data-id="${a.id}">Confirm</button>`;
            if (st === 'Confirmed') actions += `<button class="btn btn-sm btn-primary me-1" data-act="Completed" data-id="${a.id}">Complete</button>`;
            if (st !== 'Cancelled') actions += `<button class="btn btn-sm btn-outline-danger me-1" data-act="Cancelled" data-id="${a.id}">Cancel</button>`;
            if (st !== 'Cancelled') actions += `<button class="btn btn-sm pink-btn" data-prescribe="${a.id}">Prescribe</button>`;

            html += `<tr${(firstLoad || st !== 'Pending') ? '' : ' class="table-warning"'}>
                <td>${Realtime.esc(a.id)}</td>
                <td>${Realtime.esc(a.appointment_date)}</td>
                <td>${a.appointment_time ? Realtime.formatTime(a.appointment_time) : '-'}</td>
                <td>${Realtime.esc(a.patient_name)}</td>
                <td>${Realtime.esc(a.service_name || '-')}</td>
                <td>${Realtime.esc(a.notes || '')}</td>
                <td><span class="badge bg-${badge[st] || 'secondary'}">${Realtime.esc(st)}</span></td>
                <td>${actions}</td>
            </tr>`;
        });
        tbody.innerHTML = html;
        lastBookings = list;
    }

    function loadBookings() {
        fetch('../api/appointments.php?limit=30', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => { if (d && !d.error) renderBookings(d.appointments || []); })
            .catch(() => {});
    }

    loadBookings();
    Realtime.startPolling('doctor_live_bookings', '../api/appointments.php?limit=30',
        (d) => { if (d && !d.error) renderBookings(d.appointments || []); }, 5000);

    function showBookingAlert(type, msg) {
        const el = document.getElementById('bookingsAlert');
        if (!el) return;
        el.className = 'alert alert-' + type + ' py-2';
        el.textContent = msg;
        setTimeout(() => { el.className = 'd-none'; }, 5000);
    }

    document.addEventListener('click', function(e) {
        const statusBtn = e.target.closest('[data-act]');
        if (statusBtn) {
            const id = statusBtn.dataset.id, act = statusBtn.dataset.act;
            statusBtn.disabled = true;
            Realtime.postForm('../api/appointments.php', { action: 'update_status', appointment_id: id, status: act })
                .then(res => {
                    showBookingAlert(res && res.success ? 'success' : 'danger',
                        res && res.message ? res.message : (res && res.error ? res.error : 'Update failed'));
                    loadBookings();
                });
            return;
        }

        const rxBtn = e.target.closest('[data-prescribe]');
        if (rxBtn) {
            const appt = lastBookings.find(b => String(b.id) === String(rxBtn.dataset.prescribe));
            if (!appt) return;
            document.getElementById('rxAppointmentId').value = appt.id;
            document.getElementById('rxPatient').value = appt.patient_name || '';
            document.getElementById('rxSlot').value = (appt.appointment_date || '') + ' ' + (appt.appointment_time || '');
            ['rxDiagnosis', 'rxPrescription', 'rxTreatment', 'rxNotes', 'rxFollowUp'].forEach(id => document.getElementById(id).value = '');
            const al = document.getElementById('rxAlert'); al.className = 'd-none';
            new bootstrap.Modal(document.getElementById('prescribeModal')).show();
        }
    });

    document.getElementById('rxSaveBtn').addEventListener('click', async function() {
        const btn = this;
        const al = document.getElementById('rxAlert');
        const params = {
            action: 'create_prescription',
            appointment_id: document.getElementById('rxAppointmentId').value,
            diagnosis: document.getElementById('rxDiagnosis').value.trim(),
            prescription: document.getElementById('rxPrescription').value.trim(),
            treatment: document.getElementById('rxTreatment').value.trim(),
            notes: document.getElementById('rxNotes').value.trim(),
            follow_up_date: document.getElementById('rxFollowUp').value
        };
        if (!params.diagnosis && !params.prescription) {
            al.className = 'alert alert-danger py-2';
            al.textContent = 'Enter a diagnosis or prescription.';
            return;
        }
        btn.disabled = true;
        btn.textContent = 'Saving...';
        const res = await Realtime.postForm('../api/doctor_medical_records.php', params);
        btn.disabled = false;
        btn.innerHTML = 'Save &amp; Send to Patient';
        if (res && res.success) {
            al.className = 'alert alert-success py-2';
            al.textContent = res.message;
            loadBookings();
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('prescribeModal')).hide();
                showBookingAlert('success', 'Prescription sent to the patient.');
            }, 700);
        } else {
            al.className = 'alert alert-danger py-2';
            al.textContent = (res && res.error) ? res.error : 'Could not save the prescription.';
        }
    });
})();
</script>

<?php include "../includes/layout_footer.php"; ?>
