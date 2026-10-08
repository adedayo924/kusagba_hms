-- Kusagba HMS — migration 004: Care Giving Services
-- Hybrid Home-based and Bedside Caregiving Module with Shift Logging and Billing

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Extend user role to include caregiver
ALTER TABLE users MODIFY COLUMN role ENUM('admin','doctor','nurse','receptionist','pharmacist','lab','cashier','patient','caregiver') NOT NULL DEFAULT 'receptionist';

-- 2. Extend service categories to include caregiving
ALTER TABLE services MODIFY COLUMN category ENUM('consultation','lab','drug','ward','procedure','caregiving','other') NOT NULL DEFAULT 'consultation';

-- 3. Care Engagements table (contracts / bookings for caregiving)
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

-- 4. Care Shift Logs (Daily Living Activity reports, vitals, nutrition, hygiene, medication)
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

-- 5. Referential Integrity Constraints
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

-- 6. Default Caregiving Services catalogue seed
INSERT INTO services (code, name, category, price, description) VALUES
  ('CG-DAY8', 'Caregiving - Day Shift (8 Hours)', 'caregiving', 12000.00, 'Daytime personal care assistance, nutrition support, and mobility assistance (8h).'),
  ('CG-NGT12', 'Caregiving - Night Shift (12 Hours)', 'caregiving', 18000.00, 'Overnight bedside or home care monitoring, safety assistance, and medication administration support (12h).'),
  ('CG-FUL24', 'Caregiving - 24-Hour Live-in Care', 'caregiving', 30000.00, 'Comprehensive round-the-clock live-in or in-hospital bedside care attendant (24h).'),
  ('CG-VISIT', 'Home Nursing Visit (Routine)', 'caregiving', 8000.00, 'Single home nursing check, vitals monitoring, and dressing/injection assistance.');

SET FOREIGN_KEY_CHECKS = 1;
