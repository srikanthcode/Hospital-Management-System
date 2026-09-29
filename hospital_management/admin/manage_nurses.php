<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: manage_nurses.php"); exit(); }

    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        mysqli_query($conn, "DELETE FROM nurses WHERE id = $id");
        flash_set('success','Nurse deleted.');
    } elseif ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $shift = trim($_POST['shift'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($name) {
            $stmt = mysqli_prepare($conn, "INSERT INTO nurses (name,phone,email,shift,department,address) VALUES (?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt,"ssssss",$name,$phone,$email,$shift,$department,$address);
            if (mysqli_stmt_execute($stmt)) {
                $nid = mysqli_insert_id($conn);
                $pwd = password_hash("Nurse@123", PASSWORD_DEFAULT);
                $uname = $name;
                $s = mysqli_prepare($conn, "INSERT INTO users (name,email,password,role) VALUES (?,?,?,'nurse')");
                mysqli_stmt_bind_param($s,"sss",$uname,$email,$pwd);
                mysqli_stmt_execute($s);
                $uid = mysqli_insert_id($conn);
                mysqli_query($conn, "UPDATE nurses SET user_id = $uid WHERE id = $nid");
                flash_set('success','Nurse added. Default password: Nurse@123');
            } else flash_set('danger','Failed to add nurse.');
        } else flash_set('danger','Name is required.');
        header("Location: manage_nurses.php"); exit();
    }
}

$search = $_GET['search'] ?? '';
if ($search) {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $result = mysqli_query($conn, "SELECT * FROM nurses WHERE name LIKE '%$searchEsc%' OR phone LIKE '%$searchEsc%' OR email LIKE '%$searchEsc%' OR department LIKE '%$searchEsc%' ORDER BY id DESC");
} else {
    $result = mysqli_query($conn, "SELECT * FROM nurses ORDER BY id DESC");
}

$page_title = "Manage Nurses";
$active = "nurses";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<div class="d-flex justify-content-between mb-2">
  <h5>Manage Nurses</h5>
  <button class="btn pink-btn" data-bs-toggle="modal" data-bs-target="#addNurseModal">+ Add Nurse</button>
</div>
<form method="get" class="mb-3" id="searchForm">
  <div class="input-group">
    <input type="text" class="form-control" name="search" id="searchInput" placeholder="Search by name, phone, email, department..." value="<?php echo e($_GET['search'] ?? ''); ?>">
    <button class="btn btn-outline-danger" type="submit">Search</button>
    <a href="manage_nurses.php" class="btn btn-outline-secondary">Reset</a>
  </div>
</form>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Shift</th><th>Department</th><th>Duty</th><th>Patient Care</th><th>Action</th></tr></thead>
<tbody id="nursesBody">
<?php if (mysqli_num_rows($result) > 0) { while ($n = mysqli_fetch_assoc($result)) { ?>
  <tr>
    <td><?php echo $n['id']; ?></td>
    <td><?php echo e($n['name']); ?></td>
    <td><?php echo e($n['phone']); ?></td>
    <td><?php echo e($n['email']); ?></td>
    <td><?php echo e($n['shift']); ?></td>
    <td><?php echo e($n['department']); ?></td>
    <td><?php echo e($n['duty_assignment']); ?></td>
    <td><?php echo e($n['patient_care']); ?></td>
    <td>
      <a href="edit_nurse.php?id=<?php echo $n['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
      <form method="post" class="d-inline">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo $n['id']; ?>">
        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this nurse?');">Delete</button>
      </form>
    </td>
  </tr>
<?php } } else { echo '<tr><td colspan="9" class="text-center">No nurses.</td></tr>'; } ?>
</tbody>
</table>
</div>
</div>

<!-- Add Nurse Modal -->
<div class="modal fade" id="addNurseModal"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-danger text-white"><h5 class="modal-title">Add Nurse</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <form method="post" id="addNurseForm">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
      <input type="hidden" name="action" value="add">
      <div class="mb-2"><label class="form-label">Name *</label><input class="form-control" name="name" required></div>
      <div class="mb-2"><label class="form-label">Phone</label><input class="form-control" name="phone"></div>
      <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
      <div class="mb-2"><label class="form-label">Shift</label>
        <select class="form-select" name="shift"><option value="">--</option><option>Morning</option><option>Evening</option><option>Night</option></select>
      </div>
      <div class="mb-2"><label class="form-label">Department</label><input class="form-control" name="department"></div>
      <div class="mb-2"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2"></textarea></div>
      <button class="btn pink-btn">Save Nurse</button>
    </form>
  </div>
</div></div></div>

<div class="text-center mt-3"><a href="../admin_dashboard.php" class="btn pink-btn">Back to Dashboard</a></div>

<script>
(function() {
    let currentSearch = '<?php echo e($_GET['search'] ?? ''); ?>';
    let polling = false;

    function renderNurses(nurses) {
        const tbody = document.getElementById('nursesBody');
        if (!tbody) return;

        if (!nurses || nurses.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">No nurses.</td></tr>';
            return;
        }

        const csrf = '<?php echo e(csrf_token()); ?>';
        let html = '';
        nurses.forEach(n => {
            html += `
                <tr>
                    <td>${Realtime.esc(n.id)}</td>
                    <td>${Realtime.esc(n.name)}</td>
                    <td>${Realtime.esc(n.phone)}</td>
                    <td>${Realtime.esc(n.email)}</td>
                    <td>${Realtime.esc(n.shift)}</td>
                    <td>${Realtime.esc(n.department)}</td>
                    <td>${Realtime.esc(n.duty_assignment)}</td>
                    <td>${Realtime.esc(n.patient_care)}</td>
                    <td>
                        <a href="edit_nurse.php?id=${Realtime.esc(n.id)}" class="btn btn-sm btn-warning">Edit</a>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf" value="${csrf}">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="${Realtime.esc(n.id)}">
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this nurse?');">Delete</button>
                        </form>
                    </td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    async function loadNurses() {
        if (polling) return;
        polling = true;

        try {
            const params = new URLSearchParams();
            if (currentSearch) params.set('search', currentSearch);
            params.set('limit', 100);
            params.set('offset', 0);

            const resp = await fetch('../api/nurses.php?' + params.toString(), {
                credentials: 'same-origin'
            });
            if (resp.ok) {
                const data = await resp.json();
                if (data && !data.error) {
                    renderNurses(data.nurses);
                }
            }
        } catch (e) {
            console.error('Failed to load nurses:', e);
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
            history.replaceState(null, '', currentSearch ? '?search=' + encodeURIComponent(currentSearch) : 'manage_nurses.php');
            loadNurses();
        });
    }

    // Poll for real-time updates every 10 seconds
    Realtime.startPolling('manage_nurses', '../api/nurses.php?' + new URLSearchParams({limit: 100, offset: 0}).toString(),
        (data) => {
            if (data && !data.error) {
                renderNurses(data.nurses);
            }
        }, 10000);

    // Refresh after modal actions
    const addModal = document.getElementById('addNurseModal');
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', loadNurses);
    }
})();
</script>
<?php include "../includes/layout_footer.php"; ?>