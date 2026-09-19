<?php

$database_url = getenv('DATABASE_URL');
$is_render    = getenv('RENDER');

if ($database_url) {
    $parts     = parse_url($database_url);
    $host      = $parts['host'] ?? 'localhost';
    $port      = $parts['port'] ?? '3306';
    $username  = rawurldecode($parts['user'] ?? '');
    $password  = rawurldecode($parts['pass'] ?? '');
    $database  = ltrim($parts['path'] ?? '', '/');

} elseif ($is_render) {
    http_response_code(503);
    header('Content-Type: text/plain');
    die("DATABASE_URL is not set.\n\n"
        . "On Render:\n"
        . "  1. Create a MySQL service in your dashboard\n"
        . "  2. Link it to this web service via Environment > Reference\n"
        . "  3. Redeploy\n");

} else {
    $host     = 'localhost';
    $port     = '3306';
    $username = 'root';
    $password = '';
    $database = 'hospital_management';
}

$conn = @mysqli_connect($host, $username, $password, $database, (int)$port);

if (!$conn) {
    http_response_code(503);
    header('Content-Type: text/plain');
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
