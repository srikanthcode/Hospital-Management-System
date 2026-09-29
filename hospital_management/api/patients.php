<?php
/**
 * Real-time patient list for admin/manage_patients.php
 *
 *   GET api/patients.php?search=john&limit=100&offset=0
 *
 * Returns paginated patient list with search.
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
    $where .= " AND (name LIKE '%{$esc}%' OR phone LIKE '%{$esc}%' OR email LIKE '%{$esc}%')";
}

try {
    $total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM patients WHERE $where"))['c'] ?? 0;

    $stmt = mysqli_prepare($conn, "SELECT id, name, age, gender, phone, email, blood_group, address, user_id, created_at
        FROM patients WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $patients = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $patients[] = $row;
    }

    api_json([
        'total'   => (int)$total,
        'limit'   => $limit,
        'offset'  => $offset,
        'patients' => $patients,
    ]);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load patients'], 500);
}