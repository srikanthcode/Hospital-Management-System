<?php
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
$role = $user['role'];
$user_id = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $base_query = "SELECT a.*, p.name AS patient_name, d.name AS doctor_name, s.name AS service_name
        FROM appointments a
        JOIN patients p ON p.id = a.patient_id
        JOIN doctors d ON d.id = a.doctor_id
        LEFT JOIN services s ON s.id = a.service_id";

    if ($role === 'doctor') {
        $stmt = mysqli_prepare($conn, "SELECT id FROM doctors WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $doctor_id = $doctor ? $doctor['id'] : 0;

        $stmt = mysqli_prepare($conn, "$base_query WHERE a.doctor_id=? ORDER BY a.appointment_date DESC, a.appointment_time DESC");
        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

    } elseif ($role === 'patient') {
        $stmt = mysqli_prepare($conn, "SELECT id FROM patients WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $patient_id = $patient ? $patient['id'] : 0;

        $stmt = mysqli_prepare($conn, "$base_query WHERE a.patient_id=? ORDER BY a.appointment_date DESC, a.appointment_time DESC");
        mysqli_stmt_bind_param($stmt, "i", $patient_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

    } else {
        $result = mysqli_query($conn, "$base_query ORDER BY a.appointment_date DESC, a.appointment_time DESC");
    }

    $appointments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }

    api_json(['appointments' => $appointments]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    api_require_csrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $appointment_id = (int)($input['appointment_id'] ?? $_POST['appointment_id'] ?? 0);
        $new_status = $input['status'] ?? $_POST['status'] ?? '';

        // A patient may only cancel their own appointment; confirming or
        // completing is a clinical action reserved for staff.
        $allowed_by_role = [
            'admin'   => ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
            'doctor'  => ['Pending', 'Confirmed', 'Completed', 'Cancelled'],
            'patient' => ['Cancelled'],
            'nurse'   => [],
        ];
        $allowed = $allowed_by_role[$role] ?? [];

        if ($appointment_id <= 0 || !in_array($new_status, $allowed, true)) {
            api_json(['error' => 'Invalid appointment_id or status'], 400);
        }

        // Scope the update to rows the caller actually owns.
        if ($role === 'doctor') {
            $dstmt = mysqli_prepare($conn, "SELECT id FROM doctors WHERE user_id=?");
            mysqli_stmt_bind_param($dstmt, "i", $user_id);
            mysqli_stmt_execute($dstmt);
            $doctor_id = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($dstmt))['id'] ?? 0);
            $stmt = mysqli_prepare($conn, "UPDATE appointments SET status=? WHERE id=? AND doctor_id=?");
            mysqli_stmt_bind_param($stmt, "sii", $new_status, $appointment_id, $doctor_id);
        } elseif ($role === 'patient') {
            $pstmt = mysqli_prepare($conn, "SELECT id FROM patients WHERE user_id=?");
            mysqli_stmt_bind_param($pstmt, "i", $user_id);
            mysqli_stmt_execute($pstmt);
            $patient_id = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($pstmt))['id'] ?? 0);
            $stmt = mysqli_prepare($conn, "UPDATE appointments SET status=? WHERE id=? AND patient_id=?");
            mysqli_stmt_bind_param($stmt, "sii", $new_status, $appointment_id, $patient_id);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE appointments SET status=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "si", $new_status, $appointment_id);
        }
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $stmt2 = mysqli_prepare($conn, "SELECT a.patient_id, a.doctor_id, p.name AS patient_name, d.name AS doctor_name
                FROM appointments a
                JOIN patients p ON p.id = a.patient_id
                JOIN doctors d ON d.id = a.doctor_id
                WHERE a.id=?");
            mysqli_stmt_bind_param($stmt2, "i", $appointment_id);
            mysqli_stmt_execute($stmt2);
            $apt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

            if ($apt) {
                $status_color = appointment_status_color($new_status);

                $stmt3 = mysqli_prepare($conn, "SELECT user_id FROM doctors WHERE id=?");
                mysqli_stmt_bind_param($stmt3, "i", $apt['doctor_id']);
                mysqli_stmt_execute($stmt3);
                $doc_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt3));

                $stmt4 = mysqli_prepare($conn, "SELECT user_id FROM patients WHERE id=?");
                mysqli_stmt_bind_param($stmt4, "i", $apt['patient_id']);
                mysqli_stmt_execute($stmt4);
                $pat_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt4));

                if ($doc_user) {
                    api_create_notification($conn, $doc_user['user_id'], 'appointment', 'Appointment Status Updated',
                        "Appointment with {$apt['patient_name']} is now {$new_status}", $appointment_id, 'appointment');
                }
                if ($pat_user) {
                    api_create_notification($conn, $pat_user['user_id'], 'appointment', 'Appointment Status Updated',
                        "Your appointment with {$apt['doctor_name']} is now {$new_status}", $appointment_id, 'appointment');
                }

                api_log_activity($conn, $user_id, $user['name'], 'update_status', 'appointment',
                    $appointment_id, "Appointment status changed to {$new_status}", $status_color);
            }

            api_json(['success' => true, 'message' => "Status updated to {$new_status}"]);
        } else {
            api_json(['error' => 'Appointment not found'], 404);
        }

    } else {
        api_json(['error' => 'Invalid action'], 400);
    }

} else {
    api_json(['error' => 'Method not allowed'], 405);
}

function appointment_status_color($status) {
    $colors = [
        'Pending' => 'warning',
        'Confirmed' => 'success',
        'Completed' => 'secondary',
        'Cancelled' => 'danger'
    ];
    return $colors[$status] ?? 'primary';
}
