<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: manage_emergency.php"); exit(); }
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $pname = trim($_POST['patient_name'] ?? '');
        $age = (int)($_POST['age'] ?? 0);
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $ambulance_id = (int)($_POST['ambulance_id'] ?? 0);
        $doctor_id = (int)($_POST['doctor_id'] ?? 0);
        $type = trim($_POST['emergency_type'] ?? '');
        $details = trim($_POST['details'] ?? '');

        if ($pname) {
            $stmt = mysqli_prepare($conn, "INSERT INTO emergency_records (patient_name,age,phone,address,ambulance_id,doctor_id,emergency_type,details) VALUES (?,?,?,?,?,?,?,?)");
            $aid = $ambulance_id > 0 ? $ambulance_id : null;
            $did = $doctor_id > 0 ? $doctor_id : null;
            mysqli_stmt_bind_param($stmt,"sissiiss",$pname,$age,$phone,$address,$aid,$did,$type,$details);
            if (mysqli_stmt_execute($stmt)) flash_set('success','Emergency record created.');
            else flash_set('danger','Failed.');
        }
    } elseif ($action === 'resolve') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "UPDATE emergency_records SET status='Resolved' WHERE id=?");
        mysqli_stmt_bind_param($stmt,"i",$id);
        mysqli_stmt_execute($stmt);
        flash_set('success','Emergency resolved.');
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM emergency_records WHERE id = $id");
        flash_set('success','Record deleted.');
    }
    header("Location: manage_emergency.php"); exit();
}

$ambulances = mysqli_query($conn, "SELECT id, vehicle_number FROM ambulance_services WHERE status='Available' ORDER BY vehicle_number");
$doctors = mysqli_query($conn, "SELECT id,name FROM doctors ORDER BY name");
$records = mysqli_query($conn, "SELECT er.*, d.name AS doctor_name FROM emergency_records er LEFT JOIN doctors d ON d.id = er.doctor_id ORDER BY er.id DESC");
$activeCount = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM emergency_records WHERE status='Active'"))['c'];
$resolvedCount = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM emergency_records WHERE status='Resolved'"))['c'];

$page_title = "Emergency Management";
$active = "emergency";
$base = "../";
include "../includes/layout.php";
?>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card p-3"><h6>Active Emergencies</h6>
      <h2 class="text-danger" id="statActive"><?php echo $activeCount; ?></h2>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3"><h6>Resolved Today</h6>
      <h2 class="text-success" id="statResolved"><?php echo $resolvedCount; ?></h2>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-5">
    <div class="card p-3">
      <h5>Register Emergency</h5>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="add">
        <div class="mb-2"><label class="form-label">Patient Name *</label><input class="form-control" name="patient_name" required></div>
        <div class="mb-2"><label class="form-label">Age</label><input type="number" class="form-control" name="age"></div>
        <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
        <div class="mb-2"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="1"></textarea></div>
        <div class="mb-2"><label class="form-label">Emergency Type</label><input class="form-control" name="emergency_type" placeholder="e.g. Cardiac, Trauma"></div>
        <div class="mb-2"><label class="form-label">Details</label><textarea class="form-control" name="details" rows="2"></textarea></div>
        <div class="mb-2"><label class="form-label">Ambulance</label>
          <select class="form-select" name="ambulance_id" id="emergencyAmbulanceSelect"><option value="">-- None --</option>
          <?php while ($a = mysqli_fetch_assoc($ambulances)) { echo '<option value="'.$a['id'].'">'.e($a['vehicle_number']).'</option>'; } ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Doctor</label>
          <select class="form-select" name="doctor_id" id="emergencyDoctorSelect"><option value="">-- None --</option>
          <?php while ($d = mysqli_fetch_assoc($doctors)) { echo '<option value="'.$d['id'].'">'.e($d['name']).'</option>'; } ?>
          </select>
        </div>
        <button class="btn pink-btn">Save Emergency</button>
      </form>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card p-3">
      <h5>Emergency Records</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead><tr><th>ID</th><th>Patient</th><th>Type</th><th>Phone</th><th>Doctor</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
          <tbody id="emergencyBody">
          <?php if (mysqli_num_rows($records) > 0) { while ($r = mysqli_fetch_assoc($records)) { ?>
            <tr>
              <td><?php echo $r['id']; ?></td>
              <td><?php echo e($r['patient_name']); ?></td>
              <td><?php echo e($r['emergency_type']); ?></td>
              <td><?php echo e($r['phone']); ?></td>
              <td><?php echo e($r['doctor_name']); ?></td>
              <td><span class="badge bg-<?php echo $r['status']==='Active'?'danger':'success'; ?>"><?php echo e($r['status']); ?></span></td>
              <td><?php echo e($r['created_at']); ?></td>
              <td>
                <?php if ($r['status']==='Active'): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                  <input type="hidden" name="action" value="resolve">
                  <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                  <button class="btn btn-sm btn-success">Resolve</button>
                </form>
                <?php endif; ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                  <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?');">Del</button>
                </form>
              </td>
            </tr>
          <?php } } else { echo '<tr><td colspan="8" class="text-center">No emergencies.</td></tr>'; } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="text-center mt-3"><a href="../admin_dashboard.php" class="btn pink-btn">Back to Dashboard</a></div>

<script>
(function() {
    let polling = false;
    let ambulancesCache = [];
    let doctorsCache = [];

    function getStatusBadge(status) {
        return '<span class="badge bg-' + (status === 'Active' ? 'danger' : 'success') + '">' + Realtime.esc(status) + '</span>';
    }

    function renderStats(active, resolved) {
        Realtime.updateText('statActive', active);
        Realtime.updateText('statResolved', resolved);
    }

    function renderRecords(records) {
        const tbody = document.getElementById('emergencyBody');
        if (!tbody) return;

        if (!records || records.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No emergencies.</td></tr>';
            return;
        }

        const csrf = '<?php echo e(csrf_token()); ?>';
        let html = '';
        records.forEach(r => {
            const badge = getStatusBadge(r.status);
            const actions = r.status === 'Active'
                ? `<form method="post" class="d-inline">
                    <input type="hidden" name="csrf" value="${csrf}">
                    <input type="hidden" name="action" value="resolve">
                    <input type="hidden" name="id" value="${Realtime.esc(r.id)}">
                    <button class="btn btn-sm btn-success">Resolve</button>
                  </form>`
                : '';
            html += `
                <tr>
                    <td>${Realtime.esc(r.id)}</td>
                    <td>${Realtime.esc(r.patient_name)}</td>
                    <td>${Realtime.esc(r.emergency_type)}</td>
                    <td>${Realtime.esc(r.phone)}</td>
                    <td>${Realtime.esc(r.doctor_name || '')}</td>
                    <td>${badge}</td>
                    <td>${Realtime.esc(r.created_at)}</td>
                    <td>
                        ${actions}
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf" value="${csrf}">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="${Realtime.esc(r.id)}">
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?');">Del</button>
                        </form>
                    </td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    function populateDropdowns() {
        const aSelect = document.getElementById('emergencyAmbulanceSelect');
        if (aSelect && ambulancesCache.length) {
            aSelect.innerHTML = '<option value="">-- None --</option>' +
                ambulancesCache.map(a => '<option value="' + Realtime.esc(a.id) + '">' + Realtime.esc(a.vehicle_number) + '</option>').join('');
        }
        const dSelect = document.getElementById('emergencyDoctorSelect');
        if (dSelect && doctorsCache.length) {
            dSelect.innerHTML = '<option value="">-- None --</option>' +
                doctorsCache.map(d => '<option value="' + Realtime.esc(d.id) + '">' + Realtime.esc(d.name) + '</option>').join('');
        }
    }

    async function loadAll() {
        if (polling) return;
        polling = true;

        try {
            const [statsResp, recordsResp, ambResp, docResp] = await Promise.all([
                fetch('../api/emergency.php', { credentials: 'same-origin' }),
                fetch('../api/emergency.php?limit=100', { credentials: 'same-origin' }),
                fetch('../api/ambulance.php?limit=1000', { credentials: 'same-origin' }),
                fetch('../api/doctors.php?limit=1000', { credentials: 'same-origin' })
            ]);

            if (statsResp.ok) {
                const stats = await statsResp.json();
                if (stats && !stats.error) {
                    renderStats(stats.active_count, 0);
                }
            }

            if (recordsResp.ok) {
                const rec = await recordsResp.json();
                if (rec && !rec.error) {
                    const activeCount = rec.emergencies.filter(r => r.status === 'Active').length;
                    const resolvedCount = rec.emergencies.filter(r => r.status === 'Resolved').length;
                    renderStats(activeCount, resolvedCount);
                    renderRecords(rec.emergencies);
                }
            }

            if (ambResp.ok) {
                const a = await ambResp.json();
                if (a && !a.error) { ambulancesCache = a.ambulances || []; populateDropdowns(); }
            }

            if (docResp.ok) {
                const d = await docResp.json();
                if (d && !d.error) { doctorsCache = d.doctors || []; populateDropdowns(); }
            }

        } catch (e) {
            console.error('Failed to load emergencies:', e);
        } finally {
            polling = false;
        }
    }

    // Initial load
    loadAll();

    // Poll for real-time updates every 5 seconds (emergencies need faster updates)
    Realtime.startPolling('manage_emergency', '../api/emergency.php?limit=100',
        (data) => {
            if (data && !data.error) {
                const activeCount = data.emergencies.filter(r => r.status === 'Active').length;
                const resolvedCount = data.emergencies.filter(r => r.status === 'Resolved').length;
                renderStats(activeCount, resolvedCount);
                renderRecords(data.emergencies);
            }
        }, 5000);
})();
</script>
<?php include "../includes/layout_footer.php"; ?>