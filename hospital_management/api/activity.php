<?php
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
// The activity timeline logs every user's actions across the system -
// restrict it to administrators.
api_require_roles(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_json(['error' => 'Method not allowed'], 405);
}

try {
    $stmt = mysqli_prepare($conn, "SELECT id, user_name, action, entity_type, description, color, created_at
        FROM activity_logs ORDER BY created_at DESC LIMIT 20");
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $logs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // created_at is kept as-is; the client renders it with relativeTime().
        $logs[] = $row;
    }

    api_json(['activities' => $logs]);

} catch (Exception $e) {
    api_json(['error' => 'Failed to load activity logs'], 500);
}
