<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: manage_services.php"); exit(); }
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name) {
            $stmt = mysqli_prepare($conn, "INSERT INTO services (name,category,description) VALUES (?,?,?)");
            mysqli_stmt_bind_param($stmt,"sss",$name,$category,$desc);
            if (mysqli_stmt_execute($stmt)) flash_set('success','Service added.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM services WHERE id = $id");
        flash_set('success','Service deleted.');
    }
    header("Location: manage_services.php"); exit();
}

$search = $_GET['search'] ?? '';
if ($search) {
    $searchEsc = mysqli_real_escape_string($conn, $search);
    $result = mysqli_query($conn, "SELECT * FROM services WHERE name LIKE '%$searchEsc%' OR category LIKE '%$searchEsc%' OR description LIKE '%$searchEsc%' ORDER BY id DESC");
} else {
    $result = mysqli_query($conn, "SELECT * FROM services ORDER BY id DESC");
}

$page_title = "Manage Services";
$active = "services";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<div class="d-flex justify-content-between mb-2">
  <h5>Manage Services</h5>
  <button class="btn pink-btn btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">+ Add Service</button>
</div>
<form method="get" class="mb-3" id="searchForm">
  <div class="input-group">
    <input type="text" class="form-control" name="search" id="searchInput" placeholder="Search by name, category..." value="<?php echo e($_GET['search'] ?? ''); ?>">
    <button class="btn btn-outline-danger" type="submit">Search</button>
    <a href="manage_services.php" class="btn btn-outline-secondary">Reset</a>
  </div>
</form>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Description</th><th>Action</th></tr></thead>
<tbody id="servicesBody">
<?php if (mysqli_num_rows($result) > 0) { while ($s = mysqli_fetch_assoc($result)) { ?>
  <tr>
    <td><?php echo $s['id']; ?></td>
    <td><?php echo e($s['name']); ?></td>
    <td><?php echo e($s['category']); ?></td>
    <td><?php echo e($s['description']); ?></td>
    <td>
      <form method="post" class="d-inline">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?');">Delete</button>
      </form>
    </td>
  </tr>
<?php } } else { echo '<tr><td colspan="5" class="text-center">No services.</td></tr>'; } ?>
</tbody>
</table>
</div>
</div>

<div class="modal fade" id="addServiceModal"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-danger text-white"><h5 class="modal-title">Add Service</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <form method="post">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
      <input type="hidden" name="action" value="add">
      <div class="mb-2"><label class="form-label">Name *</label><input class="form-control" name="name" required></div>
      <div class="mb-2"><label class="form-label">Category</label><input class="form-control" name="category"></div>
      <div class="mb-2"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2"></textarea></div>
      <button class="btn pink-btn">Save</button>
    </form>
  </div>
</div></div></div>

<div class="text-center mt-3"><a href="../admin_dashboard.php" class="btn pink-btn">Back to Dashboard</a></div>

<script>
(function() {
    let currentSearch = '<?php echo e($_GET['search'] ?? ''); ?>';
    let polling = false;

    function renderServices(services) {
        const tbody = document.getElementById('servicesBody');
        if (!tbody) return;

        if (!services || services.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No services.</td></tr>';
            return;
        }

        const csrf = '<?php echo e(csrf_token()); ?>';
        let html = '';
        services.forEach(s => {
            html += `
                <tr>
                    <td>${Realtime.esc(s.id)}</td>
                    <td>${Realtime.esc(s.name)}</td>
                    <td>${Realtime.esc(s.category)}</td>
                    <td>${Realtime.esc(s.description)}</td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf" value="${csrf}">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="${Realtime.esc(s.id)}">
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?');">Delete</button>
                        </form>
                    </td>
                </tr>`;
        });
        tbody.innerHTML = html;
    }

    async function loadServices() {
        if (polling) return;
        polling = true;

        try {
            const params = new URLSearchParams();
            if (currentSearch) params.set('search', currentSearch);
            params.set('limit', 100);
            params.set('offset', 0);

            const resp = await fetch('../api/services.php?' + params.toString(), { credentials: 'same-origin' });
            if (resp.ok) {
                const data = await resp.json();
                if (data && !data.error) {
                    renderServices(data.services);
                }
            }
        } catch (e) {
            console.error('Failed to load services:', e);
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
            history.replaceState(null, '', currentSearch ? '?search=' + encodeURIComponent(currentSearch) : 'manage_services.php');
            loadServices();
        });
    }

    // Poll for real-time updates every 10 seconds
    Realtime.startPolling('manage_services', '../api/services.php?' + new URLSearchParams({limit: 100, offset: 0}).toString(),
        (data) => {
            if (data && !data.error) {
                renderServices(data.services);
            }
        }, 10000);

    // Refresh after modal actions
    const addModal = document.getElementById('addServiceModal');
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', loadServices);
    }
})();
</script>
<?php include "../includes/layout_footer.php"; ?>