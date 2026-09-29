<?php
/**
 * Live salary feed for admin/manage_salary.php.
 *
 *   GET api/salary.php?month=Sep-2026&type=doctor&status=Pending
 *
 * Returns the same summary + rows the page renders, so the Realtime poller
 * can repaint the board every few seconds without a page reload.
 */
include '../db.php';
require_once '../includes/api_helper.php';

api_require_auth();
api_require_roles(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json(['error' => 'Method not allowed'], 405);
}

$month  = trim((string)($_GET['month'] ?? ''));
$type   = trim((string)($_GET['type'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$where = '1=1';
if ($month !== '') {
    $where .= " AND sr.salary_month='" . mysqli_real_escape_string($conn, $month) . "'";
}
if (in_array($type, ['doctor', 'nurse'], true)) {
    $where .= " AND sr.staff_type='" . mysqli_real_escape_string($conn, $type) . "'";
}
if (in_array($status, ['Paid', 'Pending'], true)) {
    $where .= " AND sr.payment_status='" . mysqli_real_escape_string($conn, $status) . "'";
}

try {
    $sum = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total,
        COALESCE(SUM(amount),0) AS total_amount,
        COALESCE(SUM(CASE WHEN payment_status='Paid' THEN amount ELSE 0 END),0) AS paid_amount,
        COALESCE(SUM(CASE WHEN payment_status='Pending' THEN amount ELSE 0 END),0) AS pending_amount,
        COALESCE(SUM(CASE WHEN payment_status='Pending' THEN 1 ELSE 0 END),0) AS pending_count
        FROM salary_records sr WHERE $where"));

    $stmt = mysqli_prepare($conn, "SELECT id, staff_type, staff_id, staff_name, amount, salary_month,
            payment_status, payment_date, remarks
            FROM salary_records sr WHERE $where ORDER BY sr.id DESC LIMIT 500");
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $row['id'] = (int)$row['id'];
        $row['staff_id'] = (int)$row['staff_id'];
        $row['amount'] = (float)$row['amount'];
        $rows[] = $row;
    }
} catch (Exception $e) {
    api_json(['error' => 'Could not load salary data: ' . $e->getMessage()], 500);
}

api_json([
    'summary' => [
        'total'           => (int)($sum['total'] ?? 0),
        'total_amount'    => (float)($sum['total_amount'] ?? 0),
        'paid_amount'     => (float)($sum['paid_amount'] ?? 0),
        'pending_amount'  => (float)($sum['pending_amount'] ?? 0),
        'pending_count'   => (int)($sum['pending_count'] ?? 0),
    ],
    'rows'    => $rows,
    'filters' => ['month' => $month, 'type' => $type, 'status' => $status],
    'ts'      => date('c'),
]);
