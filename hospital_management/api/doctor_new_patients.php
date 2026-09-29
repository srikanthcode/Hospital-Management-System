<?php
/**
 * New/unassigned patients for doctor dashboard
 * GET api/doctor_new_patients.php?limit=10&offset=0
 */
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
api_require_roles(['doctor']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json(['error' => 'Method not allowed'], 405);
}

$limit  = min(max(1, (int)($_GET['limit'] ?? 10)), 100);
$offset = max(0, (int)($_GET['offset'] ?? 0));

$stmt = mysqli_prepare($conn, "SELECT id FROM doctors WHERE user_id=?");
mysqli_stmt_bind_param($stmt, "i", $user['id']);
mysqli_stmt_execute($stmt);
$doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$doctor_id = $doctor ? $doctor['id'] : 0;

if (!$doctor_id) {
    api_json(['error' => 'Doctor profile not found'], 404);
}

try {
    // Patients who have appointments with this doctor
    $assigned = mysqli_fetch_all(
        mysqli_query($conn, "SELECT DISTINCT patient_id FROM appointments WHERE doctor_id=$doctor_id"),
        MYSQLI_ASSOC
    );
    $assigned_ids = array_column($assigned, 'patient_id');
    $assigned_sql = $assigned_ids ? 'AND p.id NOT IN (' . implode(',', array_map('intval', $assigned_ids)) . ')' : '';

    // Total unassigned patients
    $total = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) AS c
        FROM patients p
        WHERE 1=1 $assigned_sql
    "))['c'] ?? 0;

    // Recent unassigned patients (new registrations)
    $stmt = mysqli_prepare($conn, "
        SELECT p.id, p.name, p.age, p.gender, p.phone, p.email, p.blood_group, p.address, p.created_at
        FROM patients p
        WHERE 1=1 $assigned_sql
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, "ii", $limit, $offset);
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
    api_json(['error' => 'Failed to load new patients'], 500);
}