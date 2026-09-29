<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: manage_patients.php"); exit(); }
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $age = (int)($_POST['age'] ?? 0);
        $gender = trim($_POST['gender'] ?? 'Female');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $blood = trim($_POST['blood_group'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($name) {
            $stmt = mysqli_prepare($conn, "INSERT INTO patients (name,age,gender,phone,email,blood_group,address) VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt,"sisssss",$name,$age,$gender,$phone,$email,$blood,$address);
            if (mysqli_stmt_execute($stmt)) flash_set('success','Patient added.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM patients WHERE id = $id");
        flash_set('success','Patient deleted.');
    }
    header("Location: manage_patients.php"); exit();
}

$search = $_GET['search'] ?? '';
if ($search) {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $result = mysqli_query($conn, "SELECT * FROM patients WHERE name LIKE '%$searchEsc%' OR phone LIKE '%$searchEsc%' OR email LIKE '%$searchEsc%' ORDER BY id DESC");
} else {
    $result = mysqli_query($conn, "SELECT * FROM patients ORDER BY id DESC");
}

$page_title = "Manage Patients";
$active = "patients";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<div class="d-flex justify-content-between mb-2">
  <h5>Manage Patients</h5>
  <button class="btn pink-btn btn-sm" data-bs-toggle="modal" data-bs-target="#addPatientModal">+ Add Patient</button>
</div>
<form method="get" class="mb-3" id="searchForm">
  <div class="input-group">
    <input type="text" class="form-control" name="search" id="searchInput" placeholder="Search by name, phone, email..." value="<?php echo e($search); ?>">
    <button class="btn btn-outline-danger" type="submit">Search</button>
    <a href="manage_patients.php" class="btn btn-outline-secondary">Reset</a>
  </div>
</form>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Name</th><th>Age</th><th>Gender</th><th>Phone</th><th>Email</th><th>Blood</th><th>Action</th></tr></thead>
<tbody id="patientsBody">
<?php if (mysqli_num_rows($result) > 0) { while ($p = mysqli_fetch_assoc($result)) { ?>
  <tr>
    <td><?php echo $p['id']; ?></td>
    <td><?php echo e($p['name']); ?></td>
    <td><?php echo e($p['age']); ?></td>
    <td><?php echo e($p['gender']); ?></td>
    <td><?php echo e($p['phone']); ?></td>
    <td><?php echo e($p['email']); ?></td>
    <td><?php echo e($p['blood_group']); ?></td>
    <td>
      <a href="../view_patient.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-info text-white">View</a>
      <form method="post" class="d-inline">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this patient?');">Del</button>
      </form>
    </td>
  </tr>
<?php } } else { echo '<tr><td colspan="8" class="text-center">No patients found.</td></tr>'; } ?>
</tbody>
</table>
</div>
</div>

<!-- Add Patient Modal -->
<div class="modal fade" id="addPatientModal"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-danger text-white"><h5 class="modal-title">Add Patient</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <form method="post" id="addPatientForm">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
      <input type="hidden" name="action" value="add">
      <div class="mb-2"><label class="form-label">Name *</label><input class="form-control" name="name" required></div>
      <div class="row g-1">
        <div class="col-4 mb-2"><label class="form-label">Age</label><input type="number" class="form-control" name="age"></div>
        <div class="col-4 mb-2"><label class="form-label">Gender</label>
          <select class="form-select" name="gender"><option>Female</option><option>Male</option><option>Other</option></select>
        </div>
        <div class="col-4 mb-2"><label class="form-label">Blood Group</label><input class="form-control" name="blood_group"></div>
      </div>
      <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
      <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
      <div class="mb-2"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="1"></textarea></div>
      <button class="btn pink-btn">Save Patient</button>
    </form>
  </div>
</div></div></div>

<div class="text-center mt-3"><a href="../admin_dashboard.php" class="btn pink-btn">Back to Dashboard</a></div>

<script>
(function() {
    let currentSearch = '<?php echo e($search); ?>';
    let polling = false;

    function renderPatients(patients) {
        const tbody = document.getElementById('patientsBody');
        if (!tbody) return;

        if (!patients || patients.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No patients found.</td></tr>';
            return;
        }

        let html = '';
        patients.forEach(p => {
            const csrf = '<?php echo e(csrf_token()); ?>';
            html += `
                <tr>
                    <td>${Realtime.esc(p.id)}</td>
                    <td>${Realtime.esc(p.name)}</td>
                    <td>${Realtime.esc(p.age)}</td>
                    <td>${Realtime.esc(p.gender)}</td>
                    <td>${Realtime.esc(p.phone)}</td>
                    <td>${Realtime.esc(p.email)}</td>
                    <td>${Realtime.esc(p.blood_group)}</td>
                    <td>
                        <a href="../view_patient.php?id=${Realtime.esc(p.id)}" class="btn btn-sm btn-info text-white">View</a>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf" value="${csrf}">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="${Realtime.esc(p.id)}">
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this patient?');">Del</button>
                        </form>
                    </td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    async function loadPatients() {
        if (polling) return;
        polling = true;

        try {
            const params = new URLSearchParams();
            if (currentSearch) params.set('search', currentSearch);
            params.set('limit', 100);
            params.set('offset', 0);

            const resp = await fetch('../api/patients.php?' + params.toString(), {
                credentials: 'same-origin'
            });
            if (resp.ok) {
                const data = await resp.json();
                if (data && !data.error) {
                    renderPatients(data.patients);
                }
            }
        } catch (e) {
            console.error('Failed to load patients:', e);
        } finally {
            polling = false;
        }
    }

    // Search form handler
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            currentSearch = document.getElementById('searchInput').value.trim();
            history.replaceState(null, '', currentSearch ? '?search=' + encodeURIComponent(currentSearch) : 'manage_patients.php');
            loadPatients();
        });
    }

    // Poll for real-time updates every 10 seconds
    Realtime.startPolling('manage_patients', '../api/patients.php?' + new URLSearchParams({limit: 100, offset: 0}).toString(),
        (data) => {
            if (data && !data.error) {
                renderPatients(data.patients);
            }
        }, 10000);

    // Refresh after modal actions
    const addModal = document.getElementById('addPatientModal');
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', loadPatients);
    }
})();
</script>
<?php include "../includes/layout_footer.php"; ?>