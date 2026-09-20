-- ============================================================
-- Lotus Women's Hospital - Complete Database Schema (PostgreSQL)
-- Combined: core schema + real-time tables. Idempotent.
-- ============================================================

-- ============================================================
-- Lotus Women's Hospital - Complete Database Schema (PostgreSQL)
-- Run automatically by db.php on first use against a fresh
-- Render PostgreSQL database. Safe to re-run (idempotent).
-- ============================================================

-- ------------------------------------------------------------
-- USERS  (login accounts for admin / doctor / patient / nurse)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'patient'
    CHECK (role IN ('admin','doctor','patient','nurse')),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- DOCTORS  (profile data; user_id links to users)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS doctors (
  id SERIAL PRIMARY KEY,
  user_id INT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  specialization VARCHAR(150) DEFAULT NULL,
  qualification VARCHAR(150) DEFAULT NULL,
  experience VARCHAR(50) DEFAULT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  department VARCHAR(100) DEFAULT NULL,
  address TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_doctor_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- PATIENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS patients (
  id SERIAL PRIMARY KEY,
  user_id INT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  age INT DEFAULT NULL,
  gender VARCHAR(20) DEFAULT 'Female',
  phone VARCHAR(20) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  address TEXT,
  blood_group VARCHAR(10) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_patient_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- NURSES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS nurses (
  id SERIAL PRIMARY KEY,
  user_id INT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  shift VARCHAR(50) DEFAULT NULL,
  department VARCHAR(100) DEFAULT NULL,
  address TEXT,
  duty_assignment VARCHAR(200) DEFAULT NULL,
  patient_care VARCHAR(200) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_nurse_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- SERVICES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
  id SERIAL PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(100) DEFAULT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- APPOINTMENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS appointments (
  id SERIAL PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT NOT NULL,
  service_id INT DEFAULT NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Pending'
    CHECK (status IN ('Pending','Confirmed','Completed','Cancelled')),
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_apt_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_apt_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
  CONSTRAINT fk_apt_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- MEDICAL RECORDS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS medical_records (
  id SERIAL PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT DEFAULT NULL,
  diagnosis TEXT,
  treatment TEXT,
  prescription TEXT,
  record_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mr_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_mr_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- FOLLOW UPS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS follow_ups (
  id SERIAL PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT DEFAULT NULL,
  follow_up_date DATE NOT NULL,
  remarks TEXT,
  status VARCHAR(20) DEFAULT 'Pending'
    CHECK (status IN ('Pending','Done','Missed')),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_fu_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_fu_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- WARDS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS wards (
  id SERIAL PRIMARY KEY,
  ward_name VARCHAR(100) NOT NULL,
  ward_type VARCHAR(100) DEFAULT NULL,
  description TEXT
);

-- ------------------------------------------------------------
-- BEDS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS beds (
  id SERIAL PRIMARY KEY,
  ward_id INT DEFAULT NULL,
  bed_number VARCHAR(50) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Available'
    CHECK (status IN ('Available','Occupied','Reserved','Maintenance')),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bed_ward FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- ADMISSIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admissions (
  id SERIAL PRIMARY KEY,
  patient_id INT NOT NULL,
  bed_id INT DEFAULT NULL,
  doctor_id INT DEFAULT NULL,
  admission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  discharge_date TIMESTAMP DEFAULT NULL,
  reason TEXT,
  status VARCHAR(20) NOT NULL DEFAULT 'Admitted'
    CHECK (status IN ('Admitted','Discharged')),
  CONSTRAINT fk_adm_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_adm_bed FOREIGN KEY (bed_id) REFERENCES beds(id) ON DELETE SET NULL,
  CONSTRAINT fk_adm_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- AMBULANCE SERVICES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ambulance_services (
  id SERIAL PRIMARY KEY,
  vehicle_number VARCHAR(50) NOT NULL,
  driver_name VARCHAR(150) DEFAULT NULL,
  driver_phone VARCHAR(20) DEFAULT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Available'
    CHECK (status IN ('Available','On Duty','Maintenance')),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- EMERGENCY RECORDS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS emergency_records (
  id SERIAL PRIMARY KEY,
  patient_name VARCHAR(150) NOT NULL,
  age INT DEFAULT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  address TEXT,
  ambulance_id INT DEFAULT NULL,
  doctor_id INT DEFAULT NULL,
  emergency_type VARCHAR(150) DEFAULT NULL,
  details TEXT,
  status VARCHAR(20) NOT NULL DEFAULT 'Active'
    CHECK (status IN ('Active','Resolved')),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_er_amb FOREIGN KEY (ambulance_id) REFERENCES ambulance_services(id) ON DELETE SET NULL,
  CONSTRAINT fk_er_doc FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

-- ------------------------------------------------------------
-- SALARY RECORDS  (used by Reports module)
-- staff_type: doctor | nurse
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS salary_records (
  id SERIAL PRIMARY KEY,
  staff_type VARCHAR(10) NOT NULL
    CHECK (staff_type IN ('doctor','nurse')),
  staff_id INT NOT NULL,
  staff_name VARCHAR(150) NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  salary_month VARCHAR(20) NOT NULL,
  payment_status VARCHAR(10) NOT NULL DEFAULT 'Pending'
    CHECK (payment_status IN ('Paid','Pending')),
  payment_date DATE DEFAULT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- USEFUL INDEXES
-- ------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_apt_date ON appointments(appointment_date);
CREATE INDEX IF NOT EXISTS idx_apt_doctor ON appointments(doctor_id);
CREATE INDEX IF NOT EXISTS idx_apt_patient ON appointments(patient_id);
CREATE INDEX IF NOT EXISTS idx_er_created ON emergency_records(created_at);
CREATE INDEX IF NOT EXISTS idx_adm_patient ON admissions(patient_id);
CREATE INDEX IF NOT EXISTS idx_bed_status ON beds(status);
CREATE INDEX IF NOT EXISTS idx_salary_month ON salary_records(salary_month);

-- ============================================================
-- SEED DATA  (idempotent â€” safe to re-run)
-- ============================================================

-- Default admin  (password: Admin@123)
INSERT INTO users (name,email,password,role) VALUES
 ('Administrator','admin@lotushospital.com',
  '$2y$10$y2svytsE1/XdFY7/lB7PW.rzSNkMmCYs1P926.YDvbPd39Y9Zq1qK','admin')
ON CONFLICT (email) DO UPDATE SET role='admin';

-- Sample services
INSERT INTO services (name,category,description)
SELECT 'Pediatric Services','Pediatrics','General healthcare for infants, children and adolescents.'
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name='Pediatric Services');

INSERT INTO services (name,category,description)
SELECT 'Gynaecology Services','Gynaecology','Comprehensive womens healthcare, diagnosis and treatment.'
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name='Gynaecology Services');

INSERT INTO services (name,category,description)
SELECT 'Laparoscopic & Hysteroscopic Surgery','Surgery','Minimally invasive gynaecological surgical procedures.'
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name='Laparoscopic & Hysteroscopic Surgery');

-- Sample wards
INSERT INTO wards (ward_name,ward_type,description)
SELECT 'General Ward','General','General inpatient ward.'
WHERE NOT EXISTS (SELECT 1 FROM wards WHERE ward_name='General Ward');

INSERT INTO wards (ward_name,ward_type,description)
SELECT 'Maternity Ward','Maternity','Ward for mothers and newborns.'
WHERE NOT EXISTS (SELECT 1 FROM wards WHERE ward_name='Maternity Ward');

INSERT INTO wards (ward_name,ward_type,description)
SELECT 'Pediatric Ward','Pediatrics','Ward for children.'
WHERE NOT EXISTS (SELECT 1 FROM wards WHERE ward_name='Pediatric Ward');

-- Sample beds (resolved by ward name so ward ids do not matter)
INSERT INTO beds (ward_id,bed_number,status)
SELECT w.id,'G-101','Available' FROM wards w WHERE w.ward_name='General Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='G-101');

INSERT INTO beds (ward_id,bed_number,status)
SELECT w.id,'G-102','Available' FROM wards w WHERE w.ward_name='General Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='G-102');

INSERT INTO beds (ward_id,bed_number,status)
SELECT w.id,'G-103','Available' FROM wards w WHERE w.ward_name='General Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='G-103');

INSERT INTO beds (ward_id,bed_number,status)
SELECT w.id,'M-201','Available' FROM wards w WHERE w.ward_name='Maternity Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='M-201');

INSERT INTO beds (ward_id,bed_number,status)
SELECT w.id,'M-202','Available' FROM wards w WHERE w.ward_name='Maternity Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='M-202');

INSERT INTO beds (ward_id,bed_number,status)
SELECT w.id,'P-301','Available' FROM wards w WHERE w.ward_name='Pediatric Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='P-301');

INSERT INTO beds (ward_id,bed_number,status)
SELECT w.id,'P-302','Available' FROM wards w WHERE w.ward_name='Pediatric Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='P-302');

-- Sample ambulances
INSERT INTO ambulance_services (vehicle_number,driver_name,driver_phone,status)
SELECT 'TN-31-AB-1234','Ramesh','+91 9000000001','Available'
WHERE NOT EXISTS (SELECT 1 FROM ambulance_services WHERE vehicle_number='TN-31-AB-1234');

INSERT INTO ambulance_services (vehicle_number,driver_name,driver_phone,status)
SELECT 'TN-31-AB-5678','Suresh','+91 9000000002','Available'
WHERE NOT EXISTS (SELECT 1 FROM ambulance_services WHERE vehicle_number='TN-31-AB-5678');


-- Real-time features tables (PostgreSQL)

CREATE TABLE IF NOT EXISTS notifications (
  id SERIAL PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'info',
  title VARCHAR(255) NOT NULL,
  message TEXT,
  reference_id INT DEFAULT NULL,
  reference_type VARCHAR(50) DEFAULT NULL,
  is_read SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_notif_user ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notif_read ON notifications(is_read);

CREATE TABLE IF NOT EXISTS activity_logs (
  id SERIAL PRIMARY KEY,
  user_id INT DEFAULT NULL,
  user_name VARCHAR(150) DEFAULT 'System',
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50) NOT NULL,
  entity_id INT DEFAULT NULL,
  description TEXT,
  color VARCHAR(20) DEFAULT 'primary',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_activity_created ON activity_logs(created_at);
