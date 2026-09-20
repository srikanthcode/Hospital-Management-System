<?php
/**
 * Manual install / admin-reset tool (PostgreSQL).
 *
 * Normally the schema is created automatically by db.php on first use, so you
 * do not need to run this. Use it to force a fresh install or to reset the
 * admin password:
 *
 *     php install/install.php        # from CLI
 *     php install/install.php Web    # optional custom admin password
 *
 * It reuses the connection settings resolved by db.php (DATABASE_URL / PG* env
 * vars, falling back to the Render database configured there).
 */

// Force db.php to re-check / re-create the schema instead of trusting the
// per-instance "schema already exists" marker.
$marker = sys_get_temp_dir() . '/lotus_pg_schema_ok';
if (is_file($marker)) {
    @unlink($marker);
}

// Connects and bootstraps the schema if needed.
require __DIR__ . '/../db.php';

$admin_name  = 'Administrator';
$admin_email = 'admin@lotushospital.com';
$admin_pass  = $argv[1] ?? 'Admin@123';
$admin_role  = 'admin';

$hash = password_hash($admin_pass, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, "INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)
    ON CONFLICT (email) DO UPDATE SET password=EXCLUDED.password, role=EXCLUDED.role");
mysqli_stmt_bind_param($stmt, "ssss", $admin_name, $admin_email, $hash, $admin_role);
mysqli_stmt_execute($stmt);

echo "Install complete.\n";
echo "Schema: users, doctors, patients, nurses, services, appointments,\n";
echo "        medical_records, follow_ups, wards, beds, admissions,\n";
echo "        ambulance_services, emergency_records, salary_records,\n";
echo "        notifications, activity_logs\n";
echo "Admin login: {$admin_email} / {$admin_pass}\n";
