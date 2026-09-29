<?php
require_once "../includes/auth.php";
require_role("doctor");
include "../db.php";

$user_id = $_SESSION["user_id"];
$stmt = mysqli_prepare($conn, "SELECT id,name FROM doctors WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$doctor_id = $doctor["id"] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: appointments.php"); exit(); }
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        if ($action === 'confirm') {
            $stmt = mysqli_prepare($conn, "UPDATE appointments SET status='Confirmed' WHERE id=? AND doctor_id=?");
            mysqli_stmt_bind_param($stmt,"ii",$id,$doctor_id);
            mysqli_stmt_execute($stmt);
            flash_set('success','Appointment confirmed.');
        } elseif ($action === 'complete') {
            $stmt = mysqli_prepare($conn, "UPDATE appointments SET status='Completed' WHERE id=? AND doctor_id=?");
            mysqli_stmt_bind_param($stmt,"ii",$id,$doctor_id);
            mysqli_stmt_execute($stmt);
            flash_set('success','Appointment marked as completed.');
        } elseif ($action === 'cancel') {
            $stmt = mysqli_prepare($conn, "UPDATE appointments SET status='Cancelled' WHERE id=? AND doctor_id=?");
            mysqli_stmt_bind_param($stmt,"ii",$id,$doctor_id);
            mysqli_stmt_execute($stmt);
            flash_set('warning','Appointment cancelled.');
        }
    }
    header("Location: appointments.php");
    exit();
}

$page_title = "My Appointments";
$active = "doc_appointments";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<div class="d-flex justify-content-between mb-2">
  <h5>Appointments</h5>
  <form method="get" class="mb-0" id="filterForm">
    <div class="input-group">
      <input type="text" class="form-control" name="search" id="searchInput" placeholder="Search patient..." value="<?php echo e($_GET['search'] ?? ''); ?>">
      <select class="form-select" name="status" id="statusFilter" style="width:auto">
        <option value="">All Status</option>
        <option value="Pending" <?php echo ($_GET['status'] ?? '')==='Pending'?'selected':''; ?>>Pending</option>
        <option value="Confirmed" <?php echo ($_GET['status'] ?? '')==='Confirmed'?'selected':''; ?>>Confirmed</option>
        <option value="Completed" <?php echo ($_GET['status'] ?? '')==='Completed'?'selected':''; ?>>Completed</option>
        <option value="Cancelled" <?php echo ($_GET['status'] ?? '')==='Cancelled'?'selected':''; ?>>Cancelled</option>
      </select>
      <button class="btn btn-outline-danger" type="submit">Filter</button>
      <a href="appointments.php" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>
</div>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Patient</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr></thead>
<tbody id="appointmentsBody">
  <tr><td colspan="6" class="text-center">Loading...</td></tr>
</tbody>
</table>
</div>
</div>

<script>
(function() {
    let currentParams = {
        search: '<?php echo e($_GET['search'] ?? ''); ?>',
        status: '<?php echo e($_GET['status'] ?? ''); ?>'
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
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No appointments.</td></tr>';
            return;
        }

        const csrf = '<?php echo e(csrf_token()); ?>';
        let html = '';
        appointments.forEach(a => {
            const badge = getStatusBadge(a.status);
            const actions = (a.status === 'Pending' || a.status === 'Confirmed')
                ? `<form method="post" class="d-inline">
                    <input type="hidden" name="csrf" value="${csrf}">
                    <input type="hidden" name="id" value="${Realtime.esc(a.id)}">
                    <button name="action" value="confirm" class="btn btn-sm btn-success">Confirm</button>
                    <button name="action" value="complete" class="btn btn-sm btn-secondary">Complete</button>
                    <button name="action" value="cancel" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this appointment?');">Cancel</button>
                  </form>`
                : '<em class="text-muted">No actions</em>';
            html += `
                <tr>
                    <td>${Realtime.esc(a.id)}</td>
                    <td>${Realtime.esc(a.patient_name)}</td>
                    <td>${Realtime.esc(a.appointment_date)}</td>
                    <td>${Realtime.esc(a.appointment_time)}</td>
                    <td>${badge}</td>
                    <td>${actions}</td>
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
            params.set('limit', 200);
            params.set('offset', 0);

            const resp = await fetch('../api/appointments.php?' + params.toString(), { credentials: 'same-origin' });
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

            const urlParams = new URLSearchParams();
            if (currentParams.search) urlParams.set('search', currentParams.search);
            if (currentParams.status) urlParams.set('status', currentParams.status);
            history.replaceState(null, '', urlParams.toString() ? '?' + urlParams.toString() : 'appointments.php');

            loadAppointments();
        });
    }

    // Poll for real-time updates every 5 seconds
    function pollAppointments() {
        const params = new URLSearchParams();
        if (currentParams.search) params.set('search', currentParams.search);
        if (currentParams.status) params.set('status', currentParams.status);
        params.set('limit', 200);
        params.set('offset', 0);

        return fetch('../api/appointments.php?' + params.toString(), { credentials: 'same-origin' })
            .then(resp => resp.json())
            .then(data => {
                if (data && !data.error) {
                    renderAppointments(data.appointments);
                }
            })
            .catch(e => console.error('Poll error:', e));
    }

    Realtime.startPolling('doctor_appointments', '', pollAppointments, 5000);
    Realtime.configs.doctor_appointments.customPoll = pollAppointments;
})();
</script>
<?php include "../includes/layout_footer.php"; ?>