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

$result = mysqli_query($conn, "SELECT * FROM nurses ORDER BY id DESC");

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
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Shift</th><th>Department</th><th>Duty</th><th>Patient Care</th><th>Action</th></tr></thead>
<tbody>
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
    <form method="post">
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

<div class="text-center mt-3">
  <a href="../admin_dashboard.php" class="btn pink-btn">Back to Dashboard</a>
</div>
<?php include "../includes/layout_footer.php"; ?>
