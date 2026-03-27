-- ============================================================
--  ollc_clinic.sql  —  Valenzuela Clinic System
--  Database: valenzuela_clinic
--
--  HOW TO IMPORT (XAMPP):
--  1. Open phpMyAdmin → Database tab → Import
--  2. Choose this file → Click Go
--  OR via CLI: mysql -u root < ollc_clinic.sql
--
--  Default logins after import:
--    Patient:  patient@clinic.com  /  password123
--    Admin:    admin@clinic.com    /  admin123
--    Staff:    staff@clinic.com    /  admin123
-- ============================================================

CREATE DATABASE IF NOT EXISTS valenzuela_clinic
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE valenzuela_clinic;

-- ────────────────────────────────────────────────────────────
-- TABLE: users
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  patient_no  VARCHAR(20)  DEFAULT NULL,
  email       VARCHAR(120) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('patient','admin','staff') NOT NULL DEFAULT 'patient',
  first_name  VARCHAR(60)  NOT NULL,
  last_name   VARCHAR(60)  NOT NULL,
  phone       VARCHAR(20)  DEFAULT NULL,
  address     TEXT         DEFAULT NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_patient_no (patient_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- TABLE: clinic_staff  (extended profile for staff/admin)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS clinic_staff (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT         NOT NULL UNIQUE,
  position    VARCHAR(80) DEFAULT 'Clinic Nurse',
  license_no  VARCHAR(60) DEFAULT NULL,
  is_active   TINYINT(1)  NOT NULL DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- TABLE: services  (the 8 clinic services)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS services (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL,
  icon        VARCHAR(60)  DEFAULT 'fa-stethoscope',
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  sort_order  INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- TABLE: appointments
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS appointments (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  apt_code     VARCHAR(30)  NOT NULL UNIQUE,
  patient_id   INT          NOT NULL,
  service_id   INT          DEFAULT NULL,
  reason       VARCHAR(200) DEFAULT '',
  notes        TEXT         DEFAULT NULL,
  apt_date     DATE         NOT NULL,
  apt_time     VARCHAR(20)  NOT NULL,
  status       ENUM('Pending','Approved','Cancelled','Rescheduled') NOT NULL DEFAULT 'Pending',
  priority     ENUM('normal','urgent') NOT NULL DEFAULT 'normal',
  admin_notes  TEXT         DEFAULT NULL,
  created_by   INT          DEFAULT NULL,
  updated_by   INT          DEFAULT NULL,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id)  REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (service_id)  REFERENCES services(id) ON DELETE SET NULL,
  INDEX idx_patient  (patient_id),
  INDEX idx_apt_date (apt_date),
  INDEX idx_status   (status),
  INDEX idx_service  (service_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- TABLE: time_slots
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS time_slots (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  slot_time   VARCHAR(20) NOT NULL UNIQUE,
  max_per_day INT NOT NULL DEFAULT 3,
  is_active   TINYINT(1)  NOT NULL DEFAULT 1,
  sort_order  INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- TABLE: notifications
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT  NOT NULL,
  message    TEXT NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────────────────────
-- TABLE: audit_logs
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_logs (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  action       VARCHAR(40)  NOT NULL,
  performed_by INT          DEFAULT NULL,
  detail       TEXT         NOT NULL,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================
--  SEED DATA
-- ============================================================

-- Users
INSERT INTO users (patient_no, email, password, role, first_name, last_name, phone) VALUES
  ('P-2024-001', 'patient@clinic.com',  'password123', 'patient', 'Maria',  'Santos',    '09171234567'),
  ('P-2024-002', 'juan@clinic.com',     'password123', 'patient', 'Juan',   'Dela Cruz', '09181234567'),
  ('P-2024-003', 'ana@clinic.com',      'password123', 'patient', 'Ana',    'Reyes',     '09191234567'),
  (NULL,         'admin@clinic.com',    'admin123',    'admin',   'Dr.',    'Gomez',     NULL),
  (NULL,         'staff@clinic.com',    'admin123',    'staff',   'Nurse',  'Santos',    NULL);

-- Clinic staff profiles
INSERT INTO clinic_staff (user_id, position, license_no, is_active) VALUES
  (4, 'Clinic Physician', 'MD-12345', 1),
  (5, 'Clinic Nurse',     'RN-67890', 1);

-- The 8 clinic services
INSERT INTO services (name, description, icon, sort_order) VALUES
  ('General Check-Up',            'Basic health screening for adults & kids',           'fa-heart-pulse',   1),
  ('Pediatric Consultation',      'Healthcare specifically for children',               'fa-baby',          2),
  ('Dental Check-Up',             'Cleaning, cavity check & dental advice',             'fa-tooth',         3),
  ('Vaccination & Immunization',  'Routine vaccines for adults & children',             'fa-syringe',       4),
  ('BP & Sugar Monitoring',       'Quick in-clinic vital sign tests',                   'fa-droplet',       5),
  ('Specialist Consultation',     'Referral to cardiologist, dermatologist & more',     'fa-user-doctor',   6),
  ('Teleconsultation',            'Video or chat consultation for minor concerns',      'fa-video',         7),
  ('Health & Wellness',           'Diet, exercise & lifestyle programs',                'fa-seedling',      8);

-- Default time slots
INSERT INTO time_slots (slot_time, max_per_day, sort_order) VALUES
  ('8:00 AM',  3, 1), ('9:00 AM',  3, 2), ('10:00 AM', 3, 3),
  ('11:00 AM', 3, 4), ('1:00 PM',  3, 5), ('2:00 PM',  3, 6),
  ('3:00 PM',  3, 7), ('4:00 PM',  3, 8);

-- Sample appointments (with service IDs)
INSERT INTO appointments (apt_code, patient_id, service_id, reason, notes, apt_date, apt_time, status, priority, admin_notes) VALUES
  ('APT-001', 1, 1, 'Routine annual check-up',        'Feeling slightly feverish',    '2025-02-10', '9:00 AM',  'Approved',    'normal', 'Vitals normal. Paracetamol prescribed.'),
  ('APT-002', 1, 5, '',                               'BP monitoring requested',      DATE(NOW()),  '10:00 AM', 'Pending',     'normal', ''),
  ('APT-003', 1, 6, 'Referral to cardiologist',       '',                             '2025-02-20', '2:00 PM',  'Rescheduled', 'normal', 'Rescheduled due to doctor availability.'),
  ('APT-004', 2, 1, 'Headache and mild fever',        'Severe headache since morning','2025-02-10', '8:00 AM',  'Approved',    'urgent', ''),
  ('APT-005', 3, 4, 'Annual flu shot',                '',                             DATE(NOW()),  '9:00 AM',  'Pending',     'normal', ''),
  ('APT-006', 2, 3, '',                               'Tooth ache',                   DATE(NOW()),  '11:00 AM', 'Pending',     'normal', ''),
  ('APT-007', 3, 7, 'Online consultation request',   '',                             '2025-02-05', '1:00 PM',  'Cancelled',   'normal', 'Patient did not connect.');

-- Sample notifications
INSERT INTO notifications (user_id, message) VALUES
  (1, 'Your appointment APT-001 has been APPROVED. Visit the clinic on Feb 10 at 9:00 AM.'),
  (1, 'Your appointment APT-003 has been RESCHEDULED to Feb 20, 2025 at 2:00 PM.');

-- Sample audit logs
INSERT INTO audit_logs (action, performed_by, detail) VALUES
  ('APPROVE',    4, 'Approved APT-001 for Maria Santos'),
  ('RESCHEDULE', 4, 'Rescheduled APT-003 to Feb 20 at 2:00 PM'),
  ('CANCEL',     4, 'Cancelled APT-007 — patient no-show');
