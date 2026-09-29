<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: manage_admissions.php"); exit(); }
    $action = $_POST['action'] ?? '';

    if ($action === 'admit') {
        $patient_id = (int)($_POST['patient_id'] ?? 0);
        $bed_id = (int)($_POST['bed_id'] ?? 0);
        $doctor_id = (int)($_POST['doctor_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');

        if ($patient_id && $bed_id) {
            $r = mysqli_query($conn, "SELECT status FROM beds WHERE id = $bed_id");
            $bed = mysqli_fetch_assoc($r);
            if ($bed && $bed['status'] === 'Available') {
                $stmt = mysqli_prepare($conn, "INSERT INTO admissions (patient_id, bed_id, doctor_id, reason, admission_date, status) VALUES (?,?,?,?,CURRENT_TIMESTAMP,'Admitted')");
                mysqli_stmt_bind_param($stmt,"iiis",$patient_id,$bed_id,$doctor_id,$reason);
                if (mysqli_stmt_execute($stmt)) {
                    $stmt = mysqli_prepare($conn, "UPDATE beds SET status='Occupied' WHERE id=?");
                    mysqli_stmt_bind_param($stmt,"i",$bed_id);
                    mysqli_stmt_execute($stmt);
                    flash_set('success','Patient admitted and bed assigned.');
                } else {
                    flash_set('danger','Failed to admit patient.');
                }
            } else {
                flash_set('danger','Bed is not available.');
            }
        }
    } elseif ($action === 'discharge') {
        $adm_id = (int)($_POST['admission_id'] ?? 0);
        $r = mysqli_query($conn, "SELECT bed_id FROM admissions WHERE id=$adm_id");
        $a = mysqli_fetch_assoc($r);
        if ($a) {
            $stmt = mysqli_prepare($conn, "UPDATE admissions SET status='Discharged', discharge_date=CURRENT_TIMESTAMP WHERE id=?");
            mysqli_stmt_bind_param($stmt,"i",$adm_id);
            mysqli_stmt_execute($stmt);
            if ($a['bed_id']) {
                $stmt = mysqli_prepare($conn, "UPDATE beds SET status='Available' WHERE id=?");
                mysqli_stmt_bind_param($stmt,"i",$a['bed_id']);
                mysqli_stmt_execute($stmt);
            }
            flash_set('success','Patient discharged. Bed marked available.');
        }
    }
    header("Location: manage_admissions.php"); exit();
}

$patients = mysqli_query($conn, "SELECT id,name FROM patients ORDER BY name");
$doctors  = mysqli_query($conn, "SELECT id,name FROM doctors ORDER BY name");
$beds     = mysqli_query($conn, "SELECT b.id, b.bed_number, w.ward_name, b.status FROM beds b LEFT JOIN wards w ON w.id=b.ward_id WHERE b.status='Available' ORDER BY w.ward_name, b.bed_number");
$admissions = mysqli_query($conn, "SELECT a.*, p.name AS patient_name, b.bed_number, w.ward_name, d.name AS doctor_name
    FROM admissions a
    JOIN patients p ON p.id = a.patient_id
    LEFT JOIN beds b ON b.id = a.bed_id
    LEFT JOIN wards w ON w.id = b.ward_id
    LEFT JOIN doctors d ON d.id = a.doctor_id
    ORDER BY a.id DESC");

$admittedCount = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM admissions WHERE status='Admitted'"))['c'];
$dischargedCount = (int)mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM admissions WHERE status='Discharged'"))['c'];

$page_title = "Patient Admissions";
$active = "admissions";
$base = "../";
include "../includes/layout.php";
?>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card p-3">
      <h6>Currently Admitted</h6>
      <h2 class="text-danger" id="statAdmitted"><?php echo $admittedCount; ?></h2>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3">
      <h6>Total Discharged</h6>
      <h2 class="text-success" id="statDischarged"><?php echo $dischargedCount; ?></h2>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-5">
    <div class="card p-3">
      <h5>Admit Patient</h5>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="admit">
        <div class="mb-2"><label class="form-label">Patient</label>
          <select name="patient_id" class="form-select" required id="admitPatientSelect">
            <option value="">-- Select --</option>
            <?php while ($p = mysqli_fetch_assoc($patients)) { echo '<option value="'.$p['id'].'">'.e($p['name']).'</option>'; } ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Available Bed</label>
          <select name="bed_id" class="form-select" required id="admitBedSelect">
            <option value="">-- Select --</option>
            <?php while ($b = mysqli_fetch_assoc($beds)) { echo '<option value="'.$b['id'].'">'.e($b['ward_name'].' - '.$b['bed_number']).'</option>'; } ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Doctor</label>
          <select name="doctor_id" class="form-select" id="admitDoctorSelect">
            <option value="">-- Select --</option>
            <?php while ($d = mysqli_fetch_assoc($doctors)) { echo '<option value="'.$d['id'].'">'.e($d['name']).'</option>'; } ?>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Reason</label>
          <textarea name="reason" class="form-control" rows="2"></textarea>
        </div>
        <button class="btn pink-btn">Admit</button>
      </form>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card p-3">
      <h5>Admission Records</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead><tr><th>ID</th><th>Patient</th><th>Ward</th><th>Bed</th><th>Doctor</th><th>Admitted</th><th>Status</th><th>Action</th></tr></thead>
          <tbody id="admissionsBody">
          <?php if (mysqli_num_rows($admissions) > 0) { while ($a = mysqli_fetch_assoc($admissions)) { ?>
            <tr>
              <td><?php echo $a['id']; ?></td>
              <td><?php echo e($a['patient_name']); ?></td>
              <td><?php echo e($a['ward_name']); ?></td>
              <td><?php echo e($a['bed_number']); ?></td>
              <td><?php echo e($a['doctor_name']); ?></td>
              <td><?php echo e($a['admission_date']); ?></td>
              <td><span class="badge bg-<?php echo $a['status']==='Admitted'?'danger':'success'; ?>"><?php echo e($a['status']); ?></span></td>
              <td>
                <?php if ($a['status']==='Admitted'): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                  <input type="hidden" name="action" value="discharge">
                  <input type="hidden" name="admission_id" value="<?php echo $a['id']; ?>">
                  <button class="btn btn-sm btn-success" onclick="return confirm('Discharge this patient?');">Discharge</button>
                </form>
                <?php else: ?> - <?php endif; ?>
              </td>
            </tr>
          <?php } } else { echo '<tr><td colspan="8" class="text-center">No admissions.</td></tr>'; } ?>
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
    let patientsCache = [];
    let doctorsCache = [];
    let bedsCache = [];

    function getStatusBadge(status) {
        return '<span class="badge bg-' + (status === 'Admitted' ? 'danger' : 'success') + '">' + Realtime.esc(status) + '</span>';
    }

    function renderStats(admitted, discharged) {
        Realtime.updateText('statAdmitted', admitted);
        Realtime.updateText('statDischarged', discharged);
    }

    function renderAdmissions(admissions) {
        const tbody = document.getElementById('admissionsBody');
        if (!tbody) return;

        if (!admissions || admissions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No admissions.</td></tr>';
            return;
        }

        const csrf = '<?php echo e(csrf_token()); ?>';
        let html = '';
        admissions.forEach(a => {
            const badge = getStatusBadge(a.status);
            const action = a.status === 'Admitted'
                ? `<form method="post" class="d-inline">
                    <input type="hidden" name="csrf" value="${csrf}">
                    <input type="hidden" name="action" value="discharge">
                    <input type="hidden" name="admission_id" value="${Realtime.esc(a.id)}">
                    <button class="btn btn-sm btn-success" onclick="return confirm('Discharge this patient?');">Discharge</button>
                  </form>`
                : '-';
            html += `
                <tr>
                    <td>${Realtime.esc(a.id)}</td>
                    <td>${Realtime.esc(a.patient_name)}</td>
                    <td>${Realtime.esc(a.ward_name || '')}</td>
                    <td>${Realtime.esc(a.bed_number || '')}</td>
                    <td>${Realtime.esc(a.doctor_name || '')}</td>
                    <td>${Realtime.esc(a.admission_date)}</td>
                    <td>${badge}</td>
                    <td>${action}</td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    function populateDropdowns() {
        // Populate patient select
        const pSelect = document.getElementById('admitPatientSelect');
        if (pSelect && patientsCache.length) {
            pSelect.innerHTML = '<option value="">-- Select --</option>' +
                patientsCache.map(p => '<option value="' + Realtime.esc(p.id) + '">' + Realtime.esc(p.name) + '</option>').join('');
        }
        // Populate doctor select
        const dSelect = document.getElementById('admitDoctorSelect');
        if (dSelect && doctorsCache.length) {
            dSelect.innerHTML = '<option value="">-- Select --</option>' +
                doctorsCache.map(d => '<option value="' + Realtime.esc(d.id) + '">' + Realtime.esc(d.name) + '</option>').join('');
        }
        // Populate bed select
        const bSelect = document.getElementById('admitBedSelect');
        if (bSelect && bedsCache.length) {
            bSelect.innerHTML = '<option value="">-- Select --</option>' +
                bedsCache.map(b => '<option value="' + Realtime.esc(b.id) + '">' + Realtime.esc(b.ward_name + ' - ' + b.bed_number) + '</option>').join('');
        }
    }

    async function loadAll() {
        if (polling) return;
        polling = true;

        try {
            const [statsResp, admissionsResp, patientsResp, doctorsResp, bedsResp] = await Promise.all([
                fetch('../api/admissions.php?limit=1', { credentials: 'same-origin' }),
                fetch('../api/admissions.php?limit=200', { credentials: 'same-origin' }),
                fetch('../api/patients.php?limit=1000', { credentials: 'same-origin' }),
                fetch('../api/doctors.php?limit=1000', { credentials: 'same-origin' }),
                fetch('../api/beds.php', { credentials: 'same-origin' })
            ]);

            if (statsResp.ok) {
                const stats = await statsResp.json();
                if (stats && !stats.error) {
                    renderStats(stats.total, 0);
                }
            }

            if (admissionsResp.ok) {
                const adm = await admissionsResp.json();
                if (adm && !adm.error) {
                    const admittedCount = adm.admissions.filter(a => a.status === 'Admitted').length;
                    const dischargedCount = adm.admissions.filter(a => a.status === 'Discharged').length;
                    renderStats(admittedCount, dischargedCount);
                    renderAdmissions(adm.admissions);
                }
            }

            if (patientsResp.ok) {
                const p = await patientsResp.json();
                if (p && !p.error) { patientsCache = p.patients; populateDropdowns(); }
            }

            if (doctorsResp.ok) {
                const d = await doctorsResp.json();
                if (d && !d.error) { doctorsCache = d.doctors; populateDropdowns(); }
            }

            if (bedsResp.ok) {
                const b = await bedsResp.json();
                if (b && !b.error) {
                    bedsCache = b.beds.filter(b => b.status === 'Available');
                    populateDropdowns();
                }
            }

        } catch (e) {
            console.error('Failed to load admissions:', e);
        } finally {
            polling = false;
        }
    }

    // Initial load
    loadAll();

    // Poll for real-time updates every 10 seconds
    Realtime.startPolling('manage_admissions', '../api/admissions.php?limit=200',
        (data) => {
            if (data && !data.error) {
                const admittedCount = data.admissions.filter(a => a.status === 'Admitted').length;
                const dischargedCount = data.admissions.filter(a => a.status === 'Discharged').length;
                renderStats(admittedCount, dischargedCount);
                renderAdmissions(data.admissions);
            }
        }, 10000);

    // Refresh after form submission
    document.querySelector('form[action="admit"]').addEventListener('submit', function() {
        setTimeout(loadAll, 500);
    });
})();
</script>
<?php include "../includes/layout_footer.php"; ?>