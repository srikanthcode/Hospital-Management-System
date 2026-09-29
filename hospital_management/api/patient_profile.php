<?php
/**
 * Real-time patient profile for patient/profile.php
 *
 *   GET api/patient_profile.php
 */
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
api_require_roles(['patient']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json(['error' => 'Method not allowed'], 405);
}

$stmt = mysqli_prepare($conn, "SELECT * FROM patients WHERE user_id=?");
mysqli_stmt_bind_param($stmt, 'i', $user['id']);
mysqli_stmt_execute($stmt);
$patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$patient) {
    api_json(['error' => 'Patient profile not found'], 404);
}

api_json(['patient' => $patient]);