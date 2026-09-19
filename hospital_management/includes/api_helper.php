<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

// Pull in the session/CSRF helpers (csrf_token / csrf_check).
require_once __DIR__ . '/auth.php';

function api_json($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function api_require_auth() {
    if (empty($_SESSION['user_id'])) {
        api_json(['error' => 'Unauthorized'], 401);
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['name'] ?? '',
        'role' => $_SESSION['role'] ?? ''
    ];
}

/**
 * Every state-changing API call must carry the session CSRF token.
 * The JS client sends it in the X-CSRF-Token header; same-origin HTML
 * forms may send it as the csrf_token POST field.
 */
function api_require_csrf() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!csrf_check($token)) {
        api_json(['error' => 'Invalid or missing CSRF token'], 403);
    }
}

function api_require_roles(array $roles) {
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        api_json(['error' => 'Forbidden'], 403);
    }
    return $role;
}

function api_log_activity($conn, $user_id, $user_name, $action, $entity_type, $entity_id = null, $description = '', $color = 'primary') {
    $stmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, user_name, action, entity_type, entity_id, description, color) VALUES (?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "issssss", $user_id, $user_name, $action, $entity_type, $entity_id, $description, $color);
    mysqli_stmt_execute($stmt);
}

function api_create_notification($conn, $user_id, $type, $title, $message, $ref_id = null, $ref_type = null) {
    $stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, title, message, reference_id, reference_type) VALUES (?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "isisss", $user_id, $type, $title, $message, $ref_id, $ref_type);
    mysqli_stmt_execute($stmt);
}
