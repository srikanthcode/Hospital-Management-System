<?php
require_once "../includes/auth.php";
require_role("nurse");
include "../db.php";

$user_id = $_SESSION["user_id"];

$page_title = "Nurse Dashboard";
$active = "nurse_home";
$base = "../";
include "../includes/layout.php";
?>

<div class="card mb-4 p-4">
  <h5>Welcome, Nurse <?php echo e($_SESSION['name']); ?></h5>
  <p class="text-muted mb-0">Your shift today: <strong id="statShift">-</strong></p>
</div>

<div class="row g-3">
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Shift</h6>
      <h2 id="statShiftVal" style="font-size:18px">-</h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Department</h6>
      <h2 id="statDepartment" style="font-size:18px">-</h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Duty Assignment</h6>
      <h2 id="statDuty" style="font-size:18px">-</h2>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <h6>Patient Care</h6>
      <h2 id="statPatientCare" style="font-size:18px">-</h2>
    </div>
  </div>
</div>

<div class="row g-4 mt-3">
  <div class="col-lg-6">
    <div class="card p-4">
      <h5 class="mb-3">Today's Duties</h5>
      <div id="todayAppointments">
        <div class="text-center text-muted p-3">Loading...</div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card p-4">
      <h5 class="mb-3">Active Emergencies</h5>
      <div id="emergenciesBody">
        <div class="text-center text-muted p-3">Loading...</div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
    let polling = false;

    function getStatusBadge(status) {
        return '<span class="badge bg-' + (status === 'Active' ? 'danger' : 'success') + '">' + Realtime.esc(status) + '</span>';
    }

    function renderDuties(data) {
        if (!data) return;

        Realtime.updateText('statShiftVal', data.shift || '-');
        Realtime.updateText('statDepartment', data.department || '-');
        Realtime.updateText('statDuty', data.duty_assignment || '-');
        Realtime.updateText('statPatientCare', data.patient_care || '-');

        const apptDiv = document.getElementById('todayAppointments');
        if (apptDiv) {
            const appointments = data.appointments || [];
            if (appointments.length === 0) {
                apptDiv.innerHTML = '<div class="text-center text-muted p-3">No appointments today.</div>';
            } else {
                let html = '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th></tr></thead><tbody>';
                appointments.forEach(a => {
                    const colors = { 'Confirmed': 'success', 'Completed': 'secondary', 'Cancelled': 'danger', 'Pending': 'warning' };
                    html += `<tr>
                        <td>${a.appointment_time ? Realtime.formatTime(a.appointment_time) : '-'}</td>
                        <td>${Realtime.esc(a.patient_name || '-')}</td>
                        <td>${Realtime.esc(a.doctor_name || '-')}</td>
                        <td><span class="badge bg-${colors[a.status] || 'warning'}">${Realtime.esc(a.status)}</span></td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
                apptDiv.innerHTML = html;
            }
        }

        const emDiv = document.getElementById('emergenciesBody');
        if (emDiv) {
            const emergencies = data.emergencies || [];
            if (emergencies.length === 0) {
                emDiv.innerHTML = '<div class="text-center text-muted p-3">No active emergencies.</div>';
            } else {
                let html = '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Patient</th><th>Type</th><th>Location</th><th>Ambulance</th></tr></thead><tbody>';
                emergencies.forEach(e => {
                    html += `<tr>
                        <td>${Realtime.esc(e.patient_name || '-')}</td>
                        <td>${Realtime.esc(e.emergency_type || '-')}</td>
                        <td>${Realtime.esc(e.location || '-')}</td>
                        <td>${Realtime.esc(e.vehicle_number || '-')}</td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
                emDiv.innerHTML = html;
            }
        }
    }

    async function loadDuties() {
        if (polling) return;
        polling = true;

        try {
            const resp = await fetch('../api/nurse_today_duties.php', { credentials: 'same-origin' });
            if (resp.ok) {
                const data = await resp.json();
                if (data && !data.error) {
                    renderDuties(data);
                }
            }
        } catch (e) {
            console.error('Failed to load nurse duties:', e);
        } finally {
            polling = false;
        }
    }

    // Initial load
    loadDuties();

    // Poll for real-time updates every 10 seconds
    Realtime.startPolling('nurse_dashboard', '../api/nurse_today_duties.php',
        (data) => {
            if (data && !data.error) {
                renderDuties(data);
            }
        }, 10000);
})();
</script>
<?php include "../includes/layout_footer.php"; ?>