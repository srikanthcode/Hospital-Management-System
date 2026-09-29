<?php
/**
 * Read-only printable salary report.
 * Creating / paying salaries lives in admin/manage_salary.php.
 */
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

$filter_month  = trim($_GET['month'] ?? '');
$filter_type   = trim($_GET['type'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$where = "1=1";
if ($filter_month !== '')  { $where .= " AND sr.salary_month='" . mysqli_real_escape_string($conn, $filter_month) . "'"; }
if ($filter_type !== '')   { $where .= " AND sr.staff_type='" . mysqli_real_escape_string($conn, $filter_type) . "'"; }
if ($filter_status !== '') { $where .= " AND sr.payment_status='" . mysqli_real_escape_string($conn, $filter_status) . "'"; }

$salaries = mysqli_query($conn, "SELECT sr.* FROM salary_records sr WHERE $where ORDER BY sr.salary_month DESC, sr.id DESC");
$summary  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total,
    COALESCE(SUM(amount),0) AS total_amount,
    COALESCE(SUM(CASE WHEN payment_status='Paid' THEN amount ELSE 0 END),0) AS paid_amount,
    COALESCE(SUM(CASE WHEN payment_status='Pending' THEN amount ELSE 0 END),0) AS pending_amount
    FROM salary_records sr WHERE $where"));

$by_staff = mysqli_query($conn, "SELECT staff_type, staff_name,
    COUNT(*) AS entries,
    COALESCE(SUM(amount),0) AS total,
    COALESCE(SUM(CASE WHEN payment_status='Paid' THEN amount ELSE 0 END),0) AS paid
    FROM salary_records sr WHERE $where
    GROUP BY staff_type, staff_name ORDER BY staff_type, staff_name");

$page_title = "Salary Reports";
$active = "reports";
$base = "../";
include "../includes/layout.php";
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="m-0">Salary report</h5>
  <div class="d-flex gap-2">
    <a href="../admin/manage_salary.php" class="btn pink-btn btn-sm">Manage salaries (assign / pay out)</a>
    <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print</button>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="stat-card text-center"><h6>Total Records</h6><h2><?php echo (int)($summary['total'] ?? 0); ?></h2></div></div>
  <div class="col-md-3"><div class="stat-card text-center"><h6>Total Amount</h6><h2>Rs. <?php echo number_format((float)($summary['total_amount'] ?? 0), 2); ?></h2></div></div>
  <div class="col-md-3"><div class="stat-card text-center"><h6>Paid</h6><h2 class="text-success">Rs. <?php echo number_format((float)($summary['paid_amount'] ?? 0), 2); ?></h2></div></div>
  <div class="col-md-3"><div class="stat-card text-center"><h6>Pending</h6><h2 class="text-danger">Rs. <?php echo number_format((float)($summary['pending_amount'] ?? 0), 2); ?></h2></div></div>
</div>

<div class="card p-3 mb-3">
  <form method="get" class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label">Month</label>
      <input class="form-control" name="month" placeholder="e.g. Sep-2026" value="<?php echo e($filter_month); ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Type</label>
      <select class="form-select" name="type">
        <option value="">All</option>
        <option value="doctor" <?php echo $filter_type === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
        <option value="nurse" <?php echo $filter_type === 'nurse' ? 'selected' : ''; ?>>Nurse</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Status</label>
      <select class="form-select" name="status">
        <option value="">All</option>
        <option value="Paid" <?php echo $filter_status === 'Paid' ? 'selected' : ''; ?>>Paid</option>
        <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
      </select>
    </div>
    <div class="col-auto"><button class="btn btn-outline-danger btn-sm">Filter</button></div>
    <div class="col-auto"><a href="salary_reports.php" class="btn btn-outline-secondary btn-sm">Reset</a></div>
  </form>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card p-3">
      <h6 class="mb-2">Salary records</h6>
      <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead>
            <tr>
              <th>ID</th><th>Type</th><th>Name</th><th>Amount</th><th>Month</th>
              <th>Status</th><th>Payment Date</th><th>Remarks</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($salaries && mysqli_num_rows($salaries) > 0) { while ($s = mysqli_fetch_assoc($salaries)) { ?>
            <tr>
              <td><?php echo (int)$s['id']; ?></td>
              <td><span class="badge bg-<?php echo $s['staff_type'] === 'doctor' ? 'primary' : 'success'; ?>"><?php echo e(ucfirst($s['staff_type'])); ?></span></td>
              <td><?php echo e($s['staff_name']); ?></td>
              <td class="text-nowrap">Rs. <?php echo number_format((float)$s['amount'], 2); ?></td>
              <td><?php echo e($s['salary_month']); ?></td>
              <td><span class="badge bg-<?php echo $s['payment_status'] === 'Paid' ? 'success' : 'warning text-dark'; ?>"><?php echo e($s['payment_status']); ?></span></td>
              <td><?php echo $s['payment_date'] ? e($s['payment_date']) : '-'; ?></td>
              <td class="small"><?php echo e($s['remarks']); ?></td>
            </tr>
          <?php } } else { ?>
            <tr><td colspan="8" class="text-center text-muted py-4">No salary records for this filter.</td></tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card p-3">
      <h6 class="mb-2">Totals by staff</h6>
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
          <thead><tr><th>Name</th><th>Entries</th><th>Assigned</th><th>Paid</th></tr></thead>
          <tbody>
          <?php
          $hasStaff = false;
          if ($by_staff) {
              while ($b = mysqli_fetch_assoc($by_staff)) {
                  $hasStaff = true;
                  ?>
                  <tr>
                    <td><?php echo e($b['staff_name']); ?><br><small class="text-muted"><?php echo e(ucfirst($b['staff_type'])); ?></small></td>
                    <td><?php echo (int)$b['entries']; ?></td>
                    <td>Rs. <?php echo number_format((float)$b['total'], 0); ?></td>
                    <td>Rs. <?php echo number_format((float)$b['paid'], 0); ?></td>
                  </tr>
                  <?php
              }
          }
          if (!$hasStaff) {
              echo '<tr><td colspan="4" class="text-center text-muted">No data.</td></tr>';
          }
          ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="text-center mt-3">
  <a href="index.php" class="btn pink-btn">Back to Reports</a>
</div>
<?php include "../includes/layout_footer.php"; ?>
