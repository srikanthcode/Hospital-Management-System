<?php
require __DIR__ . '/db.php';

$users = [
    ['name' => 'Demo Patient', 'email' => 'patient@lotushospital.com', 'password' => 'Patient@123', 'role' => 'patient'],
    ['name' => 'Demo Doctor', 'email' => 'doctor@lotushospital.com', 'password' => 'Doctor@123', 'role' => 'doctor'],
    ['name' => 'Demo Nurse', 'email' => 'nurse@lotushospital.com', 'password' => 'Nurse@123', 'role' => 'nurse'],
];

foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users (name,email,password,role) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE password=?, role=?");
    mysqli_stmt_bind_param($stmt, "ssssss", $u['name'], $u['email'], $hash, $u['role'], $hash, $u['role']);
    mysqli_stmt_execute($stmt);

    $uid = mysqli_insert_id($conn);
    if ($uid == 0) {
        $r = mysqli_query($conn, "SELECT id FROM users WHERE email='" . mysqli_real_escape_string($conn, $u['email']) . "'");
        $uid = mysqli_fetch_assoc($r)['id'];
    }

    if ($u['role'] == 'patient') {
        mysqli_query($conn, "INSERT IGNORE INTO patients (user_id,name,age,gender,phone,email) VALUES ($uid,'Demo Patient',28,'Female','+91 9000000000','patient@lotushospital.com')");
    } elseif ($u['role'] == 'doctor') {
        mysqli_query($conn, "INSERT IGNORE INTO doctors (user_id,name,specialization,qualification,experience,phone,email,department) VALUES ($uid,'Demo Doctor','Obstetrics & Gynaecology','MBBS, MS','10 Years','+91 9000000001','doctor@lotushospital.com','Gynaecology')");
    } elseif ($u['role'] == 'nurse') {
        mysqli_query($conn, "INSERT IGNORE INTO nurses (user_id,name,phone,email,shift,department) VALUES ($uid,'Demo Nurse','+91 9000000002','nurse@lotushospital.com','Morning','Maternity Ward')");
    }
}

echo "Demo users created successfully!\n\n";
echo "Admin:  admin@lotushospital.com / Admin@123\n";
echo "Patient: patient@lotushospital.com / Patient@123\n";
echo "Doctor:  doctor@lotushospital.com / Doctor@123\n";
echo "Nurse:   nurse@lotushospital.com / Nurse@123\n";
