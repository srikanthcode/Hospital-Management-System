<?php

// Render provides a single DATABASE_URL; local XAMPP uses separate values.
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // Parse mysql://user:password@host:port/database
    $parts = parse_url($database_url);
    $host     = $parts['host'] ?? 'localhost';
    $port     = $parts['port'] ?? '3306';
    $username = rawurldecode($parts['user'] ?? '');
    $password = rawurldecode($parts['pass'] ?? '');
    $database = ltrim($parts['path'] ?? '', '/');
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