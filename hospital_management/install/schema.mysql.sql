CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'patient',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS doctors (
  id INT AUTO_INCREMENT PRIMARY KEY,
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
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS patients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  age INT DEFAULT NULL,
  gender VARCHAR(20) DEFAULT 'Female',
  phone VARCHAR(20) DEFAULT NULL,
  email VARCHAR(150) DEFAULT NULL,
  address TEXT,
  blood_group VARCHAR(10) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS nurses (
  id INT AUTO_INCREMENT PRIMARY KEY,
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
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(100) DEFAULT NULL,
  description TEXT,
  price DECIMAL(10,2) DEFAULT NULL,
  duration_minutes INT DEFAULT NULL,
  is_active SMALLINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT NOT NULL,
  service_id INT DEFAULT NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Pending',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS medical_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT DEFAULT NULL,
  appointment_id INT DEFAULT NULL,
  diagnosis TEXT,
  treatment TEXT,
  prescription TEXT,
  notes TEXT,
  record_date DATE DEFAULT NULL,
  follow_up_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS follow_ups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT DEFAULT NULL,
  follow_up_date DATE NOT NULL,
  remarks TEXT,
  status VARCHAR(20) DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS wards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ward_name VARCHAR(100) NOT NULL,
  ward_type VARCHAR(100) DEFAULT NULL,
  description TEXT
);

CREATE TABLE IF NOT EXISTS beds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ward_id INT DEFAULT NULL,
  bed_number VARCHAR(50) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS admissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  bed_id INT DEFAULT NULL,
  doctor_id INT DEFAULT NULL,
  admission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  discharge_date TIMESTAMP NULL DEFAULT NULL,
  reason TEXT,
  status VARCHAR(20) NOT NULL DEFAULT 'Admitted',
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (bed_id) REFERENCES beds(id) ON DELETE SET NULL,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS ambulance_services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vehicle_number VARCHAR(50) NOT NULL,
  driver_name VARCHAR(150) DEFAULT NULL,
  driver_phone VARCHAR(20) DEFAULT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'Available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS emergency_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_name VARCHAR(150) NOT NULL,
  age INT DEFAULT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  address TEXT,
  ambulance_id INT DEFAULT NULL,
  doctor_id INT DEFAULT NULL,
  emergency_type VARCHAR(150) DEFAULT NULL,
  details TEXT,
  status VARCHAR(20) NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ambulance_id) REFERENCES ambulance_services(id) ON DELETE SET NULL,
  FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS salary_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_type VARCHAR(10) NOT NULL,
  staff_id INT NOT NULL,
  staff_name VARCHAR(150) NOT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  salary_month VARCHAR(20) NOT NULL,
  payment_status VARCHAR(10) NOT NULL DEFAULT 'Pending',
  payment_date DATE DEFAULT NULL,
  remarks VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS otp_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL,
  purpose VARCHAR(20) NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  attempts SMALLINT NOT NULL DEFAULT 0,
  consumed SMALLINT NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_otp_email (email, purpose)
);

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'info',
  title VARCHAR(255) NOT NULL,
  message TEXT,
  reference_id INT DEFAULT NULL,
  reference_type VARCHAR(50) DEFAULT NULL,
  is_read SMALLINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notif_user (user_id),
  INDEX idx_notif_read (is_read)
);

CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  user_name VARCHAR(150) DEFAULT 'System',
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50) NOT NULL,
  entity_id INT DEFAULT NULL,
  description TEXT,
  color VARCHAR(20) DEFAULT 'primary',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_activity_created (created_at)
);

CREATE INDEX IF NOT EXISTS idx_apt_date ON appointments(appointment_date);
CREATE INDEX IF NOT EXISTS idx_apt_doctor ON appointments(doctor_id);
CREATE INDEX IF NOT EXISTS idx_apt_patient ON appointments(patient_id);
CREATE INDEX IF NOT EXISTS idx_er_created ON emergency_records(created_at);
CREATE INDEX IF NOT EXISTS idx_adm_patient ON admissions(patient_id);
CREATE INDEX IF NOT EXISTS idx_bed_status ON beds(status);
CREATE INDEX IF NOT EXISTS idx_salary_month ON salary_records(salary_month);

INSERT IGNORE INTO users (name, email, password, role) VALUES
 ('Administrator', 'admin@lotushospital.com',
  '$2y$10$y2svytsE1/XdFY7/lB7PW.rzSNkMmCYs1P926.YDvbPd39Y9Zq1qK', 'admin');

INSERT IGNORE INTO services (name, category, description) VALUES
 ('Pediatric Services', 'Pediatrics', 'General healthcare for infants, children and adolescents.'),
 ('Gynaecology Services', 'Gynaecology', 'Comprehensive womens healthcare, diagnosis and treatment.'),
 ('Laparoscopic & Hysteroscopic Surgery', 'Surgery', 'Minimally invasive gynaecological surgical procedures.');

INSERT IGNORE INTO wards (ward_name, ward_type, description) VALUES
 ('General Ward', 'General', 'General inpatient ward.'),
 ('Maternity Ward', 'Maternity', 'Ward for mothers and newborns.'),
 ('Pediatric Ward', 'Pediatrics', 'Ward for children.');

INSERT IGNORE INTO beds (ward_id, bed_number, status)
SELECT w.id, 'G-101', 'Available' FROM wards w WHERE w.ward_name='General Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='G-101');

INSERT IGNORE INTO beds (ward_id, bed_number, status)
SELECT w.id, 'G-102', 'Available' FROM wards w WHERE w.ward_name='General Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='G-102');

INSERT IGNORE INTO beds (ward_id, bed_number, status)
SELECT w.id, 'G-103', 'Available' FROM wards w WHERE w.ward_name='General Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='G-103');

INSERT IGNORE INTO beds (ward_id, bed_number, status)
SELECT w.id, 'M-201', 'Available' FROM wards w WHERE w.ward_name='Maternity Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='M-201');

INSERT IGNORE INTO beds (ward_id, bed_number, status)
SELECT w.id, 'M-202', 'Available' FROM wards w WHERE w.ward_name='Maternity Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='M-202');

INSERT IGNORE INTO beds (ward_id, bed_number, status)
SELECT w.id, 'P-301', 'Available' FROM wards w WHERE w.ward_name='Pediatric Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='P-301');

INSERT IGNORE INTO beds (ward_id, bed_number, status)
SELECT w.id, 'P-302', 'Available' FROM wards w WHERE w.ward_name='Pediatric Ward'
AND NOT EXISTS (SELECT 1 FROM beds b WHERE b.bed_number='P-302');

INSERT IGNORE INTO ambulance_services (vehicle_number, driver_name, driver_phone, status) VALUES
 ('TN-31-AB-1234', 'Ramesh', '+91 9000000001', 'Available'),
 ('TN-31-AB-5678', 'Suresh', '+91 9000000002', 'Available');
