<?php
/**
 * Real-time nurses list for admin/manage_nurses.php
 *
 *   GET api/nurses.php?search=jane&limit=100&offset=0
 */
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
api_require_roles(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json(['error' => 'Method not allowed'], 405);
}

$search = trim((string)($_GET['search'] ?? ''));
$limit  = min(max(1, (int)($_GET['limit'] ?? 100)), 500);
$offset = max(0, (int)($_GET['offset'] ?? 0));

$where = '1=1';
if ($search !== '') {
    $esc = mysqli_real_escape_string($conn, $search);
    $where .= " AND (n.name LIKE '%{$esc}%' OR n.phone LIKE '%{$esc}%' OR n.email LIKE '%{$esc}%' OR n.department LIKE '%{$esc}%')";
}

try {
    $total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM nurses n WHERE $where"))['c'] ?? 0;

    $stmt = mysqli_prepare($conn, "SELECT n.id, n.name, n.phone, n.email, n.department, n.duty_assignment, n.patient_care, n.address, n.shift, n.user_id, n.created_at
        FROM nurses n WHERE $where ORDER BY n.id DESC LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $nurses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $nurses[] = $row;
    }

    api_json([
        'total'  => (int)$total,
        'limit'  => $limit,
        'offset' => $offset,
        'nurses' => $nurses,
    ]);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load nurses'], 500);
}