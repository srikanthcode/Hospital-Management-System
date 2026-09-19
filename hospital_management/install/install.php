<?php
$conn = @mysqli_connect("localhost", "root", "");
if (!$conn) { die("Cannot connect to MySQL. Start MySQL in XAMPP."); }

mysqli_report(MYSQLI_REPORT_OFF);

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS hospital_management DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_select_db($conn, "hospital_management");

$sql = file_get_contents(__DIR__ . '/schema.sql');
$rt = file_get_contents(__DIR__ . '/realtime_schema.sql');
if ($sql === false) { die("Cannot read schema.sql"); }
$sql .= "\n" . ($rt ?: '');

$lines = explode("\n", $sql);
$cleaned = [];
foreach ($lines as $line) {
    $trimmed = trim($line);
    if ($trimmed === '' || strpos($trimmed, '--') === 0) continue;
    $cleaned[] = $line;
}
$sql = implode("\n", $cleaned);

$statements = array_filter(array_map('trim', explode(';', $sql)));
$count = 0;
foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') continue;
    if (stripos($stmt, 'CREATE DATABASE') === 0) continue;
    if (stripos($stmt, 'USE ') === 0) continue;
    @mysqli_query($conn, $stmt);
    $count++;
}

$pwd = password_hash("Admin@123", PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, "INSERT INTO users (name,email,password,role) VALUES (?,?,?,?) AS u ON DUPLICATE KEY UPDATE password=u.password, role='admin'");
$name = "Administrator"; $email = "admin@lotushospital.com"; $role = "admin";
mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $pwd, $role);
mysqli_stmt_execute($stmt);

echo "Install complete! $count SQL statements executed.\n";
echo "Admin login: admin@lotushospital.com / Admin@123\n";
