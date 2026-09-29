<?php
/**
 * Real-time doctor's patients list for doctor/patients.php
 *
 *   GET api/doctor_patients.php?search=&limit=100&offset=0
 */
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
api_require_roles(['doctor']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json(['error' => 'Method not allowed'], 405);
}

$search = trim((string)($_GET['search'] ?? ''));
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

$where = '1=1';
if ($search !== '') {
    $esc = mysqli_real_escape_string($conn, $search);
    $where .= " AND (p.name LIKE '%{$esc}%' OR p.phone LIKE '%{$esc}%' OR p.email LIKE '%{$esc}%')";
}

try {
    $total = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(DISTINCT p.id) AS c
        FROM appointments a
        JOIN patients p ON p.id = a.patient_id
        WHERE a.doctor_id=$doctor_id AND $where"))['c'] ?? 0;

    $stmt = mysqli_prepare($conn, "
        SELECT DISTINCT ON (p.id) p.id, p.name, p.age, p.gender, p.phone, p.email, p.blood_group, p.address,
               a.id AS last_appt_id, a.appointment_date, a.appointment_time, a.status
        FROM appointments a
        JOIN patients p ON p.id = a.patient_id
        WHERE a.doctor_id=? AND $where
        ORDER BY p.id, a.appointment_date DESC, a.appointment_time DESC
        LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, 'iii', $doctor_id, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $patients = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $patients[] = $row;
    }

    api_json([
        'total'    => (int)$total,
        'limit'    => $limit,
        'offset'   => $offset,
        'patients' => $patients,
    ]);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load patients'], 500);
}