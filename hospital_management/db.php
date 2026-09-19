<?php

$conn = @mysqli_connect('localhost', 'root', '', 'hospital_management', 3306);

if (!$conn) {
    http_response_code(503);
    header('Content-Type: text/plain');
    die("Database connection failed: " . mysqli_connect_error()
        . "\n\nIf this is Render, wait a few seconds and refresh — "
        . "the MariaDB server starts automatically on first boot.");
}

mysqli_set_charset($conn, 'utf8mb4');
