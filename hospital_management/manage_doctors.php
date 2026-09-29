<?php
require_once "includes/auth.php";
require_role("admin");
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: manage_doctors.php"); exit(); }
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $specialization = trim($_POST['specialization'] ?? '');
        $qualification = trim($_POST['qualification'] ?? '');
        $experience = trim($_POST['experience'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if ($name) {
            $stmt = mysqli_prepare($conn, "INSERT INTO doctors (name,specialization,qualification,experience,phone,email,department) VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt,"sssssss",$name,$specialization,$qualification,$experience,$phone,$email,$department);
            if (mysqli_stmt_execute($stmt)) {
                $did = mysqli_insert_id($conn);
                $pwd = password_hash("Doctor@123", PASSWORD_DEFAULT);
                $uname = $name;
                $s = mysqli_prepare($conn, "INSERT INTO users (name,email,password,role) VALUES (?,?,?,'doctor')");
                mysqli_stmt_bind_param($s,"sss",$uname,$email,$pwd);
                mysqli_stmt_execute($s);
                $uid = mysqli_insert_id($conn);
                mysqli_query($conn, "UPDATE doctors SET user_id = $uid WHERE id = $did");
                flash_set('success','Doctor added. Default password: Doctor@123');
            } else flash_set('danger','Failed to add doctor.');
        } else flash_set('danger','Name is required.');
        header("Location: manage_doctors.php"); exit();
    }
}

$page_title = "Manage Doctors";
$active = "doctors";
$base = ".";
include "includes/layout.php";
?>
<div class="card p-3">
<div class="d-flex justify-content-between mb-2">
  <h5>Manage Doctors</h5>
  <button class="btn pink-btn btn-sm" data-bs-toggle="modal" data-bs-target="#addDoctorModal">+ Add Doctor</button>
</div>
<form method="get" class="mb-3" id="searchForm">
  <div class="input-group">
    <input type="text" class="form-control" name="search" id="searchInput" placeholder="Search by name, specialization, phone, email..." value="<?php echo e($_GET['search'] ?? ''); ?>">
    <button class="btn btn-outline-danger" type="submit">Search</button>
    <a href="manage_doctors.php" class="btn btn-outline-secondary">Reset</a>
  </div>
</form>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Name</th><th>Specialization</th><th>Qualification</th><th>Experience</th><th>Phone</th><th>Email</th><th>Department</th><th>Action</th></tr></thead>
<tbody id="doctorsBody">
  <tr><td colspan="9" class="text-center">Loading...</td></tr>
</tbody>
</table>
</div>
</div>

<div class="modal fade" id="addDoctorModal"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-danger text-white"><h5 class="modal-title">Add Doctor</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <form method="post" id="addDoctorForm">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
      <input type="hidden" name="action" value="add">
      <div class="mb-2"><label class="form-label">Name *</label><input class="form-control" name="name" required></div>
      <div class="mb-2"><label class="form-label">Specialization</label><input class="form-control" name="specialization"></div>
      <div class="mb-2"><label class="form-label">Qualification</label><input class="form-control" name="qualification"></div>
      <div class="mb-2"><label class="form-label">Experience</label><input class="form-control" name="experience"></div>
      <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
      <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
      <div class="mb-2"><label class="form-label">Department</label><input class="form-control" name="department"></div>
      <button class="btn pink-btn">Save Doctor</button>
    </form>
  </div>
</div></div></div>

<div class="text-center mt-3"><a href="admin_dashboard.php" class="btn pink-btn">Back to Dashboard</a></div>

<script>
(function() {
    let currentSearch = '<?php echo e($_GET['search'] ?? ''); ?>';
    let polling = false;

    function renderDoctors(doctors) {
        const tbody = document.getElementById('doctorsBody');
        if (!tbody) return;

        if (!doctors || doctors.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">No doctors.</td></tr>';
            return;
        }

        const csrf = '<?php echo e(csrf_token()); ?>';
        let html = '';
        doctors.forEach(d => {
            html += `
                <tr>
                    <td>${Realtime.esc(d.id)}</td>
                    <td>${Realtime.esc(d.name)}</td>
                    <td>${Realtime.esc(d.specialization)}</td>
                    <td>${Realtime.esc(d.qualification)}</td>
                    <td>${Realtime.esc(d.experience)}</td>
                    <td>${Realtime.esc(d.phone)}</td>
                    <td>${Realtime.esc(d.email)}</td>
                    <td>${Realtime.esc(d.department)}</td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf" value="${csrf}">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="${Realtime.esc(d.id)}">
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?');">Delete</button>
                        </form>
                    </td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    async function loadDoctors() {
        if (polling) return;
        polling = true;

        try {
            const params = new URLSearchParams();
            if (currentSearch) params.set('search', currentSearch);
            params.set('limit', 100);
            params.set('offset', 0);

            const resp = await fetch('api/doctors.php?' + params.toString(), { credentials: 'same-origin' });
            if (resp.ok) {
                const data = await resp.json();
                if (data && !data.error) {
                    renderDoctors(data.doctors);
                }
            }
        } catch (e) {
            console.error('Failed to load doctors:', e);
        } finally {
            polling = false;
        }
    }

    // Initial load
    loadDoctors();

    // Search form handler
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            currentSearch = document.getElementById('searchInput').value.trim();
            history.replaceState(null, '', currentSearch ? '?search=' + encodeURIComponent(currentSearch) : 'manage_doctors.php');
            loadDoctors();
        });
    }

    // Poll for real-time updates every 10 seconds
    Realtime.startPolling('manage_doctors', 'api/doctors.php?' + new URLSearchParams({limit: 100, offset: 0}).toString(),
        (data) => {
            if (data && !data.error) {
                renderDoctors(data.doctors);
            }
        }, 10000);

    // Refresh after modal actions
    const addModal = document.getElementById('addDoctorModal');
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', loadDoctors);
    }
})();
</script>
<?php include "includes/layout_footer.php"; ?>