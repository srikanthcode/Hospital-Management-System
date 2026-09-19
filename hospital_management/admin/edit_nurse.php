<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: manage_nurses.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: manage_nurses.php"); exit(); }

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $shift = trim($_POST['shift'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $duty = trim($_POST['duty_assignment'] ?? '');
    $pcare = trim($_POST['patient_care'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $stmt = mysqli_prepare($conn, "UPDATE nurses SET name=?,phone=?,email=?,shift=?,department=?,duty_assignment=?,patient_care=?,address=? WHERE id=?");
    mysqli_stmt_bind_param($stmt,"ssssssssi",$name,$phone,$email,$shift,$department,$duty,$pcare,$address,$id);
    if (mysqli_stmt_execute($stmt)) {
        flash_set('success','Nurse updated.');
    } else {
        flash_set('danger','Update failed.');
    }
    header("Location: manage_nurses.php"); exit();
}

$nurse = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM nurses WHERE id = $id"));
if (!$nurse) { header("Location: manage_nurses.php"); exit(); }

$page_title = "Edit Nurse";
$active = "nurses";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-4">
  <h5>Edit Nurse</h5>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
    <div class="row g-2">
      <div class="col-md-6 mb-2"><label class="form-label">Name *</label>
        <input class="form-control" name="name" value="<?php echo e($nurse['name']); ?>" required>
      </div>
      <div class="col-md-6 mb-2"><label class="form-label">Phone</label>
        <input class="form-control" name="phone" value="<?php echo e($nurse['phone']); ?>">
      </div>
      <div class="col-md-6 mb-2"><label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" value="<?php echo e($nurse['email']); ?>">
      </div>
      <div class="col-md-6 mb-2"><label class="form-label">Shift</label>
        <select class="form-select" name="shift">
          <?php foreach(['Morning','Evening','Night'] as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo $nurse['shift']===$s?'selected':''; ?>><?php echo $s; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6 mb-2"><label class="form-label">Department</label>
        <input class="form-control" name="department" value="<?php echo e($nurse['department']); ?>">
      </div>
      <div class="col-md-6 mb-2"><label class="form-label">Duty Assignment</label>
        <input class="form-control" name="duty_assignment" value="<?php echo e($nurse['duty_assignment']); ?>">
      </div>
      <div class="col-md-6 mb-2"><label class="form-label">Patient Care</label>
        <input class="form-control" name="patient_care" value="<?php echo e($nurse['patient_care']); ?>">
      </div>
      <div class="col-12 mb-2"><label class="form-label">Address</label>
        <textarea class="form-control" name="address" rows="2"><?php echo e($nurse['address']); ?></textarea>
      </div>
    </div>
    <button class="btn pink-btn">Update Nurse</button>
    <a href="manage_nurses.php" class="btn btn-secondary">Cancel</a>
  </form>
</div>
<?php include "../includes/layout_footer.php"; ?>
