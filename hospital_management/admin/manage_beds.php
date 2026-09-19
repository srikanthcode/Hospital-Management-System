<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

$page_title = "Bed & Ward Management";
$active = "beds";
$base = "../";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: manage_beds.php"); exit(); }
    $action = $_POST['action'] ?? '';

    if ($action === 'add_bed') {
        $ward_id = (int)($_POST['ward_id'] ?? 0);
        $bed_number = trim($_POST['bed_number'] ?? '');
        if ($ward_id && $bed_number) {
            $stmt = mysqli_prepare($conn, "INSERT INTO beds (ward_id, bed_number, status) VALUES (?,?,'Available')");
            mysqli_stmt_bind_param($stmt,"is",$ward_id,$bed_number);
            if (mysqli_stmt_execute($stmt)) flash_set('success','Bed added.');
            else flash_set('danger','Failed to add bed.');
        }
    } elseif ($action === 'delete_bed') {
        $bed_id = (int)($_POST['bed_id'] ?? 0);
        mysqli_query($conn, "DELETE FROM beds WHERE id = $bed_id AND status = 'Available'");
        flash_set('success','Bed deleted (if available).');
    } elseif ($action === 'add_ward') {
        $ward_name = trim($_POST['ward_name'] ?? '');
        $ward_type = trim($_POST['ward_type'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($ward_name) {
            $stmt = mysqli_prepare($conn, "INSERT INTO wards (ward_name, ward_type, description) VALUES (?,?,?)");
            mysqli_stmt_bind_param($stmt,"sss",$ward_name,$ward_type,$desc);
            if (mysqli_stmt_execute($stmt)) flash_set('success','Ward added.');
        }
    }
    header("Location: manage_beds.php"); exit();
}

$wards = mysqli_query($conn, "SELECT * FROM wards ORDER BY ward_name");
$beds  = mysqli_query($conn, "SELECT b.*, w.ward_name FROM beds b LEFT JOIN wards w ON w.id = b.ward_id ORDER BY w.ward_name, b.bed_number");
?>
<?php include "../includes/layout.php"; ?>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card p-3">
      <h6>Available Beds</h6>
      <h2 class="text-success"><?php echo mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM beds WHERE status='Available'"))['c']; ?></h2>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3">
      <h6>Occupied Beds</h6>
      <h2 class="text-danger"><?php echo mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM beds WHERE status='Occupied'"))['c']; ?></h2>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3">
      <h6>Maintenance</h6>
      <h2 class="text-warning"><?php echo mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM beds WHERE status='Maintenance'"))['c']; ?></h2>
    </div>
  </div>
</div>

<div class="row g-3 mt-2">
  <div class="col-md-5">
    <div class="card p-3">
      <h5>Wards</h5>
      <form method="post" class="row g-1 mb-3">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="add_ward">
        <div class="col"><input class="form-control" name="ward_name" placeholder="Ward Name" required></div>
        <div class="col"><input class="form-control" name="ward_type" placeholder="Type"></div>
        <div class="col"><button class="btn btn-sm pink-btn">Add Ward</button></div>
      </form>
      <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead><tr><th>ID</th><th>Name</th><th>Type</th></tr></thead>
          <tbody>
          <?php while ($w = mysqli_fetch_assoc($wards)) { ?>
            <tr><td><?php echo $w['id']; ?></td><td><?php echo e($w['ward_name']); ?></td><td><?php echo e($w['ward_type']); ?></td></tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-7">
    <div class="card p-3">
      <div class="d-flex justify-content-between mb-2">
        <h5>Beds</h5>
        <button class="btn pink-btn btn-sm" data-bs-toggle="modal" data-bs-target="#addBedModal">+ Add Bed</button>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead><tr><th>ID</th><th>Ward</th><th>Bed #</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
          <?php while ($b = mysqli_fetch_assoc($beds)) {
            $cls = $b['status']==='Available'?'success':($b['status']==='Occupied'?'danger':($b['status']==='Reserved'?'info':'warning'));
          ?>
            <tr>
              <td><?php echo $b['id']; ?></td>
              <td><?php echo e($b['ward_name']); ?></td>
              <td><?php echo e($b['bed_number']); ?></td>
              <td><span class="badge bg-<?php echo $cls; ?>"><?php echo e($b['status']); ?></span></td>
              <td>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                  <input type="hidden" name="action" value="delete_bed">
                  <input type="hidden" name="bed_id" value="<?php echo $b['id']; ?>">
                  <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this bed?');">Del</button>
                </form>
              </td>
            </tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add Bed Modal -->
<div class="modal fade" id="addBedModal"><div class="modal-dialog"><div class="modal-content">
  <div class="modal-header bg-danger text-white"><h5 class="modal-title">Add Bed</h5><button class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <form method="post">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
      <input type="hidden" name="action" value="add_bed">
      <div class="mb-2"><label class="form-label">Ward</label>
        <select name="ward_id" class="form-select" required>
          <option value="">-- Select Ward --</option>
          <?php
          $w2 = mysqli_query($conn, "SELECT id, ward_name FROM wards ORDER BY ward_name");
          while ($w = mysqli_fetch_assoc($w2)) { echo '<option value="'.$w['id'].'">'.e($w['ward_name']).'</option>'; }
          ?>
        </select>
      </div>
      <div class="mb-2"><label class="form-label">Bed Number</label><input class="form-control" name="bed_number" required></div>
      <button class="btn pink-btn">Save</button>
    </form>
  </div>
</div></div></div>

<div class="text-center mt-3"><a href="../admin_dashboard.php" class="btn pink-btn">Back to Dashboard</a></div>
<?php include "../includes/layout_footer.php"; ?>
