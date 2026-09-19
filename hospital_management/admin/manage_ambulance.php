<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: manage_ambulance.php"); exit(); }
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $vn = trim($_POST['vehicle_number'] ?? '');
        $dn = trim($_POST['driver_name'] ?? '');
        $dp = trim($_POST['driver_phone'] ?? '');
        if ($vn) {
            $status = 'Available';
            $stmt = mysqli_prepare($conn, "INSERT INTO ambulance_services (vehicle_number,driver_name,driver_phone,status) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($stmt,"ssss",$vn,$dn,$dp,$status);
            if (mysqli_stmt_execute($stmt)) flash_set('success','Ambulance added.');
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        if ($id && $status) {
            $stmt = mysqli_prepare($conn, "UPDATE ambulance_services SET status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt,"si",$status,$id);
            mysqli_stmt_execute($stmt);
            flash_set('success','Status updated.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        mysqli_query($conn, "DELETE FROM ambulance_services WHERE id = $id");
        flash_set('success','Deleted.');
    }
    header("Location: manage_ambulance.php"); exit();
}

$ambulances = mysqli_query($conn, "SELECT * FROM ambulance_services ORDER BY id");

$page_title = "Ambulance Services";
$active = "ambulance";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-3">
<div class="d-flex justify-content-between mb-2">
  <h5>Ambulance Services</h5>
  <button class="btn pink-btn btn-sm" data-bs-toggle="modal" data-bs-target="#addAmbModal">+ Add Ambulance</button>
</div>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Vehicle #</th><th>Driver Name</th><th>Driver Phone</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php while ($a = mysqli_fetch_assoc($ambulances)) { ?>
  <tr>
    <td><?php echo $a['id']; ?></td>
    <td><?php echo e($a['vehicle_number']); ?></td>
    <td><?php echo e($a['driver_name']); ?></td>
    <td><?php echo e($a['driver_phone']); ?></td>
    <td>
      <form method="post" class="d-inline">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
        <select name="status" class="form-select form-select-sm d-inline" style="width:auto" onchange="this.form.submit()">
          <?php foreach(['Available','On Duty','Maintenance'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo $a['status']===$s?'selected':''; ?>><?php echo $s; ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </td>
    <td>
      <form method="post" class="d-inline">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo $a['id']; ?>">
        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?');">Del</button>
      </form>
    </td>
  </tr>
<?php } ?>
</tbody>
</table>
</div>
</div>

<div class="modal fade" id="addAmbModal"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-danger text-white"><h5 class="modal-title">Add Ambulance</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <form method="post">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
      <input type="hidden" name="action" value="add">
      <div class="mb-2"><label class="form-label">Vehicle Number *</label><input class="form-control" name="vehicle_number" required></div>
      <div class="mb-2"><label class="form-label">Driver Name</label><input class="form-control" name="driver_name"></div>
      <div class="mb-2"><label class="form-label">Driver Phone</label><input class="form-control" name="driver_phone"></div>
      <button class="btn pink-btn">Save</button>
    </form>
  </div>
</div></div></div>

<div class="text-center mt-3"><a href="../admin_dashboard.php" class="btn pink-btn">Back to Dashboard</a></div>
<?php include "../includes/layout_footer.php"; ?>
