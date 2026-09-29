<?php
/**
 * Digital prescriptions / medical records for the doctor side.
 *
 *   GET  api/doctor_medical_records.php?patient_id=&limit=100&offset=0
 *   POST api/doctor_medical_records.php   action=create_prescription
 *        appointment_id (preferred) or patient_id,
 *        diagnosis, treatment, prescription, notes, follow_up_date
 */
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
api_require_roles(['doctor']);

$stmt = mysqli_prepare($conn, "SELECT id FROM doctors WHERE user_id=?");
mysqli_stmt_bind_param($stmt, 'i', $user['id']);
mysqli_stmt_execute($stmt);
$doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$doctor_id = $doctor ? (int)$doctor['id'] : 0;

if (!$doctor_id) {
    api_json(['error' => 'Doctor profile not found'], 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $patient_id = (int)($_GET['patient_id'] ?? 0);
    $limit  = min(max(1, (int)($_GET['limit'] ?? 100)), 500);
    $offset = max(0, (int)($_GET['offset'] ?? 0));

    try {
        $where = 'mr.doctor_id = ?';
        $params = [$doctor_id];
        $types = 'i';

        if ($patient_id > 0) {
            $where .= ' AND mr.patient_id = ?';
            $params[] = $patient_id;
            $types .= 'i';
        }

        $cstmt = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM medical_records mr WHERE $where");
        mysqli_stmt_bind_param($cstmt, $types, ...$params);
        mysqli_stmt_execute($cstmt);
        $total = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($cstmt))['c'] ?? 0);

        $stmt = mysqli_prepare($conn, "
            SELECT mr.id, mr.appointment_id, mr.patient_id, mr.diagnosis, mr.treatment,
                   mr.prescription, mr.notes, mr.record_date, mr.follow_up_date, mr.created_at,
                   p.name AS patient_name, a.appointment_date, a.appointment_time
            FROM medical_records mr
            JOIN patients p ON p.id = mr.patient_id
            LEFT JOIN appointments a ON a.id = mr.appointment_id
            WHERE $where
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
            'total'   => $total,
            'limit'   => $limit,
            'offset'  => $offset,
            'records' => $records,
        ]);

    } catch (Exception $e) {
        api_json(['error' => 'Failed to load medical records'], 500);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    api_require_csrf();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];

    $action = $input['action'] ?? $_POST['action'] ?? '';
    if ($action !== 'create_prescription') {
        api_json(['error' => 'Invalid action'], 400);
    }

    $appointment_id = (int)($input['appointment_id'] ?? $_POST['appointment_id'] ?? 0);
    $patient_id     = (int)($input['patient_id'] ?? $_POST['patient_id'] ?? 0);
    $diagnosis      = trim((string)($input['diagnosis'] ?? $_POST['diagnosis'] ?? ''));
    $treatment      = trim((string)($input['treatment'] ?? $_POST['treatment'] ?? ''));
    $prescription   = trim((string)($input['prescription'] ?? $_POST['prescription'] ?? ''));
    $notes          = trim((string)($input['notes'] ?? $_POST['notes'] ?? ''));
    $follow_up      = trim((string)($input['follow_up_date'] ?? $_POST['follow_up_date'] ?? ''));
    $record_date    = date('Y-m-d');

    if ($diagnosis === '' && $prescription === '') {
        api_json(['error' => 'Please enter at least a diagnosis or a prescription'], 400);
    }

    // The appointment must belong to this doctor.
    if ($appointment_id > 0) {
        $stmt = mysqli_prepare($conn, "SELECT id, patient_id FROM appointments WHERE id=? AND doctor_id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $doctor_id);
        mysqli_stmt_execute($stmt);
        $apt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$apt) api_json(['error' => 'Appointment not found for this doctor'], 404);
        $patient_id = (int)$apt['patient_id'];
    }

    if ($patient_id <= 0) {
        api_json(['error' => 'Missing appointment or patient'], 400);
    }

    $pstmt = mysqli_prepare($conn, "SELECT id FROM patients WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($pstmt, "i", $patient_id);
    mysqli_stmt_execute($pstmt);
    if (!mysqli_fetch_assoc(mysqli_stmt_get_result($pstmt))) {
        api_json(['error' => 'Patient not found'], 404);
    }

    $follow_param = ($follow_up !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $follow_up)) ? $follow_up : null;

    $stmt = mysqli_prepare($conn, "INSERT INTO medical_records
        (patient_id, doctor_id, appointment_id, diagnosis, treatment, prescription, notes, record_date, follow_up_date)
        VALUES (?,?,?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "iiissssss", $patient_id, $doctor_id, $appointment_id,
        $diagnosis, $treatment, $prescription, $notes, $record_date, $follow_param);

    if (!mysqli_stmt_execute($stmt)) {
        api_json(['error' => 'Could not save the prescription'], 500);
    }

    $record_id = (int)mysqli_insert_id($conn);

    // Tell the patient straight away (bell + live prescription card).
    $ustmt = mysqli_prepare($conn, "SELECT user_id, name FROM patients WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($ustmt, "i", $patient_id);
    mysqli_stmt_execute($ustmt);
    $pat = mysqli_fetch_assoc(mysqli_stmt_get_result($ustmt));

    if ($pat) {
        $title = 'New Prescription from Dr. ' . $user['name'];
        $message = ($diagnosis !== '' ? $diagnosis : 'Your doctor shared a prescription');
        if ($follow_param) $message .= " | Follow-up: " . date('d M Y', strtotime($follow_param));
        $type = 'medical_record';
        $ref_type = 'medical_record';
        $nstmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type) VALUES (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($nstmt, "isssis", $pat['user_id'], $type, $title, $message, $record_id, $ref_type);
        mysqli_stmt_execute($nstmt);
    }

    $action_name = 'create_prescription';
    $entity_type = 'medical_record';
    $description = "Prescription issued for patient #{$patient_id}";
    $color = 'success';
    $astmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, user_name, action, entity_type, entity_id, description, color) VALUES (?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($astmt, "issssss", $user['id'], $user['name'], $action_name, $entity_type, $record_id, $description, $color);
    mysqli_stmt_execute($astmt);

    // Mark the appointment as completed so the patient sees the flow close.
    if ($appointment_id > 0) {
        $astmt = mysqli_prepare($conn, "UPDATE appointments SET status='Completed' WHERE id=? AND doctor_id=? AND status IN ('Pending','Confirmed')");
        mysqli_stmt_bind_param($astmt, "ii", $appointment_id, $doctor_id);
        mysqli_stmt_execute($astmt);
    }

    api_json([
        'success'    => true,
        'message'    => 'Prescription saved and sent to the patient',
        'record_id'  => $record_id,
    ]);

} else {
    api_json(['error' => 'Method not allowed'], 405);
}
