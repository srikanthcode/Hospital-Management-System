<?php

include "db.php";

$email = "admin@lotushospital.com";
$password = "Admin@123";

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$sql = "UPDATE users SET password = ?, role = 'admin' WHERE email = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ss", $hashedPassword, $email);

if (mysqli_stmt_execute($stmt)) {
    echo "Admin password updated successfully!";
} else {
    echo "Error: " . mysqli_error($conn);
}

?>