<?php
/**
 * Shared auth helper for the new modules.
 * Existing login.php is NOT modified. This file only adds helpers
 * used by the new dashboard pages.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (!isset($_SESSION["user_id"])) {
        header("Location: ../login.php");
        exit();
    }
}

function require_role($role) {
    require_login();
    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== $role) {
        header("Location: ../login.php");
        exit();
    }
}

function e($val) {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

function flash_get() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function flash_set($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_check($token) {
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$token);
}
