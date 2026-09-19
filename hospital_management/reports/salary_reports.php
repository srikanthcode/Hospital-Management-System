<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'] ?? '';
    if (!csrf_check($token)) { flash_set('danger','Invalid CSRF token.'); header("Location: salary_reports.php"); exit(); }
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $staff_type = trim($_POST['staff_type'] ?? '');
        $staff_id = (int)($_POST['staff_id'] ?? 0);
        $staff_name = trim($_POST['staff_name'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $month = trim($_POST['salary_month'] ?? '');
        $pay_status = trim($_POST['payment_status'] ?? 'Pending');
        $pay_date = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;
        $remarks = trim($_POST['remarks'] ?? '');

        if ($staff_type && $staff_id && $month) {
            if ($staff_name === '') {
                $tbl = $staff_type === 'doctor' ? 'doctors' : 'nurses';
                $rn = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM $tbl WHERE id=" . (int)$staff_id));
                $staff_name = $rn['name'] ?? '';
            }
            $stmt = mysqli_prepare($conn, "INSERT INTO salary_records (staff_type,staff_id,staff_name,amount,salary_month,payment_status,payment_date,remarks) VALUES (?,?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt,"sisdssss",$staff_type,$staff_id,$staff_name,$amount,$month,$pay_status,$pay_date,$remarks);
            if (mysqli_stmt_execute($stmt)) flash_set('success','Salary record added.');
            else flash_set('danger','Failed.');
        }
    } elseif ($action === 'pay') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "UPDATE salary_records SET payment_status='Paid', payment_date=CURDATE() WHERE id=?");
        mysqli_stmt_bind_param($stmt,"i",$id);
        mysqli_stmt_execute($stmt);
        flash_set('success','Marked as Paid.');
    }
    header("Location: salary_reports.php"); exit();
}

$filter_month = $_GET['month'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';

$where = "1=1";
if ($filter_month) { $fm = mysqli_real_escape_string($conn,$filter_month); $where .= " AND sr.salary_month='$fm'"; }
if ($filter_type) { $ft = mysqli_real_escape_string($conn,$filter_type); $where .= " AND sr.staff_type='$ft'"; }
if ($filter_status) { $fs = mysqli_real_escape_string($conn,$filter_status); $where .= " AND sr.payment_status='$fs'"; }

$salaries = mysqli_query($conn, "SELECT sr.* FROM salary_records sr WHERE $where ORDER BY sr.id DESC");
$doctors = mysqli_query($conn, "SELECT id,name FROM doctors ORDER BY name");
$nurses = mysqli_query($conn, "SELECT id,name FROM nurses ORDER BY name");

// Summary
$summary = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total, SUM(amount) total_amount, SUM(CASE WHEN payment_status='Paid' THEN amount ELSE 0 END) paid_amount, SUM(CASE WHEN payment_status='Pending' THEN amount ELSE 0 END) pending_amount FROM salary_records sr WHERE $where"));

$page_title = "Salary Reports";
$active = "reports";
$base = "../";
include "../includes/layout.php";
?>
<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="stat-card text-center"><h6>Total Records</h6><h2><?php echo $summary['total'] ?? 0; ?></h2></div></div>
  <div class="col-md-3"><div class="stat-card text-center"><h6>Total Amount</h6><h2>Rs. <?php echo number_format($summary['total_amount'] ?? 0); ?></h2></div></div>
  <div class="col-md-3"><div class="stat-card text-center"><h6>Paid</h6><h2 class="text-success">Rs. <?php echo number_format($summary['paid_amount'] ?? 0); ?></h2></div></div>
  <div class="col-md-3"><div class="stat-card text-center"><h6>Pending</h6><h2 class="text-danger">Rs. <?php echo number_format($summary['pending_amount'] ?? 0); ?></h2></div></div>
</div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card p-3">
      <h5>Add Salary Record</h5>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="add">
        <div class="mb-2"><label class="form-label">Staff Type</label>
          <select class="form-select" name="staff_type" id="salaryType" required onchange="updateStaffList()">
            <option value="doctor">Doctor</option>
            <option value="nurse">Nurse</option>
          </select>
        </div>
        <div class="mb-2"><label class="form-label">Staff</label>
          <select class="form-select" name="staff_id" id="salaryStaff" required onchange="document.getElementById('salaryName').value=this.options[this.selectedIndex].text">
            <option value="">-- Select --</option>
          </select>
          <input type="hidden" name="staff_name" id="salaryName">
        </div>
        <div class="mb-2"><label class="form-label">Amount (Rs.)</label><input type="number" class="form-control" name="amount" step="0.01" required></div>
        <div class="mb-2"><label class="form-label">Month (e.g. Jan-2026)</label><input class="form-control" name="salary_month" required></div>
        <div class="mb-2"><label class="form-label">Payment Status</label>
          <select class="form-select" name="payment_status"><option>Pending</option><option>Paid</option></select>
        </div>
        <div class="mb-2"><label class="form-label">Payment Date</label><input type="date" class="form-control" name="payment_date"></div>
        <div class="mb-2"><label class="form-label">Remarks</label><input class="form-control" name="remarks"></div>
        <button class="btn pink-btn">Add Salary Record</button>
      </form>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card p-3">
      <h5>Salary Records</h5>
      <form method="get" class="row g-1 mb-3">
        <div class="col-md-3"><input class="form-control" name="month" placeholder="Month" value="<?php echo e($filter_month); ?>"></div>
        <div class="col-md-2">
          <select class="form-select" name="type"><option value="">All</option><option value="doctor" <?php echo $filter_type==='doctor'?'selected':''; ?>>Doctor</option><option value="nurse" <?php echo $filter_type==='nurse'?'selected':''; ?>>Nurse</option></select>
        </div>
        <div class="col-md-2">
          <select class="form-select" name="status"><option value="">All</option><option value="Paid" <?php echo $filter_status==='Paid'?'selected':''; ?>>Paid</option><option value="Pending" <?php echo $filter_status==='Pending'?'selected':''; ?>>Pending</option></select>
        </div>
        <div class="col-auto"><button class="btn btn-outline-danger btn-sm">Filter</button></div>
        <div class="col-auto"><a href="salary_reports.php" class="btn btn-outline-secondary btn-sm">Reset</a></div>
        <div class="col-auto"><button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print</button></div>
      </form>
      <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead><tr><th>ID</th><th>Type</th><th>Name</th><th>Amount</th><th>Month</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
          <tbody>
          <?php if (mysqli_num_rows($salaries) > 0) { while ($s = mysqli_fetch_assoc($salaries)) { ?>
            <tr>
              <td><?php echo $s['id']; ?></td>
              <td><span class="badge bg-<?php echo $s['staff_type']==='doctor'?'primary':'success'; ?>"><?php echo ucfirst(e($s['staff_type'])); ?></span></td>
              <td><?php echo e($s['staff_name']); ?></td>
              <td>Rs. <?php echo number_format($s['amount']); ?></td>
              <td><?php echo e($s['salary_month']); ?></td>
              <td><span class="badge bg-<?php echo $s['payment_status']==='Paid'?'success':'danger'; ?>"><?php echo e($s['payment_status']); ?></span></td>
              <td><?php echo e($s['payment_date']); ?></td>
              <td>
                <?php if ($s['payment_status']==='Pending'): ?>
                <form method="post" class="d-inline">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                  <input type="hidden" name="action" value="pay">
                  <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                  <button class="btn btn-sm btn-success">Mark Paid</button>
                </form>
                <?php else: ?> - <?php endif; ?>
              </td>
            </tr>
          <?php } } else { echo '<tr><td colspan="8" class="text-center">No salary records.</td></tr>'; } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const doctors = <?php echo json_encode(mysqli_fetch_all($doctors)); ?>;
const nurses = <?php echo json_encode(mysqli_fetch_all($nurses)); ?>;

function updateStaffList(){
  const type = document.getElementById('salaryType').value;
  const sel = document.getElementById('salaryStaff');
  sel.innerHTML = '<option value="">-- Select --</option>';
  const list = type === 'doctor' ? doctors : nurses;
  list.forEach(r => {
    const opt = document.createElement('option');
    opt.value = r[0]; opt.text = r[1];
    sel.appendChild(opt);
  });
}
updateStaffList();
</script>

<div class="text-center mt-3"><a href="index.php" class="btn pink-btn">Back to Reports</a></div>
<?php include "../includes/layout_footer.php"; ?>
