<?php
mysqli_report(MYSQLI_REPORT_OFF);
$isLocal = !getenv('DATABASE_URL') && !getenv('PGHOST');

if (!$isLocal) require_once __DIR__ . '/includes/mysqli_compat.php';

if ($isLocal) {
    $conn = @mysqli_connect('127.0.0.1', 'root', '', 'hospital_db', 3306);
    if (!$conn) {
        $tmp = @mysqli_connect('127.0.0.1', 'root', '', '', 3306);
        if ($tmp) {
            mysqli_query($tmp, "CREATE DATABASE IF NOT EXISTS hospital_db CHARACTER SET utf8mb4");
            mysqli_close($tmp);
            $conn = @mysqli_connect('127.0.0.1', 'root', '', 'hospital_db', 3306);
        }
    }
    if (!$conn) { http_response_code(503); die("Cannot connect to MySQL. Start XAMPP MySQL."); }
    mysql_bootstrap($conn);
} else {
    $conn = false;
    for ($i = 0; $i < 10; $i++) {
        $conn = @mysqli_connect(getenv('PGHOST') ?: 'dpg-dansemek1f9s739q0bjg-a.oregon-postgres.render.com',
            getenv('PGUSER') ?: 'hospital_e8tc_user', getenv('PGPASSWORD') ?: 'BLNx6kOfBIfkVABWagIeGy4Z2w6nNuBy',
            getenv('PGDATABASE') ?: 'hospital_e8tc', (int)(getenv('PGPORT') ?: 5432));
        if ($conn) break;
        sleep(1);
    }
    if (!$conn) { http_response_code(503); die("DB connection failed."); }
}

function mysql_bootstrap($conn) {
    $marker = sys_get_temp_dir() . '/lotus_mysql_ok';
    if (is_file($marker)) return;
    $sql = file_get_contents(__DIR__ . '/install/schema.mysql.sql');
    foreach (explode(';', $sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '' && !preg_match('/^--/', $stmt)) @mysqli_query($conn, $stmt);
    }
    @file_put_contents($marker, '1');
}
