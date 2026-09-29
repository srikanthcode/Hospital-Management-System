<?php
/**
 * Real-time doctors list for admin/manage_doctors.php
 *
 *   GET api/doctors.php?search=jane&limit=100&offset=0
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
    $where .= " AND (d.name LIKE '%{$esc}%' OR d.specialization LIKE '%{$esc}%' OR d.phone LIKE '%{$esc}%' OR d.email LIKE '%{$esc}%')";
}

try {
    $total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM doctors d WHERE $where"))['c'] ?? 0;

    $stmt = mysqli_prepare($conn, "SELECT d.id, d.name, d.specialization, d.qualification, d.experience, d.phone, d.email, d.department, d.user_id, d.created_at
        FROM doctors d WHERE $where ORDER BY d.id DESC LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $doctors = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $doctors[] = $row;
    }

    api_json([
        'total'   => (int)$total,
        'limit'   => $limit,
        'offset'  => $offset,
        'doctors' => $doctors,
    ]);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load doctors'], 500);
}