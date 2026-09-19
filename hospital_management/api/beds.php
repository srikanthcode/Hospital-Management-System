<?php
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
api_require_roles(['admin', 'doctor', 'nurse']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json(['error' => 'Method not allowed'], 405);
}

try {
    $stats = [];

    $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM beds");
    $stats['total'] = (int)mysqli_fetch_assoc($q)['c'];

    $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM beds WHERE status='Available'");
    $stats['available'] = (int)mysqli_fetch_assoc($q)['c'];

    $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM beds WHERE status='Occupied'");
    $stats['occupied'] = (int)mysqli_fetch_assoc($q)['c'];

    $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM beds WHERE status='Reserved'");
    $stats['reserved'] = (int)mysqli_fetch_assoc($q)['c'];

    $q = mysqli_query($conn, "SELECT COUNT(*) AS c FROM beds WHERE status='Maintenance'");
    $stats['maintenance'] = (int)mysqli_fetch_assoc($q)['c'];

    $wards_result = mysqli_query($conn, "SELECT w.*, COUNT(b.id) AS total_beds,
        SUM(CASE WHEN b.status='Available' THEN 1 ELSE 0 END) AS available_beds,
        SUM(CASE WHEN b.status='Occupied' THEN 1 ELSE 0 END) AS occupied_beds
        FROM wards w
        LEFT JOIN beds b ON b.ward_id = w.id
        GROUP BY w.id
        ORDER BY w.ward_name");

    // Fetch every bed once and group in PHP instead of one query per ward.
    $beds_result = mysqli_query($conn, "SELECT ward_id, id, bed_number, status FROM beds ORDER BY ward_id, bed_number");
    $beds_by_ward = [];
    while ($bed = mysqli_fetch_assoc($beds_result)) {
        $beds_by_ward[(int)$bed['ward_id']][] = $bed;
    }

    $wards = [];
    while ($ward = mysqli_fetch_assoc($wards_result)) {
        $ward['beds'] = $beds_by_ward[(int)$ward['id']] ?? [];
        $wards[] = $ward;
    }

    api_json(['stats' => $stats, 'wards' => $wards]);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load bed data'], 500);
}
