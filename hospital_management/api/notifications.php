<?php
include '../db.php';
require_once '../includes/api_helper.php';

$user = api_require_auth();
$user_id = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = mysqli_prepare($conn, "SELECT id, type, title, message, reference_id, reference_type, is_read, created_at
        FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $notifications = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }

    $uc = mysqli_prepare($conn, "SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0");
    mysqli_stmt_bind_param($uc, "i", $user_id);
    mysqli_stmt_execute($uc);
    $unread_count = mysqli_fetch_assoc(mysqli_stmt_get_result($uc))['c'] ?? 0;

    api_json(['notifications' => $notifications, 'unread_count' => (int)$unread_count]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    api_require_csrf();
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? '';

    if ($action === 'mark_read') {
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        api_json(['success' => true, 'message' => 'All notifications marked as read']);

    } elseif ($action === 'mark_one') {
        $notification_id = (int)($input['id'] ?? $_POST['id'] ?? $input['notification_id'] ?? $_POST['notification_id'] ?? 0);
        if ($notification_id <= 0) {
            api_json(['error' => 'Invalid notification_id'], 400);
        }

        $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
        mysqli_stmt_execute($stmt);

        api_json(['success' => true, 'message' => 'Notification marked as read']);

    } else {
        api_json(['error' => 'Invalid action'], 400);
    }

} else {
    api_json(['error' => 'Method not allowed'], 405);
}
