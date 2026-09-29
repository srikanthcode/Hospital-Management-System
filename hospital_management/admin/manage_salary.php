<?php
require_once "../includes/auth.php";
require_role("admin");
include "../db.php";

/* ------------------------------------------------------------------ */
/* Small helpers (api_helper.php is not included here because it forces */
/* a JSON content type, which would break this HTML page).              */
/* ------------------------------------------------------------------ */
if (!function_exists('salary_log_activity')) {
    function salary_log_activity($conn, $user_id, $user_name, $action, $entity_type, $entity_id, $description, $color = 'primary')
    {
        try {
            $stmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id,user_name,action,entity_type,entity_id,description,color) VALUES (?,?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, "issssss", $user_id, $user_name, $action, $entity_type, $entity_id, $description, $color);
            mysqli_stmt_execute($stmt);
        } catch (Exception $e) {
            // logging must never break a payment
        }
    }
}
if (!function_exists('salary_notify_admins')) {
    function salary_notify_admins($conn, $type, $title, $message)
    {
        try {
            $res = mysqli_query($conn, "SELECT id FROM users WHERE role='admin'");
            while ($row = mysqli_fetch_assoc($res)) {
                $stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id,type,title,message,reference_type) VALUES (?,?,?,?, 'salary')");
                mysqli_stmt_bind_param($stmt, "isss", $row['id'], $type, $title, $message);
                mysqli_stmt_execute($stmt);
            }
        } catch (Exception $e) {
            // ignore
        }
    }
}

/* ------------------------------------------------------------------ */
/* POST actions                                                        */
/* ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token  = $_POST['csrf'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!csrf_check($token)) {
        flash_set('danger', 'Invalid CSRF token. Please try again.');
        header("Location: manage_salary.php");
        exit();
    }

    if ($action === 'assign') {
        $staff_type = trim($_POST['staff_type'] ?? '');
        $staff_id   = (int)($_POST['staff_id'] ?? 0);
        $staff_name = trim($_POST['staff_name'] ?? '');
        $amount     = (float)($_POST['amount'] ?? 0);
        $month      = trim($_POST['salary_month'] ?? '');
        $status     = trim($_POST['payment_status'] ?? 'Pending');
        $pay_date   = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;
        $remarks    = trim($_POST['remarks'] ?? '');

        if (!in_array($staff_type, ['doctor', 'nurse'], true)) {
            flash_set('danger', 'Choose Doctor or Nurse.');
        } elseif ($staff_id <= 0) {
            flash_set('danger', 'Select the staff member to assign the salary to.');
        } elseif ($month === '') {
            flash_set('danger', 'Salary month is required (for example Sep-2026).');
        } elseif ($amount <= 0) {
            flash_set('danger', 'Amount must be greater than zero.');
        } elseif (!in_array($status, ['Pending', 'Paid'], true)) {
            flash_set('danger', 'Payment status must be Pending or Paid.');
        } else {
            $tbl = $staff_type === 'doctor' ? 'doctors' : 'nurses';
            if ($staff_name === '') {
                $rn = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM $tbl WHERE id=" . (int)$staff_id));
                $staff_name = $rn['name'] ?? '';
            }
            if ($staff_name === '') {
                flash_set('danger', 'That staff member no longer exists.');
            } else {
                $dup = mysqli_prepare($conn, "SELECT id FROM salary_records WHERE staff_type=? AND staff_id=? AND salary_month=? LIMIT 1");
                mysqli_stmt_bind_param($dup, "sis", $staff_type, $staff_id, $month);
                mysqli_stmt_execute($dup);
                $already = mysqli_fetch_assoc(mysqli_stmt_get_result($dup));

                if ($already) {
                    flash_set('danger', "A salary for $staff_name already exists for $month. Update it instead of adding a duplicate.");
                } else {
                    if ($status === 'Paid' && $pay_date === null) {
                        $pay_date = date('Y-m-d');
                    }
                    $stmt = mysqli_prepare($conn, "INSERT INTO salary_records (staff_type,staff_id,staff_name,amount,salary_month,payment_status,payment_date,remarks) VALUES (?,?,?,?,?,?,?,?)");
                    mysqli_stmt_bind_param($stmt, "sisdssss", $staff_type, $staff_id, $staff_name, $amount, $month, $status, $pay_date, $remarks);
                    if (mysqli_stmt_execute($stmt)) {
                        $new_id = mysqli_insert_id($conn);
                        salary_log_activity($conn, $_SESSION['user_id'], $_SESSION['name'] ?? 'Admin',
                            'Salary assigned', 'salary', $new_id,
                            ucfirst($staff_type) . ' ' . $staff_name . ' - Rs. ' . number_format($amount, 2) . ' for ' . $month, 'primary');
                        salary_notify_admins($conn, 'info', 'Salary assigned',
                            $_SESSION['name'] . ' assigned Rs. ' . number_format($amount, 0) . ' to ' . $staff_name . ' for ' . $month . '.');
                        flash_set('success', "Salary assigned to $staff_name for $month.");
                    } else {
                        flash_set('danger', 'Could not save the salary record.');
                    }
                }
            }
        }
    } elseif ($action === 'pay') {
        $id = (int)($_POST['id'] ?? 0);
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM salary_records WHERE id=" . $id));
        if (!$row) {
            flash_set('danger', 'Salary record not found.');
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE salary_records SET payment_status='Paid', payment_date=CURRENT_DATE WHERE id=?");
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            salary_log_activity($conn, $_SESSION['user_id'], $_SESSION['name'] ?? 'Admin',
                'Salary paid out', 'salary', $id,
                'Paid Rs. ' . number_format((float)$row['amount'], 2) . ' to ' . $row['staff_name'] . ' (' . $row['salary_month'] . ')', 'success');
            salary_notify_admins($conn, 'success', 'Salary paid out',
                'Rs. ' . number_format((float)$row['amount'], 0) . ' paid to ' . $row['staff_name'] . ' for ' . $row['salary_month'] . '.');
            flash_set('success', 'Payment of Rs. ' . number_format((float)$row['amount'], 2) . ' to ' . $row['staff_name'] . ' recorded as Paid.');
        }
    } elseif ($action === 'pay_all') {
        $month = trim($_POST['salary_month'] ?? '');
        $type  = trim($_POST['staff_type'] ?? '');

        $sql = "UPDATE salary_records SET payment_status='Paid', payment_date=CURRENT_DATE WHERE payment_status='Pending'";
        if ($month !== '') { $sql .= " AND salary_month='" . mysqli_real_escape_string($conn, $month) . "'"; }
        if (in_array($type, ['doctor', 'nurse'], true)) { $sql .= " AND staff_type='" . mysqli_real_escape_string($conn, $type) . "'"; }

        mysqli_query($conn, $sql);
        $scope = ($month !== '' ? $month . ' ' : '') . ($type !== '' ? strtolower($type) . 's' : 'all staff');
        salary_log_activity($conn, $_SESSION['user_id'], $_SESSION['name'] ?? 'Admin',
            'Bulk salary payout', 'salary', null, 'Paid out every pending salary for ' . $scope, 'success');
        salary_notify_admins($conn, 'success', 'Bulk salary payout',
            $_SESSION['name'] . ' paid out all pending salaries for ' . $scope . '.');
        flash_set('success', 'All pending salaries' . ($month !== '' ? ' for ' . $month : '') . ' marked as Paid.');
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM salary_records WHERE id=" . $id));
        if ($row) {
            mysqli_query($conn, "DELETE FROM salary_records WHERE id=" . $id);
            salary_log_activity($conn, $_SESSION['user_id'], $_SESSION['name'] ?? 'Admin',
                'Salary record deleted', 'salary', $id,
                'Removed Rs. ' . number_format((float)$row['amount'], 2) . ' for ' . $row['staff_name'] . ' (' . $row['salary_month'] . ')', 'danger');
            flash_set('success', 'Salary record deleted.');
        } else {
            flash_set('danger', 'Salary record not found.');
        }
    } else {
        flash_set('danger', 'Unknown action.');
    }

    $back = array_filter([
        'month'  => (string)($_POST['filter_month'] ?? ''),
        'type'   => (string)($_POST['filter_type'] ?? ''),
        'status' => (string)($_POST['filter_status'] ?? ''),
    ]);
    header("Location: manage_salary.php" . ($back ? ('?' . http_build_query($back)) : ''));
    exit();
}

/* ------------------------------------------------------------------ */
/* Filters + data                                                      */
/* ------------------------------------------------------------------ */
$filter_month  = trim($_GET['month'] ?? '');
$filter_type   = trim($_GET['type'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$where = "1=1";
if ($filter_month !== '')  { $where .= " AND sr.salary_month='" . mysqli_real_escape_string($conn, $filter_month) . "'"; }
if ($filter_type !== '')   { $where .= " AND sr.staff_type='" . mysqli_real_escape_string($conn, $filter_type) . "'"; }
if ($filter_status !== '') { $where .= " AND sr.payment_status='" . mysqli_real_escape_string($conn, $filter_status) . "'"; }

$salaries = mysqli_query($conn, "SELECT sr.* FROM salary_records sr WHERE $where ORDER BY sr.id DESC");
$doctors  = mysqli_query($conn, "SELECT id,name,specialization FROM doctors ORDER BY name");
$nurses   = mysqli_query($conn, "SELECT id,name,shift FROM nurses ORDER BY name");
$summary  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total,
    COALESCE(SUM(amount),0) AS total_amount,
    COALESCE(SUM(CASE WHEN payment_status='Paid' THEN amount ELSE 0 END),0) AS paid_amount,
    COALESCE(SUM(CASE WHEN payment_status='Pending' THEN amount ELSE 0 END),0) AS pending_amount,
    COALESCE(SUM(CASE WHEN payment_status='Pending' THEN 1 ELSE 0 END),0) AS pending_count
    FROM salary_records sr WHERE $where"));

// Months already in use + the current one, for quick picking.
$month_opts = [];
$res = mysqli_query($conn, "SELECT DISTINCT salary_month FROM salary_records ORDER BY salary_month DESC LIMIT 24");
while ($r = mysqli_fetch_assoc($res)) { $month_opts[] = $r['salary_month']; }
$current_month = date('M-Y');

$page_title = "Salary & Payments";
$active = "salary";
$base = "../";
include "../includes/layout.php";
?>

<style>
  .pay-badge{ font-size:11px; }
  .salary-row-pending td{ background:#fffdf5; }
</style>

<div class="row g-3 mb-3" id="salaryStats">
  <div class="col-md-3"><div class="stat-card text-center"><h6>Records</h6><h2 id="stTotal">0</h2></div></div>
  <div class="col-md-3"><div class="stat-card text-center"><h6>Total assigned</h6><h2 id="stAmount">Rs. 0</h2></div></div>
  <div class="col-md-3"><div class="stat-card text-center"><h6>Paid out</h6><h2 id="stPaid" class="text-success">Rs. 0</h2></div></div>
  <div class="col-md-3"><div class="stat-card text-center"><h6>Pending</h6><h2 id="stPending" class="text-danger">Rs. 0</h2></div></div>
</div>

<div class="row g-3">
  <!-- ASSIGN -->
  <div class="col-lg-4">
    <div class="card p-3">
      <h5 class="mb-3">Assign salary</h5>
      <form method="post" id="assignForm">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="assign">
        <input type="hidden" name="filter_month" value="<?php echo e($filter_month); ?>">
        <input type="hidden" name="filter_type" value="<?php echo e($filter_type); ?>">
        <input type="hidden" name="filter_status" value="<?php echo e($filter_status); ?>">

        <div class="mb-2">
          <label class="form-label">Staff type</label>
          <select class="form-select" name="staff_type" id="salaryType" required onchange="updateStaffList()">
            <option value="doctor">Doctor</option>
            <option value="nurse">Nurse</option>
          </select>
        </div>

        <div class="mb-2">
          <label class="form-label">Staff member</label>
          <select class="form-select" name="staff_id" id="salaryStaff" required onchange="syncStaffName()">
            <option value="">-- Select --</option>
          </select>
          <input type="hidden" name="staff_name" id="salaryName">
        </div>

        <div class="row g-2">
          <div class="col-6 mb-2">
            <label class="form-label">Amount (Rs.)</label>
            <input type="number" class="form-control" name="amount" step="0.01" min="1" required placeholder="0.00">
          </div>
          <div class="col-6 mb-2">
            <label class="form-label">Month</label>
            <input class="form-control" name="salary_month" id="salaryMonth" list="monthList"
                   value="<?php echo e($current_month); ?>" required placeholder="Sep-2026">
            <datalist id="monthList">
              <?php foreach ($month_opts as $m) { ?>
                <option value="<?php echo e($m); ?>"></option>
              <?php } ?>
            </datalist>
          </div>
        </div>

        <div class="row g-2">
          <div class="col-6 mb-2">
            <label class="form-label">Status</label>
            <select class="form-select" name="payment_status" id="salaryStatus">
              <option>Pending</option>
              <option>Paid</option>
            </select>
          </div>
          <div class="col-6 mb-2">
            <label class="form-label">Payment date</label>
            <input type="date" class="form-control" name="payment_date" id="salaryDate">
          </div>
        </div>

        <div class="mb-2">
          <label class="form-label">Remarks</label>
          <input class="form-control" name="remarks" placeholder="Bonus, increment, advance...">
        </div>

        <button class="btn pink-btn w-100">Assign salary</button>
      </form>

      <hr>
      <h6 class="mb-2">Pay out all pending</h6>
      <form method="post" onsubmit="return confirm('Pay out ALL pending salaries in this scope?');">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="action" value="pay_all">
        <input type="hidden" name="filter_month" value="<?php echo e($filter_month); ?>">
        <input type="hidden" name="filter_type" value="<?php echo e($filter_type); ?>">
        <input type="hidden" name="filter_status" value="<?php echo e($filter_status); ?>">
        <div class="row g-2">
          <div class="col-6 mb-2">
            <input class="form-control" name="salary_month" list="monthList" placeholder="All months" value="<?php echo e($filter_month); ?>">
          </div>
          <div class="col-6 mb-2">
            <select class="form-select" name="staff_type">
              <option value="">All staff</option>
              <option value="doctor" <?php echo $filter_type === 'doctor' ? 'selected' : ''; ?>>Doctors</option>
              <option value="nurse" <?php echo $filter_type === 'nurse' ? 'selected' : ''; ?>>Nurses</option>
            </select>
          </div>
        </div>
        <button class="btn btn-outline-success w-100">Pay out pending salaries</button>
      </form>
    </div>
  </div>

  <!-- RECORDS -->
  <div class="col-lg-8">
    <div class="card p-3">
      <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <h5 class="m-0">Salary records <small class="text-muted" id="liveDot" title="Live">&#9679;</small></h5>
        <a href="../reports/salary_reports.php" class="btn btn-sm btn-outline-secondary">Printable report</a>
      </div>

      <form method="get" class="row g-1 mb-3">
        <div class="col-md-3"><input class="form-control" name="month" placeholder="Month e.g. Sep-2026" value="<?php echo e($filter_month); ?>"></div>
        <div class="col-md-2">
          <select class="form-select" name="type">
            <option value="">All types</option>
            <option value="doctor" <?php echo $filter_type === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
            <option value="nurse" <?php echo $filter_type === 'nurse' ? 'selected' : ''; ?>>Nurse</option>
          </select>
        </div>
        <div class="col-md-2">
          <select class="form-select" name="status">
            <option value="">All status</option>
            <option value="Paid" <?php echo $filter_status === 'Paid' ? 'selected' : ''; ?>>Paid</option>
            <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
          </select>
        </div>
        <div class="col-auto"><button class="btn btn-outline-danger btn-sm">Filter</button></div>
        <div class="col-auto"><a href="manage_salary.php" class="btn btn-outline-secondary btn-sm">Reset</a></div>
        <div class="col-auto"><button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print</button></div>
      </form>

      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle" id="salaryTable">
          <thead>
            <tr>
              <th>ID</th><th>Type</th><th>Name</th><th>Amount</th><th>Month</th>
              <th>Status</th><th>Date</th><th>Remarks</th><th style="width:190px;">Action</th>
            </tr>
          </thead>
          <tbody id="salaryBody">
          <?php
          $rows = 0;
          if ($salaries && mysqli_num_rows($salaries) > 0) {
              while ($s = mysqli_fetch_assoc($salaries)) {
                  $rows++;
                  $isPending = $s['payment_status'] === 'Pending';
                  ?>
                  <tr class="<?php echo $isPending ? 'salary-row-pending' : ''; ?>">
                    <td><?php echo (int)$s['id']; ?></td>
                    <td><span class="badge bg-<?php echo $s['staff_type'] === 'doctor' ? 'primary' : 'success'; ?>"><?php echo e(ucfirst($s['staff_type'])); ?></span></td>
                    <td><?php echo e($s['staff_name']); ?></td>
                    <td class="text-nowrap">Rs. <?php echo number_format((float)$s['amount'], 2); ?></td>
                    <td><?php echo e($s['salary_month']); ?></td>
                    <td><span class="badge bg-<?php echo $isPending ? 'warning text-dark' : 'success'; ?>"><?php echo e($s['payment_status']); ?></span></td>
                    <td><?php echo $s['payment_date'] ? e($s['payment_date']) : '-'; ?></td>
                    <td class="small"><?php echo e($s['remarks']); ?></td>
                    <td class="text-nowrap">
                      <?php if ($isPending) { ?>
                        <form method="post" class="d-inline">
                          <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                          <input type="hidden" name="action" value="pay">
                          <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                          <input type="hidden" name="filter_month" value="<?php echo e($filter_month); ?>">
                          <input type="hidden" name="filter_type" value="<?php echo e($filter_type); ?>">
                          <input type="hidden" name="filter_status" value="<?php echo e($filter_status); ?>">
                          <button class="btn btn-sm btn-success" onclick="return confirm('Pay out Rs. <?php echo number_format((float)$s['amount'], 2); ?> to <?php echo e($s['staff_name']); ?>?');">Pay out</button>
                        </form>
                      <?php } else { ?>
                        <span class="text-muted small">Paid</span>
                      <?php } ?>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$s['id']; ?>">
                        <input type="hidden" name="filter_month" value="<?php echo e($filter_month); ?>">
                        <input type="hidden" name="filter_type" value="<?php echo e($filter_type); ?>">
                        <input type="hidden" name="filter_status" value="<?php echo e($filter_status); ?>">
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this salary record?');">Del</button>
                      </form>
                    </td>
                  </tr>
                  <?php
              }
          }
          ?>
          <?php if ($rows === 0) { ?>
            <tr><td colspan="9" class="text-center text-muted py-4" id="emptyRow">No salary records yet. Assign the first salary on the left.</td></tr>
          <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="text-center mt-3">
  <a href="../admin_dashboard.php" class="btn pink-btn">Back to Dashboard</a>
</div>

<script>
const doctors = <?php echo json_encode(mysqli_fetch_all($doctors, MYSQLI_ASSOC)); ?>;
const nurses  = <?php echo json_encode(mysqli_fetch_all($nurses, MYSQLI_ASSOC)); ?>;
const FILTERS = {
  month: <?php echo json_encode($filter_month); ?>,
  type: <?php echo json_encode($filter_type); ?>,
  status: <?php echo json_encode($filter_status); ?>
};

function updateStaffList() {
  const type = document.getElementById('salaryType').value;
  const sel = document.getElementById('salaryStaff');
  const current = sel.value;
  sel.innerHTML = '<option value="">-- Select --</option>';
  const list = type === 'doctor' ? doctors : nurses;
  list.forEach(r => {
    const opt = document.createElement('option');
    opt.value = r.id;
    opt.text = r.name + (type === 'doctor' && r.specialization ? ' - ' + r.specialization : '')
             + (type === 'nurse' && r.shift ? ' (' + r.shift + ')' : '');
    sel.appendChild(opt);
  });
  if (current) sel.value = current;
  syncStaffName();
}

function syncStaffName() {
  const sel = document.getElementById('salaryStaff');
  const hidden = document.getElementById('salaryName');
  const opt = sel.options[sel.selectedIndex];
  hidden.value = (opt && sel.value) ? opt.text : '';
}

updateStaffList();
</script>

<script>
/* ------------------------------------------------------------------
 * Live salary board - polls api/salary.php every 5 seconds through the
 * shared Realtime engine (pauses when the tab is hidden, backs off on
 * failures, redirects to login on 401).
 * ------------------------------------------------------------------ */
let lastSalarySig = '';

function rs(v) {
  if (window.Realtime && Realtime.esc) return Realtime.esc(v === null || v === undefined ? '' : v);
  const d = document.createElement('div');
  d.textContent = v === null || v === undefined ? '' : String(v);
  return d.innerHTML;
}

function money(n, decimals) {
  const num = Number(n || 0);
  return num.toLocaleString('en-IN', {
    minimumFractionDigits: decimals ? 2 : 0,
    maximumFractionDigits: decimals ? 2 : 0
  });
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (el && el.textContent !== value) {
    el.textContent = value;
    el.classList.add('pulse');
    setTimeout(() => el.classList.remove('pulse'), 600);
  }
}

function salaryRowHtml(r) {
  const pending = r.payment_status === 'Pending';
  const csrf = rs(window.CSRF_TOKEN || '');
  const fm = rs(FILTERS.month), ft = rs(FILTERS.type), fs = rs(FILTERS.status);
  const hidden = '<input type="hidden" name="csrf" value="' + csrf + '">'
    + '<input type="hidden" name="filter_month" value="' + fm + '">'
    + '<input type="hidden" name="filter_type" value="' + ft + '">'
    + '<input type="hidden" name="filter_status" value="' + fs + '">';
  let action = '<span class="text-muted small">Paid</span>';
  if (pending) {
    action = '<form method="post" class="d-inline">' + hidden
      + '<input type="hidden" name="action" value="pay">'
      + '<input type="hidden" name="id" value="' + rs(r.id) + '">'
      + '<button class="btn btn-sm btn-success">Pay out</button></form>';
  }
  action += '<form method="post" class="d-inline">' + hidden
    + '<input type="hidden" name="action" value="delete">'
    + '<input type="hidden" name="id" value="' + rs(r.id) + '">'
    + '<button class="btn btn-sm btn-outline-danger">Del</button></form>';

  return '<tr class="' + (pending ? 'salary-row-pending' : '') + '">'
    + '<td>' + rs(r.id) + '</td>'
    + '<td><span class="badge bg-' + (r.staff_type === 'doctor' ? 'primary' : 'success') + '">' + rs(r.staff_type === 'doctor' ? 'Doctor' : 'Nurse') + '</span></td>'
    + '<td>' + rs(r.staff_name) + '</td>'
    + '<td class="text-nowrap">Rs. ' + money(r.amount, true) + '</td>'
    + '<td>' + rs(r.salary_month) + '</td>'
    + '<td><span class="badge bg-' + (pending ? 'warning text-dark' : 'success') + '">' + rs(r.payment_status) + '</span></td>'
    + '<td>' + (r.payment_date ? rs(r.payment_date) : '-') + '</td>'
    + '<td class="small">' + rs(r.remarks) + '</td>'
    + '<td class="text-nowrap">' + action + '</td>'
    + '</tr>';
}

function renderSalary(data) {
  if (!data || data.error) return;
  const s = data.summary || {};

  setText('stTotal', String(s.total ?? 0));
  setText('stAmount', 'Rs. ' + money(s.total_amount));
  setText('stPaid', 'Rs. ' + money(s.paid_amount));
  setText('stPending', 'Rs. ' + money(s.pending_amount));

  const rows = data.rows || [];
  const sig = JSON.stringify(s) + '|' + JSON.stringify(rows);
  if (sig === lastSalarySig) return;
  lastSalarySig = sig;

  const body = document.getElementById('salaryBody');
  if (!body) return;
  body.innerHTML = rows.length
    ? rows.map(salaryRowHtml).join('')
    : '<tr><td colspan="9" class="text-center text-muted py-4">No salary records match this filter.</td></tr>';
}

document.addEventListener('DOMContentLoaded', function () {
  if (!window.Realtime) return;
  const params = new URLSearchParams({
    month: FILTERS.month || '',
    type: FILTERS.type || '',
    status: FILTERS.status || ''
  });
  // Rows are always rendered from JSON, so the first poll repaints the table
  // once and every poll after that only repaints when something actually moved.
  Realtime.startPolling('salary', 'api/salary.php?' + params.toString(), renderSalary, 5000);
});
</script>

<?php include "../includes/layout_footer.php"; ?>
