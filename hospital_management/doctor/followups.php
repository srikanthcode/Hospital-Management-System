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
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: followups.php"); exit(); }
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'done' && $id > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE follow_ups SET status='Done' WHERE id=? AND doctor_id=?");
        mysqli_stmt_bind_param($stmt,"ii",$id,$doctor_id);
        mysqli_stmt_execute($stmt);
        flash_set('success','Follow-up marked as done.');
    } elseif ($action === 'add') {
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $follow_up_date = $_POST['follow_up_date'] ?? '';
        $remarks = trim($_POST['remarks'] ?? '');
        if ($patient_id > 0 && $follow_up_date) {
            $stmt = mysqli_prepare($conn, "INSERT INTO follow_ups (patient_id, doctor_id, follow_up_date, remarks) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($stmt,"iiss",$patient_id,$doctor_id,$follow_up_date,$remarks);
            mysqli_stmt_execute($stmt);
            flash_set('success','Follow-up scheduled.');
        }
    }
    header("Location: followups.php");
    exit();
}

$patients = mysqli_query($conn, "SELECT p.id,p.name FROM patients p
    JOIN appointments a ON a.patient_id = p.id WHERE a.doctor_id = $doctor_id GROUP BY p.id");

$page_title = "Follow-ups";
$active = "doc_followups";
$base = "../";
include "../includes/layout.php";
?>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card p-3">
      <h5>Schedule Follow-up</h5>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="add">
        <div class="mb-2"><label class="form-label">Patient</label>
          <select name="patient_id" class="form-select" required id="followupPatientSelect">
            <option value="">-- Select --</option>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Follow-up Date</label>
          <input type="date" name="follow_up_date" class="form-control" required>
        </div>
        <div class="mb-2"><label class="form-label">Remarks</label>
          <textarea name="remarks" class="form-control" rows="2"></textarea>
        </div>
        <button class="btn pink-btn">Schedule</button>
      </form>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card p-3">
      <h5>Follow-up List</h5>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead><tr><th>ID</th><th>Patient</th><th>Date</th><th>Remarks</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="followupsBody">
            <tr><td colspan="6" class="text-center">Loading...</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
    let polling = false;
    let patientsCache = [];

    function getStatusBadge(status) {
        return '<span class="badge bg-' + (status === 'Done' ? 'success' : 'warning') + '">' + Realtime.esc(status) + '</span>';
    }

    function renderFollowups(followups) {
        const tbody = document.getElementById('followupsBody');
        if (!tbody) return;

        if (!followups || followups.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No follow-ups.</td></tr>';
            return;
        }

        const csrf = '<?php echo e(csrf_token()); ?>';
        let html = '';
        followups.forEach(r => {
            const badge = getStatusBadge(r.status);
            const action = r.status !== 'Done'
                ? `<form method="post" class="d-inline">
                    <input type="hidden" name="csrf" value="${csrf}">
                    <input type="hidden" name="action" value="done">
                    <input type="hidden" name="id" value="${Realtime.esc(r.id)}">
                    <button class="btn btn-sm btn-success">Mark Done</button>
                  </form>`
                : '-';
            html += `
                <tr>
                    <td>${Realtime.esc(r.id)}</td>
                    <td>${Realtime.esc(r.patient_name)}</td>
                    <td>${Realtime.esc(r.follow_up_date)}</td>
                    <td>${Realtime.esc(r.remarks)}</td>
                    <td>${badge}</td>
                    <td>${action}</td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    function populatePatients() {
        const select = document.getElementById('followupPatientSelect');
        if (select && patientsCache.length) {
            select.innerHTML = '<option value="">-- Select --</option>' +
                patientsCache.map(p => '<option value="' + Realtime.esc(p.id) + '">' + Realtime.esc(p.name) + '</option>').join('');
        }
    }

    async function loadAll() {
        if (polling) return;
        polling = true;

        try {
            const [patientsResp, followupsResp] = await Promise.all([
                fetch('../api/doctor_patients.php?limit=1000', { credentials: 'same-origin' }),
                fetch('../api/doctor_followups.php?limit=100', { credentials: 'same-origin' })
            ]);

            if (patientsResp.ok) {
                const p = await patientsResp.json();
                if (p && !p.error) { patientsCache = p.patients || []; populatePatients(); }
            }

            if (followupsResp.ok) {
                const f = await followupsResp.json();
                if (f && !f.error) { renderFollowups(f.followups); }
            }
        } catch (e) {
            console.error('Failed to load follow-ups:', e);
        } finally {
            polling = false;
        }
    }

    // Initial load
    loadAll();

    // Poll for real-time updates every 10 seconds
    Realtime.startPolling('doctor_followups', '../api/doctor_followups.php?limit=100',
        (data) => {
            if (data && !data.error) {
                renderFollowups(data.followups);
            }
        }, 10000);

    // Refresh after form submission
    document.querySelector('form[method="post"]').addEventListener('submit', function() {
        setTimeout(loadAll, 500);
    });
})();
</script>
<?php include "../includes/layout_footer.php"; ?>