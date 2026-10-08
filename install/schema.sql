-- Kusagba Hospital Management System
-- MySQL / MariaDB schema (engine neutral: works on WAMP MySQL 8 and InfinityFree MariaDB)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','doctor','nurse','receptionist','pharmacist','lab','cashier','patient','caregiver') NOT NULL DEFAULT 'receptionist',
  full_name VARCHAR(120) NOT NULL,
  gender ENUM('Male','Female','Other') NULL,
  dob DATE NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(120) NULL,
  staff_id VARCHAR(40) NULL,
  specialty VARCHAR(120) NULL,
  qualification VARCHAR(120) NULL,
  license_no VARCHAR(60) NULL,
  department VARCHAR(80) NULL,
  address VARCHAR(255) NULL,
  patient_id INT UNSIGNED NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  deleted_at DATETIME NULL,
  last_login DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_active (active),
  INDEX idx_username (username),
  KEY idx_role (role),
  UNIQUE KEY uq_users_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_no VARCHAR(30) NOT NULL UNIQUE,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  gender ENUM('Male','Female','Other') NOT NULL DEFAULT 'Male',
  dob DATE NULL,
  blood_group VARCHAR(8) NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(120) NULL,
  address VARCHAR(255) NULL,
  occupation VARCHAR(80) NULL,
  next_of_kin_name VARCHAR(120) NULL,
  next_of_kin_phone VARCHAR(40) NULL,
  next_of_kin_relation VARCHAR(40) NULL,
  allergies VARCHAR(255) NULL,
  medical_history TEXT NULL,
  notes TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  updated_at DATETIME NULL DEFAULT NULL,
  KEY idx_created_at (created_at),
  KEY idx_patient_no (patient_no),
  KEY idx_name (last_name, first_name),
  KEY idx_active (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS record_changelogs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(40) NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  action VARCHAR(20) NOT NULL DEFAULT 'update',
  changes TEXT NULL,
  user_id INT UNSIGNED NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_entity (entity_type, entity_id),
  KEY idx_created (created_at),
  KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  doctor_id INT UNSIGNED NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NULL,
  reason VARCHAR(255) NULL,
  status ENUM('pending','confirmed','checked_in','completed','cancelled','no_show') NOT NULL DEFAULT 'pending',
  notes TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  KEY idx_date (appointment_date),
  KEY idx_status (status),
  KEY idx_patient (patient_id),
  KEY idx_doctor_date (doctor_id, appointment_date),
  KEY idx_created_at (created_at),
  KEY idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consultations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  doctor_id INT UNSIGNED NULL,
  appointment_id INT UNSIGNED NULL,
  visit_date DATE NOT NULL,
  visit_type ENUM('outpatient','inpatient') NOT NULL DEFAULT 'outpatient',
  chief_complaint TEXT NULL,
  history TEXT NULL,
  examination TEXT NULL,
  temperature DECIMAL(4,1) NULL,
  blood_pressure VARCHAR(12) NULL,
  pulse SMALLINT UNSIGNED NULL,
  respiratory_rate SMALLINT UNSIGNED NULL,
  weight DECIMAL(5,2) NULL,
  height DECIMAL(5,2) NULL,
  spo2 SMALLINT UNSIGNED NULL,
  diagnosis TEXT NULL,
  treatment_plan TEXT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_visit_date (visit_date),
  KEY idx_patient (patient_id),
  KEY idx_doctor (doctor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  department VARCHAR(80) NULL,
  total_beds INT UNSIGNED NOT NULL DEFAULT 10,
  notes VARCHAR(255) NULL,
  deleted_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admission_no VARCHAR(30) NOT NULL UNIQUE,
  patient_id INT UNSIGNED NOT NULL,
  ward_id INT UNSIGNED NOT NULL,
  bed_label VARCHAR(20) NULL,
  consultant_id INT UNSIGNED NULL,
  diagnosis_on_admit VARCHAR(255) NULL,
  amount_per_day DECIMAL(12,2) NOT NULL DEFAULT 0,
  admitted_by INT UNSIGNED NULL,
  admitted_at DATETIME NOT NULL,
  status ENUM('admitted','discharged') NOT NULL DEFAULT 'admitted',
  discharged_at DATETIME NULL,
  discharge_summary TEXT NULL,
  deleted_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_status (status),
  KEY idx_patient (patient_id),
  KEY idx_ward_status (ward_id, status),
  KEY idx_admitted_at (admitted_at),
  KEY idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NULL,
  name VARCHAR(150) NOT NULL,
  category ENUM('drug','supply','equipment','other') NOT NULL DEFAULT 'drug',
  unit VARCHAR(30) NOT NULL DEFAULT 'unit',
  quantity INT NOT NULL DEFAULT 0,
  reorder_level INT UNSIGNED NOT NULL DEFAULT 5,
  selling_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  notes VARCHAR(255) NULL,
  deleted_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock_movements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL,
  movement_type ENUM('purchase','issue','wastage','adjustment','dispense') NOT NULL,
  reference VARCHAR(80) NULL,
  batch_no VARCHAR(40) NULL,
  expiry_date DATE NULL,
  notes VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prescriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  prescription_no VARCHAR(30) NOT NULL UNIQUE,
  consultation_id INT UNSIGNED NULL,
  patient_id INT UNSIGNED NOT NULL,
  prescribed_by INT UNSIGNED NULL,
  notes TEXT NULL,
  status ENUM('active','dispensed','cancelled') NOT NULL DEFAULT 'active',
  dispensed_by INT UNSIGNED NULL,
  dispensed_at DATETIME NULL,
  prescribed_at DATETIME NOT NULL,
  KEY idx_status (status),
  KEY idx_patient (patient_id),
  KEY idx_prescribed_at (prescribed_at),
  KEY idx_consultation (consultation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS prescription_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  prescription_id INT UNSIGNED NOT NULL,
  item_id INT UNSIGNED NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  quantity_dispensed INT UNSIGNED NOT NULL DEFAULT 0,
  dosage VARCHAR(80) NULL,
  frequency VARCHAR(80) NULL,
  duration VARCHAR(80) NULL,
  notes VARCHAR(255) NULL,
  dispensed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_prescription (prescription_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lab_tests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NULL,
  name VARCHAR(120) NOT NULL,
  category VARCHAR(60) NULL,
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  unit VARCHAR(40) NULL,
  normal_range VARCHAR(120) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  deleted_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lab_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_no VARCHAR(30) NOT NULL UNIQUE,
  patient_id INT UNSIGNED NOT NULL,
  doctor_id INT UNSIGNED NULL,
  consultation_id INT UNSIGNED NULL,
  ward_id INT UNSIGNED NULL,
  priority ENUM('routine','urgent','stat') NOT NULL DEFAULT 'routine',
  clinical_notes TEXT NULL,
  status ENUM('pending','sample_collected','processing','resulted','cancelled') NOT NULL DEFAULT 'pending',
  requested_by INT UNSIGNED NULL,
  requested_at DATETIME NOT NULL,
  resulted_by INT UNSIGNED NULL,
  resulted_at DATETIME NULL,
  KEY idx_status (status),
  KEY idx_patient (patient_id),
  KEY idx_requested_at (requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lab_request_tests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lab_request_id INT UNSIGNED NOT NULL,
  test_id INT UNSIGNED NOT NULL,
  result_value VARCHAR(255) NULL,
  result_note VARCHAR(255) NULL,
  resulted_by INT UNSIGNED NULL,
  resulted_at DATETIME NULL,
  KEY idx_request (lab_request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NULL,
  name VARCHAR(150) NOT NULL,
  category ENUM('consultation','lab','drug','ward','procedure','caregiving','other') NOT NULL DEFAULT 'consultation',
  price DECIMAL(12,2) NOT NULL DEFAULT 0,
  description VARCHAR(255) NULL,
  deleted_at DATETIME NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(30) NOT NULL UNIQUE,
  patient_id INT UNSIGNED NULL,
  invoice_date DATE NOT NULL,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  status ENUM('unpaid','partial','paid','void') NOT NULL DEFAULT 'unpaid',
  notes VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_date (invoice_date),
  KEY idx_status (status),
  KEY idx_patient (patient_id),
  KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoice_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT UNSIGNED NOT NULL,
  item_type VARCHAR(20) NOT NULL DEFAULT 'service',
  ref_id INT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  qty INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  KEY idx_invoice (invoice_id),
  KEY idx_ref (item_type, ref_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payment_no VARCHAR(30) NOT NULL UNIQUE,
  invoice_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  method ENUM('cash','card','transfer','bank','other') NOT NULL DEFAULT 'cash',
  reference_no VARCHAR(80) NULL,
  received_by INT UNSIGNED NULL,
  paid_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_invoice (invoice_id),
  KEY idx_paid_at (paid_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  skey VARCHAR(80) NOT NULL PRIMARY KEY,
  svalue TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(40) NOT NULL,
  module VARCHAR(40) NULL,
details VARCHAR(255) NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_created (created_at),
  KEY idx_user (user_id),
  -- Serves the database-backed login throttle (failed attempts per IP).
  KEY idx_action_ip (action, ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Referential integrity.
--
-- ON DELETE RESTRICT everywhere, deliberately: no CASCADE. Cascading would
-- silently destroy clinical and financial history when a master record is
-- removed, which is exactly the defect this constraint set prevents. Master
-- records (patients, staff, wards, catalogue items) are archived via
-- deleted_at instead of being deleted.
-- ---------------------------------------------------------------------------

ALTER TABLE users ADD CONSTRAINT fk_user_patient
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT;

ALTER TABLE appointments
  ADD CONSTRAINT fk_appt_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_appt_doctor  FOREIGN KEY (doctor_id)  REFERENCES users(id)     ON DELETE RESTRICT;

ALTER TABLE consultations
  ADD CONSTRAINT fk_consult_patient FOREIGN KEY (patient_id)     REFERENCES patients(id)      ON DELETE RESTRICT,
  ADD CONSTRAINT fk_consult_doctor  FOREIGN KEY (doctor_id)      REFERENCES users(id)          ON DELETE RESTRICT,
  ADD CONSTRAINT fk_consult_appt    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE RESTRICT;

ALTER TABLE admissions
  ADD CONSTRAINT fk_adm_patient    FOREIGN KEY (patient_id)    REFERENCES patients(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_adm_ward       FOREIGN KEY (ward_id)       REFERENCES wards(id)    ON DELETE RESTRICT,
  ADD CONSTRAINT fk_adm_consultant FOREIGN KEY (consultant_id) REFERENCES users(id)    ON DELETE RESTRICT;

ALTER TABLE prescriptions
  ADD CONSTRAINT fk_rx_patient FOREIGN KEY (patient_id)     REFERENCES patients(id)      ON DELETE RESTRICT,
  ADD CONSTRAINT fk_rx_doctor  FOREIGN KEY (prescribed_by)  REFERENCES users(id)          ON DELETE RESTRICT,
  ADD CONSTRAINT fk_rx_consult FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE RESTRICT;

ALTER TABLE prescription_items
  ADD CONSTRAINT fk_rxi_rx   FOREIGN KEY (prescription_id) REFERENCES prescriptions(id)   ON DELETE RESTRICT,
  ADD CONSTRAINT fk_rxi_item FOREIGN KEY (item_id)         REFERENCES inventory_items(id) ON DELETE RESTRICT;

ALTER TABLE lab_requests
  ADD CONSTRAINT fk_labreq_patient FOREIGN KEY (patient_id)     REFERENCES patients(id)      ON DELETE RESTRICT,
  ADD CONSTRAINT fk_labreq_doctor  FOREIGN KEY (doctor_id)      REFERENCES users(id)          ON DELETE RESTRICT,
  ADD CONSTRAINT fk_labreq_consult FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_labreq_ward    FOREIGN KEY (ward_id)        REFERENCES wards(id)         ON DELETE RESTRICT;

ALTER TABLE lab_request_tests
  ADD CONSTRAINT fk_lrt_req  FOREIGN KEY (lab_request_id) REFERENCES lab_requests(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_lrt_test FOREIGN KEY (test_id)        REFERENCES lab_tests(id)    ON DELETE RESTRICT;

ALTER TABLE invoices
  ADD CONSTRAINT fk_inv_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT;

ALTER TABLE invoice_items
  ADD CONSTRAINT fk_invi_inv FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT;

ALTER TABLE payments
  ADD CONSTRAINT fk_pay_inv FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT;

ALTER TABLE stock_movements
  ADD CONSTRAINT fk_sm_item FOREIGN KEY (item_id) REFERENCES inventory_items(id) ON DELETE RESTRICT;

ALTER TABLE activity_logs
  ADD CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS care_engagements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_no VARCHAR(30) NOT NULL UNIQUE,
  patient_id INT UNSIGNED NOT NULL,
  care_type ENUM('home','bedside') NOT NULL DEFAULT 'home',
  shift_type ENUM('day_8h','night_12h','full_24h','custom') NOT NULL DEFAULT 'day_8h',
  service_id INT UNSIGNED NULL,
  rate_per_shift DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_days INT UNSIGNED NOT NULL DEFAULT 1,
  start_date DATE NOT NULL,
  end_date DATE NULL,
  location_address VARCHAR(255) NULL,
  ward_id INT UNSIGNED NULL,
  bed_label VARCHAR(20) NULL,
  special_instructions TEXT NULL,
  emergency_contact_name VARCHAR(120) NULL,
  emergency_contact_phone VARCHAR(40) NULL,
  primary_caregiver_id INT UNSIGNED NULL,
  status ENUM('pending','approved','active','completed','cancelled') NOT NULL DEFAULT 'pending',
  invoice_id INT UNSIGNED NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  KEY idx_status (status),
  KEY idx_patient (patient_id),
  KEY idx_caregiver (primary_caregiver_id),
  KEY idx_dates (start_date, end_date),
  KEY idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS care_shift_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  engagement_id INT UNSIGNED NOT NULL,
  caregiver_id INT UNSIGNED NOT NULL,
  shift_date DATE NOT NULL,
  shift_start TIME NULL,
  shift_end TIME NULL,
  status ENUM('scheduled','in_progress','completed','missed') NOT NULL DEFAULT 'scheduled',
  vitals_summary VARCHAR(255) NULL,
  feeding_notes TEXT NULL,
  mobility_notes TEXT NULL,
  hygiene_notes TEXT NULL,
  medication_administered TEXT NULL,
  general_notes TEXT NULL,
  supervisor_notes TEXT NULL,
  supervisor_reviewed_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_engagement (engagement_id),
  KEY idx_caregiver_date (caregiver_id, shift_date),
  KEY idx_shift_date (shift_date),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE care_engagements
  ADD CONSTRAINT fk_ce_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_ce_caregiver FOREIGN KEY (primary_caregiver_id) REFERENCES users(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_ce_ward FOREIGN KEY (ward_id) REFERENCES wards(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_ce_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_ce_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT;

ALTER TABLE care_shift_logs
  ADD CONSTRAINT fk_csl_engagement FOREIGN KEY (engagement_id) REFERENCES care_engagements(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_csl_caregiver FOREIGN KEY (caregiver_id) REFERENCES users(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_csl_supervisor FOREIGN KEY (supervisor_reviewed_by) REFERENCES users(id) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;