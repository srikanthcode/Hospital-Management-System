<?php
require __DIR__ . '/db.php';

$users = [
    ['name' => 'Demo Patient', 'email' => 'patient@lotushospital.com', 'password' => 'Patient@123', 'role' => 'patient'],
    ['name' => 'Demo Doctor', 'email' => 'doctor@lotushospital.com', 'password' => 'Doctor@123', 'role' => 'doctor'],
    ['name' => 'Demo Nurse', 'email' => 'nurse@lotushospital.com', 'password' => 'Nurse@123', 'role' => 'nurse'],
];

foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users (name,email,password,role) VALUES (?,?,?,?) ON CONFLICT (email) DO UPDATE SET password=EXCLUDED.password, role=EXCLUDED.role");
    mysqli_stmt_bind_param($stmt, "ssss", $u['name'], $u['email'], $hash, $u['role']);
    mysqli_stmt_execute($stmt);

    $uid = mysqli_insert_id($conn);
    if ($uid == 0) {
        $r = mysqli_query($conn, "SELECT id FROM users WHERE email='" . mysqli_real_escape_string($conn, $u['email']) . "'");
        $uid = mysqli_fetch_assoc($r)['id'];
    }

    if ($u['role'] == 'patient') {
        mysqli_query($conn, "INSERT INTO patients (user_id,name,age,gender,phone,email) SELECT $uid,'Demo Patient',28,'Female','+91 9000000000','patient@lotushospital.com' WHERE NOT EXISTS (SELECT 1 FROM patients WHERE user_id=$uid)");
    } elseif ($u['role'] == 'doctor') {
        mysqli_query($conn, "INSERT INTO doctors (user_id,name,specialization,qualification,experience,phone,email,department) SELECT $uid,'Demo Doctor','Obstetrics & Gynaecology','MBBS, MS','10 Years','+91 9000000001','doctor@lotushospital.com','Gynaecology' WHERE NOT EXISTS (SELECT 1 FROM doctors WHERE user_id=$uid)");
    } elseif ($u['role'] == 'nurse') {
        mysqli_query($conn, "INSERT INTO nurses (user_id,name,phone,email,shift,department) SELECT $uid,'Demo Nurse','+91 9000000002','nurse@lotushospital.com','Morning','Maternity Ward' WHERE NOT EXISTS (SELECT 1 FROM nurses WHERE user_id=$uid)");
    }
}

echo "Demo users created successfully!\n\n";
echo "Admin:  admin@lotushospital.com / Admin@123\n";
echo "Patient: patient@lotushospital.com / Patient@123\n";
echo "Doctor:  doctor@lotushospital.com / Doctor@123\n";
echo "Nurse:   nurse@lotushospital.com / Nurse@123\n";
