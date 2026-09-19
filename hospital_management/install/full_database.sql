-- ============================================================
-- Lotus Women's Hospital - Complete Database Schema
-- Run this once on a fresh MySQL/MariaDB database.
-- Existing tables (users, doctors, patients, appointments)
-- are kept compatible with the original admin_dashboard counts.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `hospital_management`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `hospital_management`;

-- ------------------------------------------------------------
-- USERS  (login accounts for admin / doctor / patient / nurse)
-- Compatible with the existing login.php query
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','doctor','patient','nurse') NOT NULL DEFAULT 'patient',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- DOCTORS  (profile data; user_id links to users)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `name` VARCHAR(150) NOT NULL,
  `specialization` VARCHAR(150) DEFAULT NULL,
  `qualification` VARCHAR(150) DEFAULT NULL,
  `experience` VARCHAR(50) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_doctor_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- PATIENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `patients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `name` VARCHAR(150) NOT NULL,
  `age` INT DEFAULT NULL,
  `gender` VARCHAR(20) DEFAULT 'Female',
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `blood_group` VARCHAR(10) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_patient_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- NURSES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `nurses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `shift` VARCHAR(50) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `duty_assignment` VARCHAR(200) DEFAULT NULL,
  `patient_care` VARCHAR(200) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_nurse_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- SERVICES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- APPOINTMENTS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `service_id` INT DEFAULT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `status` ENUM('Pending','Confirmed','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_apt_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_apt_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_apt_service` FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- MEDICAL RECORDS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `medical_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `doctor_id` INT DEFAULT NULL,
  `diagnosis` TEXT DEFAULT NULL,
  `treatment` TEXT DEFAULT NULL,
  `prescription` TEXT DEFAULT NULL,
  `record_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_mr_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mr_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- FOLLOW UPS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `follow_ups` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `doctor_id` INT DEFAULT NULL,
  `follow_up_date` DATE NOT NULL,
  `remarks` TEXT DEFAULT NULL,
  `status` ENUM('Pending','Done','Missed') DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fu_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fu_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- WARDS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wards` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ward_name` VARCHAR(100) NOT NULL,
  `ward_type` VARCHAR(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- BEDS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `beds` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ward_id` INT DEFAULT NULL,
  `bed_number` VARCHAR(50) NOT NULL,
  `status` ENUM('Available','Occupied','Reserved','Maintenance') NOT NULL DEFAULT 'Available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_bed_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ADMISSIONS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `bed_id` INT DEFAULT NULL,
  `doctor_id` INT DEFAULT NULL,
  `admission_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `discharge_date` DATETIME DEFAULT NULL,
  `reason` TEXT DEFAULT NULL,
  `status` ENUM('Admitted','Discharged') NOT NULL DEFAULT 'Admitted',
  CONSTRAINT `fk_adm_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_adm_bed` FOREIGN KEY (`bed_id`) REFERENCES `beds`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_adm_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- AMBULANCE SERVICES
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ambulance_services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_number` VARCHAR(50) NOT NULL,
  `driver_name` VARCHAR(150) DEFAULT NULL,
  `driver_phone` VARCHAR(20) DEFAULT NULL,
  `status` ENUM('Available','On Duty','Maintenance') NOT NULL DEFAULT 'Available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- EMERGENCY RECORDS
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `emergency_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_name` VARCHAR(150) NOT NULL,
  `age` INT DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `ambulance_id` INT DEFAULT NULL,
  `doctor_id` INT DEFAULT NULL,
  `emergency_type` VARCHAR(150) DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `status` ENUM('Active','Resolved') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_er_amb` FOREIGN KEY (`ambulance_id`) REFERENCES `ambulance_services`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_er_doc` FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- SALARY RECORDS  (used by Reports module)
-- staff_type: doctor | nurse
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `salary_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_type` ENUM('doctor','nurse') NOT NULL,
  `staff_id` INT NOT NULL,
  `staff_name` VARCHAR(150) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `salary_month` VARCHAR(20) NOT NULL,
  `payment_status` ENUM('Paid','Pending') NOT NULL DEFAULT 'Pending',
  `payment_date` DATE DEFAULT NULL,
  `remarks` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- USEFUL INDEXES
-- ------------------------------------------------------------
CREATE INDEX idx_apt_date ON appointments(appointment_date);
CREATE INDEX idx_apt_doctor ON appointments(doctor_id);
CREATE INDEX idx_apt_patient ON appointments(patient_id);
CREATE INDEX idx_er_created ON emergency_records(created_at);
CREATE INDEX idx_adm_patient ON admissions(patient_id);
CREATE INDEX idx_bed_status ON beds(status);
CREATE INDEX idx_salary_month ON salary_records(salary_month);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin  (password: Admin@123)
INSERT INTO `users` (`name`,`email`,`password`,`role`) VALUES
('Administrator','admin@lotushospital.com',
 '$2y$10$y2svytsE1/XdFY7/lB7PW.rzSNkMmCYs1P926.YDvbPd39Y9Zq1qK','admin')
ON DUPLICATE KEY UPDATE role='admin';

-- Sample services
INSERT INTO `services` (`name`,`category`,`description`) VALUES
('Pediatric Services','Pediatrics','General healthcare for infants, children and adolescents.'),
('Gynaecology Services','Gynaecology','Comprehensive womens healthcare, diagnosis and treatment.'),
('Laparoscopic & Hysteroscopic Surgery','Surgery','Minimally invasive gynaecological surgical procedures.');

-- Sample wards
INSERT INTO `wards` (`ward_name`,`ward_type`,`description`) VALUES
('General Ward','General','General inpatient ward.'),
('Maternity Ward','Maternity','Ward for mothers and newborns.'),
('Pediatric Ward','Pediatrics','Ward for children.');

-- Sample beds
INSERT INTO `beds` (`ward_id`,`bed_number`,`status`) VALUES
(1,'G-101','Available'),(1,'G-102','Available'),(1,'G-103','Available'),
(2,'M-201','Available'),(2,'M-202','Available'),
(3,'P-301','Available'),(3,'P-302','Available');

-- Sample ambulances
INSERT INTO `ambulance_services` (`vehicle_number`,`driver_name`,`driver_phone`,`status`) VALUES
('TN-31-AB-1234','Ramesh','+91 9000000001','Available'),
('TN-31-AB-5678','Suresh','+91 9000000002','Available');


-- Real-time features tables

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'info',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT,
  `reference_id` INT DEFAULT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notif_user` (`user_id`),
  INDEX `idx_notif_read` (`is_read`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `user_name` VARCHAR(150) DEFAULT 'System',
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT DEFAULT NULL,
  `description` TEXT,
  `color` VARCHAR(20) DEFAULT 'primary',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_activity_created` (`created_at`)
) ENGINE=InnoDB;

