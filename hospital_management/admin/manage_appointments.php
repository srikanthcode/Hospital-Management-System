<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: manage_appointments.php"); exit(); }
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        if ($action === 'delete') {
            mysqli_query($conn, "DELETE FROM appointments WHERE id = $id");
            flash_set('success','Appointment deleted.');
        } elseif ($action === 'status') {
            $status = trim($_POST['status'] ?? '');
            if (!in_array($status, ['Pending','Confirmed','Completed','Cancelled'])) {
                flash_set('danger','Invalid status.');
                header("Location: manage_appointments.php"); exit();
            }
            $stmt = mysqli_prepare($conn, "UPDATE appointments SET status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt,"si",$status,$id);
            mysqli_stmt_execute($stmt);
            flash_set('success','Status updated to '.$status.'.');
        }
    }
    header("Location: manage_appointments.php"); exit();
}

$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_date = $_GET['date'] ?? '';

$page_title = "Manage Appointments";
$active = "appointments";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<h5>Manage Appointments</h5>
<form method="get" class="row g-1 mb-3" id="filterForm">
  <div class="col-md-3">
    <input type="text" class="form-control" name="search" id="searchInput" placeholder="Search patient/doctor..." value="<?php echo e($search); ?>">
  </div>
  <div class="col-md-2">
    <select class="form-select" name="status" id="statusFilter">
      <option value="">All Status</option>
      <?php foreach(['Pending','Confirmed','Completed','Cancelled'] as $st): ?>
        <option value="<?php echo $st; ?>" <?php echo $filter_status===$st?'selected':''; ?>><?php echo $st; ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <input type="date" class="form-control" name="date" id="dateFilter" value="<?php echo e($filter_date); ?>">
  </div>
  <div class="col-auto"><button class="btn btn-outline-danger" type="submit">Filter</button></div>
  <div class="col-auto"><a href="manage_appointments.php" class="btn btn-outline-secondary">Reset</a></div>
</form>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr></thead>
<tbody id="appointmentsBody">
  <tr><td colspan="8" class="text-center">Loading...</td></tr>
</tbody>
</table>
</div>
</div>
<div class="text-center mt-3"><a href="../admin_dashboard.php" class="btn pink-btn">Back to Dashboard</a></div>

<script>
(function() {
    let currentParams = {
        search: '<?php echo e($search); ?>',
        status: '<?php echo e($filter_status); ?>',
        date: '<?php echo e($filter_date); ?>'
    };
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
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No appointments.</td></tr>';
            return;
        }

        const csrf = '<?php echo e(csrf_token()); ?>';
        let html = '';
        appointments.forEach(a => {
            html += `
                <tr>
                    <td>${Realtime.esc(a.id)}</td>
                    <td>${Realtime.esc(a.patient_name)}</td>
                    <td>${Realtime.esc(a.doctor_name)}</td>
                    <td>${Realtime.esc(a.service_name || '')}</td>
                    <td>${Realtime.esc(a.appointment_date)}</td>
                    <td>${Realtime.esc(a.appointment_time)}</td>
                    <td>${getStatusBadge(a.status)}</td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf" value="${csrf}">
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="id" value="${Realtime.esc(a.id)}">
                            <select name="status" class="form-select form-select-sm d-inline" style="width:auto" onchange="this.form.submit()">
                                <option value="Pending" ${a.status==='Pending'?'selected':''}>Pending</option>
                                <option value="Confirmed" ${a.status==='Confirmed'?'selected':''}>Confirmed</option>
                                <option value="Completed" ${a.status==='Completed'?'selected':''}>Completed</option>
                                <option value="Cancelled" ${a.status==='Cancelled'?'selected':''}>Cancelled</option>
                            </select>
                        </form>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf" value="${csrf}">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="${Realtime.esc(a.id)}">
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this appointment?');">Del</button>
                        </form>
                    </td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    async function loadAppointments() {
        if (polling) return;
        polling = true;

        try {
            const params = new URLSearchParams();
            if (currentParams.search) params.set('search', currentParams.search);
            if (currentParams.status) params.set('status', currentParams.status);
            if (currentParams.date) params.set('date', currentParams.date);
            params.set('limit', 200);
            params.set('offset', 0);

            const resp = await fetch('../api/appointments.php?' + params.toString(), {
                credentials: 'same-origin'
            });
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

    // Filter form handler
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            currentParams.search = document.getElementById('searchInput').value.trim();
            currentParams.status = document.getElementById('statusFilter').value;
            currentParams.date = document.getElementById('dateFilter').value;

            const urlParams = new URLSearchParams();
            if (currentParams.search) urlParams.set('search', currentParams.search);
            if (currentParams.status) urlParams.set('status', currentParams.status);
            if (currentParams.date) urlParams.set('date', currentParams.date);
            history.replaceState(null, '', urlParams.toString() ? '?' + urlParams.toString() : 'manage_appointments.php');

            loadAppointments();
        });
    }

    // Poll for real-time updates every 5 seconds
    function pollAppointments() {
        const params = new URLSearchParams();
        if (currentParams.search) params.set('search', currentParams.search);
        if (currentParams.status) params.set('status', currentParams.status);
        if (currentParams.date) params.set('date', currentParams.date);
        params.set('limit', 200);
        params.set('offset', 0);

        return fetch('../api/appointments.php?' + params.toString(), {
            credentials: 'same-origin'
        }).then(resp => resp.json())
          .then(data => {
              if (data && !data.error) {
                  renderAppointments(data.appointments);
              }
          })
          .catch(e => console.error('Poll error:', e));
    }

    Realtime.startPolling('manage_appointments', '', pollAppointments, 5000);

    // Override the polling URL builder
    Realtime.configs.manage_appointments.url = null;
    Realtime.configs.manage_appointments.customPoll = pollAppointments;
})();
</script>
<?php include "../includes/layout_footer.php"; ?>