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

// ── Auto-install: create tables on first run ──────────────────────
$check = @$conn->query("SHOW TABLES LIKE 'users'");
if ($check && $check->num_rows === 0) {

    $schema_files = [
        __DIR__ . '/install/schema.sql',
        __DIR__ . '/install/realtime_schema.sql',
    ];

    foreach ($schema_files as $file) {
        if (!file_exists($file)) continue;

        $sql = file_get_contents($file);

        // Strip statements that assume a specific database name —
        // we are already connected to the correct database.
        $sql = preg_replace('/CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+[^;]+;/', '', $sql);
        $sql = preg_replace('/USE\s+`?[a-zA-Z_]+`?\s*;/', '', $sql);

        // Remove -- and /* */ comments, then split by semicolons.
        $sql       = preg_replace('/--.*$/m', '', $sql);
        $sql       = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                @$conn->query($stmt);
            }
        }
    }
}
