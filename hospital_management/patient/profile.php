<?php
require_once "../includes/auth.php";
require_role("patient");
include "../db.php";

$user_id = $_SESSION["user_id"];
$stmt = mysqli_prepare($conn, "SELECT p.* FROM patients p WHERE p.user_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$patient) { flash_set('danger','Patient profile missing.'); header("Location: ../logout.php"); exit(); }

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: profile.php"); exit(); }
    $name = trim($_POST['name'] ?? '');
    $age = (int)($_POST['age'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');

    $stmt = mysqli_prepare($conn, "UPDATE patients SET name=?, age=?, phone=?, email=?, address=?, blood_group=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "sissssi", $name, $age, $phone, $email, $address, $blood_group, $patient['id']);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['name'] = $name;
        flash_set('success','Profile updated.');
    } else {
        flash_set('danger','Failed to update.');
    }
    header("Location: profile.php");
    exit();
}

$page_title = "My Profile";
$active = "pt_profile";
$base = "../";
include "../includes/layout.php";
?>
<div class="card p-4">
<h5>My Profile</h5>
<form method="post">
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
  <div class="row g-2">
    <div class="col-md-6 mb-2"><label class="form-label">Name</label>
      <input class="form-control" name="name" value="<?php echo e($patient['name']); ?>" required>
    </div>
    <div class="col-md-3 mb-2"><label class="form-label">Age</label>
      <input type="number" class="form-control" name="age" value="<?php echo e($patient['age']); ?>">
    </div>
    <div class="col-md-3 mb-2"><label class="form-label">Blood Group</label>
      <input class="form-control" name="blood_group" value="<?php echo e($patient['blood_group']); ?>">
    </div>
    <div class="col-md-6 mb-2"><label class="form-label">Phone</label>
      <input class="form-control" name="phone" value="<?php echo e($patient['phone']); ?>">
    </div>
    <div class="col-md-6 mb-2"><label class="form-label">Email</label>
      <input type="email" class="form-control" name="email" value="<?php echo e($patient['email']); ?>">
    </div>
    <div class="col-12 mb-2"><label class="form-label">Address</label>
      <textarea class="form-control" name="address" rows="2"><?php echo e($patient['address']); ?></textarea>
    </div>
  </div>
  <button class="btn pink-btn">Save Changes</button>
</form>
</div>
<?php include "../includes/layout_footer.php"; ?>
