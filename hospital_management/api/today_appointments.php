<?php
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
$role = $user['role'];
$user_id = $user['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json(['error' => 'Method not allowed'], 405);
}

try {
    // Derive "today" in PHP once and use it in every branch, so the date
    // returned to the client always matches the date the query filtered on
    // (MySQL CURDATE() and PHP date() can differ if timezones diverge).
    $today = date('Y-m-d');
    $today_sql = mysqli_real_escape_string($conn, $today);

    $base_query = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.notes,
        p.name AS patient_name, d.name AS doctor_name, s.name AS service_name
        FROM appointments a
        JOIN patients p ON p.id = a.patient_id
        JOIN doctors d ON d.id = a.doctor_id
        LEFT JOIN services s ON s.id = a.service_id
        WHERE a.appointment_date = '{$today_sql}'";

    if ($role === 'doctor') {
        $stmt = mysqli_prepare($conn, "SELECT id FROM doctors WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $doctor_id = $doctor ? $doctor['id'] : 0;

        $stmt = mysqli_prepare($conn, "$base_query AND a.doctor_id=? ORDER BY a.appointment_time ASC LIMIT 100");
        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

    } elseif ($role === 'patient') {
        $stmt = mysqli_prepare($conn, "SELECT id FROM patients WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $patient_id = $patient ? $patient['id'] : 0;

        $stmt = mysqli_prepare($conn, "$base_query AND a.patient_id=? ORDER BY a.appointment_time ASC LIMIT 100");
        mysqli_stmt_bind_param($stmt, "i", $patient_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

    } else {
        $result = mysqli_query($conn, "$base_query ORDER BY a.appointment_time ASC LIMIT 100");
    }

    $appointments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }

    api_json(['date' => $today, 'appointments' => $appointments]);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load today\'s appointments'], 500);
}
