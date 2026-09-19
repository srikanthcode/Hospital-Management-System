<?php

// Retry connection up to 10 times — MariaDB may still be starting
// on the very first boot inside the Docker container.
$conn = null;
for ($attempt = 1; $attempt <= 10; $attempt++) {
    $conn = @mysqli_connect('localhost', 'root', '', 'hospital_management', 3306);
    if ($conn) break;
    sleep(1);
}

if (!$conn) {
    http_response_code(503);
    header('Content-Type: text/plain');
    die("Database connection failed after 10 retries: " . mysqli_connect_error()
        . "\n\nIf this is the first boot, the MariaDB server inside the "
        . "Docker container may still be starting. Try refreshing in a few seconds.");
}

mysqli_set_charset($conn, 'utf8mb4');
