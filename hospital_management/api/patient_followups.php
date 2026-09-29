<?php
/**
 * Real-time patient's follow-ups for patient/followups.php
 *
 *   GET api/patient_followups.php?limit=100&offset=0
 */
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
api_require_roles(['patient']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json(['error' => 'Method not allowed'], 405);
}

$limit  = min(max(1, (int)($_GET['limit'] ?? 100)), 500);
$offset = max(0, (int)($_GET['offset'] ?? 0));

$stmt = mysqli_prepare($conn, "SELECT id FROM patients WHERE user_id=?");
mysqli_stmt_bind_param($stmt, 'i', $user['id']);
mysqli_stmt_execute($stmt);
$patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$patient_id = $patient ? $patient['id'] : 0;

if (!$patient_id) {
    api_json(['error' => 'Patient profile not found'], 404);
}

try {
    $total = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS c FROM follow_ups WHERE patient_id=$patient_id"))['c'] ?? 0;

    $stmt = mysqli_prepare($conn, "
        SELECT fu.id, fu.appointment_id, fu.follow_up_date, fu.reason, fu.status,
               fu.notes, fu.created_at,
               d.name AS doctor_name, a.appointment_date, a.appointment_time
        FROM follow_ups fu
        JOIN appointments a ON a.id = fu.appointment_id
        JOIN doctors d ON d.id = a.doctor_id
        WHERE fu.patient_id=?
        ORDER BY fu.follow_up_date ASC, fu.id DESC
        LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, 'iii', $patient_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $followups = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $followups[] = $row;
    }

    api_json([
        'total'     => (int)$total,
        'limit'     => $limit,
        'offset'    => $offset,
        'followups' => $followups,
    ]);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load follow-ups'], 500);
}