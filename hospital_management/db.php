<?php

// MariaDB inside the Docker container may still be starting on first boot.
// Retry the connection with increasing delays.
$conn = null;

$attempts = [
    ['host' => 'localhost', 'port' => 3306],   // socket via localhost
    ['host' => '127.0.0.1', 'port' => 3306],   // TCP fallback
];

for ($try = 0; $try < 20; $try++) {
    foreach ($attempts as $cfg) {
        $conn = @mysqli_connect($cfg['host'], 'root', '', 'hospital_management', $cfg['port']);
        if ($conn) break 2;
    }
    sleep(1);
}

if (!$conn) {
    http_response_code(503);
    header('Content-Type: text/plain');
    die("Database connection failed after 20 retries.\n"
        . "mysqli error: " . mysqli_connect_error() . "\n\n"
        . "Check Render deploy logs for MariaDB startup output.");
}

mysqli_set_charset($conn, 'utf8mb4');
