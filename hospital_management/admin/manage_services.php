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

$services = mysqli_query($conn, "SELECT * FROM services ORDER BY id");

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
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Description</th><th>Action</th></tr></thead>
<tbody>
<?php while ($s = mysqli_fetch_assoc($services)) { ?>
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
<?php } ?>
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
<?php include "../includes/layout_footer.php"; ?>
