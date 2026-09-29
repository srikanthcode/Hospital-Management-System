<?php
if (function_exists('mysqli_report')) mysqli_report(MYSQLI_REPORT_OFF);
$isLocal = function_exists('mysqli_connect')
    && !getenv('DATABASE_URL') && !getenv('PGHOST') && !getenv('PGUSER') && !getenv('RENDER');

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

// Make sure the demo logins always exist in whatever database is in use.
$demo_db_key = $isLocal ? 'hospital_db' : (getenv('PGDATABASE') ?: 'hospital_e8tc');
seed_demo_accounts($conn, $demo_db_key);
ensure_appointment_schema($conn, $demo_db_key);

/**
 * Columns added for the booking / digital-prescription flow. Older installs
 * (and the Render PostgreSQL database) may pre-date them, so add whatever is
 * missing once per database.
 */
function ensure_appointment_schema($conn, $db_key) {
    $marker = sys_get_temp_dir() . '/lotus_schema_app_v2_' . preg_replace('/[^A-Za-z0-9_]/', '_', $db_key);
    if (is_file($marker)) return;

    $statements = [
        "ALTER TABLE medical_records ADD COLUMN appointment_id INT NULL",
        "ALTER TABLE medical_records ADD COLUMN notes TEXT NULL",
        "ALTER TABLE medical_records ADD COLUMN follow_up_date DATE NULL",
        "ALTER TABLE services ADD COLUMN price DECIMAL(10,2) NULL",
        "ALTER TABLE services ADD COLUMN duration_minutes INT NULL",
        "ALTER TABLE services ADD COLUMN is_active SMALLINT DEFAULT 1",
    ];

    $ok = true;
    foreach ($statements as $sql) {
        if (@mysqli_query($conn, $sql)) continue;
        $err = (string)mysqli_error($conn);
        if (stripos($err, 'duplicate column') === false && stripos($err, 'already exists') === false) {
            $ok = false; // real failure (e.g. table not created yet) - retry next request
        }
    }

    if ($ok) @file_put_contents($marker, '1');
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

function seed_demo_accounts($conn, $db_key) {
    $marker = sys_get_temp_dir() . '/lotus_demo_' . preg_replace('/[^A-Za-z0-9_]/', '_', $db_key);
    if (is_file($marker)) return;

    $demo = [
        ['Administrator', 'admin@lotushospital.com',   'Admin@123',   'admin'],
        ['Demo Patient',  'patient@lotushospital.com', 'Patient@123', 'patient'],
        ['Demo Doctor',   'doctor@lotushospital.com',  'Doctor@123',  'doctor'],
        ['Demo Nurse',    'nurse@lotushospital.com',   'Nurse@123',   'nurse'],
    ];

    $in_list = [];
    foreach ($demo as $d) { $in_list[] = "'" . $d[1] . "'"; }

    $res = @mysqli_query($conn, "SELECT id, lower(email) AS email FROM users WHERE lower(email) IN (" . implode(',', $in_list) . ")");
    if (!$res) return; // schema not ready yet - retry on the next request

    $have = [];
    while ($row = mysqli_fetch_assoc($res)) { $have[$row['email']] = (int)$row['id']; }

    $done = true;
    foreach ($demo as $d) {
        list($name, $email, $password, $role) = $d;

        if (isset($have[$email])) {
            $uid = $have[$email];
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = @mysqli_prepare($conn, "INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
            if (!$stmt) { $done = false; continue; }
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hash, $role);
            if (!@mysqli_stmt_execute($stmt)) { $done = false; continue; }

            $uid = (int)mysqli_insert_id($conn);
            if (!$uid) {
                $q = @mysqli_query($conn, "SELECT id FROM users WHERE lower(email) = lower('" . $email . "') LIMIT 1");
                $r = $q ? mysqli_fetch_assoc($q) : null;
                $uid = $r ? (int)$r['id'] : 0;
            }
            $have[$email] = $uid;
        }

        if ($uid) seed_demo_profile($conn, $role, $uid, $name);
    }

    if ($done) @file_put_contents($marker, '1');
}

function seed_demo_profile($conn, $role, $uid, $name) {
    $uid = (int)$uid;

    if ($role === 'patient') {
        $r = @mysqli_query($conn, "SELECT id FROM patients WHERE user_id = $uid LIMIT 1");
        if ($r && mysqli_fetch_assoc($r)) return;
        $stmt = @mysqli_prepare($conn, "INSERT INTO patients (user_id,name,age,gender,phone,email,address) VALUES (?,?,'30','Female','+91 9000000000',?,'')");
        if (!$stmt) return;
        $email = 'patient@lotushospital.com';
        mysqli_stmt_bind_param($stmt, "iss", $uid, $name, $email);
        mysqli_stmt_execute($stmt);
    } elseif ($role === 'doctor') {
        $r = @mysqli_query($conn, "SELECT id FROM doctors WHERE user_id = $uid LIMIT 1");
        if ($r && mysqli_fetch_assoc($r)) return;
        $stmt = @mysqli_prepare($conn, "INSERT INTO doctors (user_id,name,specialization,qualification,experience,phone,email,department,address) VALUES (?,?,'Obstetrics & Gynaecology','MBBS, MD','10 Years','+91 9000000001',?,'Gynaecology','')");
        if (!$stmt) return;
        $email = 'doctor@lotushospital.com';
        mysqli_stmt_bind_param($stmt, "iss", $uid, $name, $email);
        mysqli_stmt_execute($stmt);
    } elseif ($role === 'nurse') {
        $r = @mysqli_query($conn, "SELECT id FROM nurses WHERE user_id = $uid LIMIT 1");
        if ($r && mysqli_fetch_assoc($r)) return;
        $stmt = @mysqli_prepare($conn, "INSERT INTO nurses (user_id,name,phone,email,shift,department,address) VALUES (?,?,'+91 9000000002',?,'Morning','Maternity Ward','')");
        if (!$stmt) return;
        $email = 'nurse@lotushospital.com';
        mysqli_stmt_bind_param($stmt, "iss", $uid, $name, $email);
        mysqli_stmt_execute($stmt);
    }
}
