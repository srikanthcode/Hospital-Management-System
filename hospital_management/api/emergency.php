<?php
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Emergency records contain patient phone numbers and addresses -
    // never expose them to patient accounts.
    api_require_roles(['admin', 'doctor', 'nurse']);

    $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM emergency_records WHERE status='Active'");
    $active_count = (int)mysqli_fetch_assoc($q)['c'];

    $result = mysqli_query($conn, "SELECT er.*, a.vehicle_number, d.name AS doctor_name
        FROM emergency_records er
        LEFT JOIN ambulance_services a ON a.id = er.ambulance_id
        LEFT JOIN doctors d ON d.id = er.doctor_id
        ORDER BY er.created_at DESC
        LIMIT 100");

    $emergencies = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $emergencies[] = $row;
    }

    api_json(['active_count' => $active_count, 'emergencies' => $emergencies]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    api_require_csrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? '';

    if ($action === 'update_status') {
        if (!in_array($user['role'], ['admin', 'doctor'])) {
            api_json(['error' => 'Forbidden'], 403);
        }
        $emergency_id = (int)($input['emergency_id'] ?? 0);
        $new_status = $input['status'] ?? '';

        if ($emergency_id <= 0 || !in_array($new_status, ['Active', 'Resolved'])) {
            api_json(['error' => 'Invalid emergency_id or status'], 400);
        }

        $stmt = mysqli_prepare($conn, "UPDATE emergency_records SET status=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $emergency_id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $color = $new_status === 'Resolved' ? 'success' : 'danger';
            api_log_activity($conn, $user['id'], $user['name'], 'update_status', 'emergency',
                $emergency_id, "Emergency status changed to {$new_status}", $color);

            api_json(['success' => true, 'message' => "Emergency status updated to {$new_status}"]);
        } else {
            api_json(['error' => 'Emergency record not found'], 404);
        }

    } else {
        api_json(['error' => 'Invalid action'], 400);
    }

} else {
    api_json(['error' => 'Method not allowed'], 405);
}
