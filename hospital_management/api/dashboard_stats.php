<?php
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
$role = $user['role'];
$user_id = $user['id'];

try {
    $stats = [];

    if ($role === 'admin') {
        $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM doctors");
        $stats['doctors'] = (int)mysqli_fetch_assoc($q)['c'];

        $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM patients");
        $stats['patients'] = (int)mysqli_fetch_assoc($q)['c'];

        $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM appointments");
        $stats['appointments'] = (int)mysqli_fetch_assoc($q)['c'];

        $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM beds WHERE status='Available'");
        $stats['available_beds'] = (int)mysqli_fetch_assoc($q)['c'];

        $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM emergency_records WHERE status='Active'");
        $stats['active_emergencies'] = (int)mysqli_fetch_assoc($q)['c'];

    } elseif ($role === 'doctor') {
        $stmt = mysqli_prepare($conn, "SELECT id FROM doctors WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $doctor_id = $doctor ? $doctor['id'] : 0;

        $stmt = mysqli_prepare($conn, "SELECT COUNT(DISTINCT patient_id) AS c FROM appointments WHERE doctor_id=?");
        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        $stats['my_patients'] = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM appointments WHERE doctor_id=? AND appointment_date=CURDATE()");
        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        $stats['today_appointments'] = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM appointments WHERE doctor_id=?");
        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        $stats['total_appointments'] = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM medical_records WHERE doctor_id=?");
        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        $stats['medical_records'] = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

    } elseif ($role === 'patient') {
        $stmt = mysqli_prepare($conn, "SELECT id FROM patients WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $patient_id = $patient ? $patient['id'] : 0;

        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM appointments WHERE patient_id=?");
        mysqli_stmt_bind_param($stmt, "i", $patient_id);
        mysqli_stmt_execute($stmt);
        $stats['my_appointments'] = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM medical_records WHERE patient_id=?");
        mysqli_stmt_bind_param($stmt, "i", $patient_id);
        mysqli_stmt_execute($stmt);
        $stats['medical_records'] = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM follow_ups WHERE patient_id=?");
        mysqli_stmt_bind_param($stmt, "i", $patient_id);
        mysqli_stmt_execute($stmt);
        $stats['follow_ups'] = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

        $stmt = mysqli_prepare($conn, "SELECT a.appointment_date AS date, a.appointment_time AS time, d.name AS doctor_name, a.status
            FROM appointments a
            JOIN doctors d ON d.id = a.doctor_id
            WHERE a.patient_id=? AND a.appointment_date >= CURDATE() AND a.status != 'Cancelled'
            ORDER BY a.appointment_date ASC, a.appointment_time ASC LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $patient_id);
        mysqli_stmt_execute($stmt);
        $stats['next_appointment'] = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    } elseif ($role === 'nurse') {
        $stmt = mysqli_prepare($conn, "SELECT shift, department, duty_assignment, patient_care FROM nurses WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $nurse = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $stats['shift'] = $nurse ? $nurse['shift'] : '-';
        $stats['department'] = $nurse ? $nurse['department'] : '-';
        $stats['duty_assignment'] = $nurse ? $nurse['duty_assignment'] : '-';
        // "Patient Care" card renders this description string, not a count.
        $stats['patient_care'] = $nurse ? $nurse['patient_care'] : '-';
    }

    api_json($stats);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load dashboard stats'], 500);
}
