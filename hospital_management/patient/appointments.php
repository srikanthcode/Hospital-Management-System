<?php
require_once "../includes/auth.php";
require_role("patient");
include "../db.php";

$user_id = $_SESSION["user_id"];

$page_title = "My Appointments";
$active = "pt_appts";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<div class="d-flex justify-content-between mb-2">
  <h5>My Appointments</h5>
  <a href="book_appointment.php" class="btn pink-btn">+ Book New</a>
</div>
<div class="table-responsive">
<table class="table table-bordered">
<thead><tr><th>ID</th><th>Doctor</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
<tbody id="appointmentsBody">
  <tr><td colspan="6" class="text-center">Loading...</td></tr>
</tbody>
</table>
</div>
</div>

<script>
(function() {
    let polling = false;

    function getStatusBadge(status) {
        const colors = {
            'Confirmed': 'success',
            'Completed': 'secondary',
            'Cancelled': 'danger',
            'Pending': 'warning'
        };
        return '<span class="badge bg-' + colors[status] + '">' + Realtime.esc(status) + '</span>';
    }

    function renderAppointments(appointments) {
        const tbody = document.getElementById('appointmentsBody');
        if (!tbody) return;

        if (!appointments || appointments.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No appointments.</td></tr>';
            return;
        }

        let html = '';
        appointments.forEach(r => {
            html += `
                <tr>
                    <td>${Realtime.esc(r.id)}</td>
                    <td>${Realtime.esc(r.doctor_name)}</td>
                    <td>${Realtime.esc(r.service_name || '')}</td>
                    <td>${Realtime.esc(r.appointment_date)}</td>
                    <td>${Realtime.esc(r.appointment_time)}</td>
                    <td>${getStatusBadge(r.status)}</td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    async function loadAppointments() {
        if (polling) return;
        polling = true;

        try {
            const resp = await fetch('../api/appointments.php?limit=200', { credentials: 'same-origin' });
            if (resp.ok) {
                const data = await resp.json();
                if (data && !data.error) {
                    renderAppointments(data.appointments);
                }
            }
        } catch (e) {
            console.error('Failed to load appointments:', e);
        } finally {
            polling = false;
        }
    }

    // Initial load
    loadAppointments();

    // Poll for real-time updates every 10 seconds
    Realtime.startPolling('patient_appointments', '../api/appointments.php?limit=200',
        (data) => {
            if (data && !data.error) {
                renderAppointments(data.appointments);
            }
        }, 10000);
})();
</script>
<?php include "../includes/layout_footer.php"; ?>