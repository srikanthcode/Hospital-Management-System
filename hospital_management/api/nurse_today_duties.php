<?php
/**
 * Real-time nurse's today duties for nurse/dashboard.php
 *
 *   GET api/nurse_today_duties.php
 */
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
api_require_roles(['nurse']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json(['error' => 'Method not allowed'], 405);
}

$today = date('Y-m-d');

$stmt = mysqli_prepare($conn, "SELECT id FROM nurses WHERE user_id=?");
mysqli_stmt_bind_param($stmt, 'i', $user['id']);
mysqli_stmt_execute($stmt);
$nurse = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$nurse_id = $nurse ? $nurse['id'] : 0;

if (!$nurse_id) {
    api_json(['error' => 'Nurse profile not found'], 404);
}

try {
    // Get today's appointments assigned to this nurse's department/ward
    $result = mysqli_query($conn, "
        SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.notes,
               p.name AS patient_name, d.name AS doctor_name, s.name AS service_name
        FROM appointments a
        JOIN patients p ON p.id = a.patient_id
        JOIN doctors d ON d.id = a.doctor_id
        LEFT JOIN services s ON s.id = a.service_id
        WHERE a.appointment_date = '$today'
        ORDER BY a.appointment_time ASC LIMIT 100");

    $appointments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }

    // Get active emergencies
    $emergencies = [];
    $emergency_result = mysqli_query($conn, "
        SELECT er.id, er.patient_name, er.patient_phone, er.location, er.description,
               er.created_at, er.status, a.vehicle_number
        FROM emergency_records er
        LEFT JOIN ambulance_services a ON a.id = er.ambulance_id
        WHERE er.status = 'Active'
        ORDER BY er.created_at DESC LIMIT 20");

    while ($row = mysqli_fetch_assoc($emergency_result)) {
        $emergencies[] = $row;
    }

    api_json([
        'date'          => $today,
        'appointments'  => $appointments,
        'emergencies'   => $emergencies,
        'shift'         => $nurse['shift'] ?? '-',
        'department'    => $nurse['department'] ?? '-',
        'duty_assignment' => $nurse['duty_assignment'] ?? '-',
        'patient_care'  => $nurse['patient_care'] ?? '-',
    ]);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load nurse duties'], 500);
}