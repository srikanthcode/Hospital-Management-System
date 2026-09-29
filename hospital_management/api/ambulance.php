<?php
/**
 * Real-time ambulance list for admin/manage_ambulance.php
 *
 *   GET api/ambulance.php?search=&limit=100&offset=0
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
    $where .= " AND (vehicle_number LIKE '%{$esc}%' OR driver_name LIKE '%{$esc}%' OR driver_phone LIKE '%{$esc}%' OR status LIKE '%{$esc}%')";
}

try {
    $total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM ambulance_services WHERE $where"))['c'] ?? 0;

    $stmt = mysqli_prepare($conn, "SELECT id, vehicle_number, driver_name, driver_phone, status, current_location, created_at
        FROM ambulance_services WHERE $where ORDER BY id DESC LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $ambulances = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $ambulances[] = $row;
    }

    api_json([
        'total'      => (int)$total,
        'limit'      => $limit,
        'offset'     => $offset,
        'ambulances' => $ambulances,
    ]);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load ambulances'], 500);
}