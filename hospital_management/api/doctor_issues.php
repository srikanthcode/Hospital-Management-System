<?php
/**
 * Patient issues/complaints for doctor dashboard
 * Sources: emergency_records (Active), medical_records (recent), follow_ups (Pending)
 * GET api/doctor_issues.php?limit=10&offset=0
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
    $issues = [];

    // 1. Active emergency records for this doctor's patients
    $stmt = mysqli_prepare($conn, "
        SELECT 'emergency' AS type, er.id, er.patient_name, er.age, er.phone, er.address,
               er.emergency_type, er.details, er.created_at, er.status
        FROM emergency_records er
        WHERE er.doctor_id = ? AND er.status = 'Active'
        ORDER BY er.created_at DESC
        LIMIT ?");
    mysqli_stmt_bind_param($stmt, "ii", $doctor_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['source'] = 'Emergency';
        $row['title'] = $row['emergency_type'] ?? 'Emergency';
        $row['description'] = $row['details'];
        $row['patient_name'] = $row['patient_name'];
        $issues[] = $row;
    }

    // 2. Recent medical records for this doctor (as issues/complaints)
    $stmt = mysqli_prepare($conn, "
        SELECT 'medical' AS type, mr.id, p.name AS patient_name, p.age, p.phone,
               mr.diagnosis, mr.treatment, mr.prescription, mr.record_date AS created_at,
               'Recorded' AS status
        FROM medical_records mr
        JOIN patients p ON p.id = mr.patient_id
        WHERE mr.doctor_id = ?
        ORDER BY mr.created_at DESC
        LIMIT ?");
    mysqli_stmt_bind_param($stmt, "ii", $doctor_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['source'] = 'Medical Record';
        $row['title'] = $row['diagnosis'] ? (strlen($row['diagnosis']) > 50 ? substr($row['diagnosis'], 0, 50) . '...' : $row['diagnosis']) : 'Medical Record';
        $row['description'] = $row['treatment'] . ($row['prescription'] ? ' | Rx: ' . $row['prescription'] : '');
        $issues[] = $row;
    }

    // 3. Pending follow-ups for this doctor's patients
    $stmt = mysqli_prepare($conn, "
        SELECT 'followup' AS type, fu.id, p.name AS patient_name, p.age, p.phone,
               fu.follow_up_date AS created_at, fu.remarks AS description, fu.status
        FROM follow_ups fu
        JOIN patients p ON p.id = fu.patient_id
        WHERE fu.doctor_id = ? AND fu.status = 'Pending'
        ORDER BY fu.follow_up_date ASC
        LIMIT ?");
    mysqli_stmt_bind_param($stmt, "ii", $doctor_id, $limit);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $row['source'] = 'Follow-up';
        $row['title'] = 'Follow-up due: ' . $row['follow_up_date'];
        $issues[] = $row;
    }

    // Sort by date (newest first)
    usort($issues, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    $total = count($issues);
    $issues = array_slice($issues, $offset, $limit);

    api_json([
        'total'  => $total,
        'limit'  => $limit,
        'offset' => $offset,
        'issues' => $issues,
    ]);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load issues'], 500);
}