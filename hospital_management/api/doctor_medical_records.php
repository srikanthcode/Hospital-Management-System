<?php
/**
 * Real-time medical records list for doctor/medical_records.php
 *
 *   GET api/doctor_medical_records.php?patient_id=&limit=100&offset=0
 */
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
api_require_roles(['doctor']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json(['error' => 'Method not allowed'], 405);
}

$patient_id = (int)($_GET['patient_id'] ?? 0);
$limit  = min(max(1, (int)($_GET['limit'] ?? 100)), 500);
$offset = max(0, (int)($_GET['offset'] ?? 0));

$stmt = mysqli_prepare($conn, "SELECT id FROM doctors WHERE user_id=?");
mysqli_stmt_bind_param($stmt, 'i', $user['id']);
mysqli_stmt_execute($stmt);
$doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$doctor_id = $doctor ? $doctor['id'] : 0;

if (!$doctor_id) {
    api_json(['error' => 'Doctor profile not found'], 404);
}

try {
    $where = '1=1';
    $params = [$doctor_id];
    $types = 'i';

    if ($patient_id > 0) {
        $where .= ' AND mr.patient_id = ?';
        $params[] = $patient_id;
        $types .= 'i';
    }

    $total = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS c FROM medical_records mr
        JOIN appointments a ON a.id = mr.appointment_id
        WHERE a.doctor_id={$doctor_id} AND $where"))['c'] ?? 0;

    $stmt = mysqli_prepare($conn, "
        SELECT mr.id, mr.appointment_id, mr.patient_id, mr.diagnosis, mr.prescription, mr.notes,
               mr.follow_up_date, mr.created_at,
               p.name AS patient_name, a.appointment_date, a.appointment_time
        FROM medical_records mr
        JOIN appointments a ON a.id = mr.appointment_id
        JOIN patients p ON p.id = mr.patient_id
        WHERE a.doctor_id=? AND $where
        ORDER BY mr.created_at DESC
        LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $records = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $records[] = $row;
    }

    api_json([
        'total'    => (int)$total,
        'limit'    => $limit,
        'offset'   => $offset,
        'records'  => $records,
    ]);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load medical records'], 500);
}