<?php
/**
 * Real-time admissions list for admin/manage_admissions.php
 *
 *   GET api/admissions.php?search=&limit=100&offset=0
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
    $where .= " AND (a.name LIKE '%{$esc}%' OR a.phone LIKE '%{$esc}%' OR a.email LIKE '%{$esc}%' OR p.name LIKE '%{$esc}%' OR d.name LIKE '%{$esc}%')";
}

try {
    $total = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS c
        FROM admissions a
        LEFT JOIN patients p ON p.id = a.patient_id
        LEFT JOIN doctors d ON d.id = a.doctor_id
        WHERE $where"))['c'] ?? 0;

    $stmt = mysqli_prepare($conn, "
        SELECT a.id, a.patient_id, a.doctor_id, a.ward_id, a.bed_id, a.admission_date, a.discharge_date,
               a.status, a.notes, a.created_at,
               p.name AS patient_name, d.name AS doctor_name, w.ward_name, b.bed_number
        FROM admissions a
        LEFT JOIN patients p ON p.id = a.patient_id
        LEFT JOIN doctors d ON d.id = a.doctor_id
        LEFT JOIN wards w ON w.id = a.ward_id
        LEFT JOIN beds b ON b.id = a.bed_id
        WHERE $where
        ORDER BY a.admission_date DESC, a.id DESC
        LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $admissions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $admissions[] = $row;
    }

    api_json([
        'total'      => (int)$total,
        'limit'      => $limit,
        'offset'     => $offset,
        'admissions' => $admissions,
    ]);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load admissions'], 500);
}