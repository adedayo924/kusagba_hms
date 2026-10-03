-- Kusagba HMS — migration 003: data integrity, soft deletes, indexes, foreign keys
-- Run ONCE against an existing database (phpMyAdmin / CLI), e.g.:
--   mysql -u USER -p DBNAME < install/migrations/003_integrity.sql
--
-- Fresh installs already contain all of this in install/schema.sql; do not run
-- this migration on a database created from the current schema.sql.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 1. Soft-delete + record-history columns
-- ---------------------------------------------------------------------------
-- Master records (patients, staff, catalogue items) are archived rather than
-- deleted so clinical and financial history is never orphaned.

ALTER TABLE patients
  ADD COLUMN deleted_at DATETIME NULL AFTER notes,
  ADD COLUMN updated_at DATETIME NULL DEFAULT NULL AFTER deleted_at;

ALTER TABLE users       ADD COLUMN deleted_at DATETIME NULL AFTER active;
ALTER TABLE wards       ADD COLUMN deleted_at DATETIME NULL AFTER created_at;
ALTER TABLE services    ADD COLUMN deleted_at DATETIME NULL AFTER active;
ALTER TABLE lab_tests   ADD COLUMN deleted_at DATETIME NULL AFTER active;
ALTER TABLE inventory_items ADD COLUMN deleted_at DATETIME NULL AFTER active;
ALTER TABLE admissions  ADD COLUMN deleted_at DATETIME NULL AFTER discharge_summary;
ALTER TABLE appointments ADD COLUMN deleted_at DATETIME NULL AFTER updated_at;

-- ---------------------------------------------------------------------------
-- 2. Record changelog (field-level "what changed and when")
-- ---------------------------------------------------------------------------
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

-- ---------------------------------------------------------------------------
-- 3. Indexes for the hot report/dashboard paths
-- ---------------------------------------------------------------------------
-- payments.paid_at and lab_requests.requested_at were filtered on every
-- dashboard and report load but carried no index.
ALTER TABLE payments       ADD KEY idx_paid_at (paid_at);
ALTER TABLE lab_requests   ADD KEY idx_requested_at (requested_at);
ALTER TABLE users          ADD KEY idx_role (role);
ALTER TABLE activity_logs  ADD KEY idx_user (user_id);
ALTER TABLE activity_logs  ADD KEY idx_action_ip (action, ip);
ALTER TABLE invoice_items  ADD KEY idx_ref (item_type, ref_id);
ALTER TABLE invoices       ADD KEY idx_patient (patient_id);
ALTER TABLE invoices       ADD KEY idx_created_at (created_at);
ALTER TABLE patients       ADD KEY idx_created_at (created_at);
ALTER TABLE appointments   ADD KEY idx_doctor_date (doctor_id, appointment_date);
ALTER TABLE appointments   ADD KEY idx_created_at (created_at);
ALTER TABLE appointments   ADD KEY idx_deleted (deleted_at);
ALTER TABLE admissions     ADD KEY idx_ward_status (ward_id, status);
ALTER TABLE admissions     ADD KEY idx_admitted_at (admitted_at);
ALTER TABLE admissions     ADD KEY idx_deleted (deleted_at);
ALTER TABLE prescriptions  ADD KEY idx_prescribed_at (prescribed_at);
ALTER TABLE prescriptions  ADD KEY idx_consultation (consultation_id);

-- ---------------------------------------------------------------------------
-- 4. One portal account per patient
-- ---------------------------------------------------------------------------
-- Preserve existing duplicate accounts by detaching the later ones rather
-- than deleting them (non-destructive), then enforce uniqueness.
UPDATE users u1
  JOIN users u2 ON u1.patient_id = u2.patient_id AND u1.id > u2.id
  SET u1.patient_id = NULL;

ALTER TABLE users ADD UNIQUE KEY uq_users_patient (patient_id);

-- ---------------------------------------------------------------------------
-- 5. Foreign keys
-- ---------------------------------------------------------------------------
-- Deliberately ON DELETE RESTRICT everywhere: no CASCADE. Cascading would
-- silently destroy clinical and financial history, which is the defect this
-- migration exists to prevent.

-- consultations.doctor_id was NOT NULL but is written from Auth::id(), which let a
-- nurse or admin consultation be filed under a non-doctor. Relax to NULL so the
-- attribution can be correct and so dangling references can be detached below.
ALTER TABLE consultations MODIFY COLUMN doctor_id INT UNSIGNED NULL;

-- Orphan cleanup: nullable references are detached, NOT NULL references drop
-- the unusable orphan row.
UPDATE appointments   SET doctor_id = NULL      WHERE doctor_id IS NOT NULL AND doctor_id NOT IN (SELECT id FROM users);
UPDATE consultations  SET doctor_id = NULL      WHERE doctor_id IS NOT NULL AND doctor_id NOT IN (SELECT id FROM users);
UPDATE admissions     SET consultant_id = NULL  WHERE consultant_id IS NOT NULL AND consultant_id NOT IN (SELECT id FROM users);
UPDATE lab_requests   SET doctor_id = NULL      WHERE doctor_id IS NOT NULL AND doctor_id NOT IN (SELECT id FROM users);
UPDATE prescriptions  SET prescribed_by = NULL  WHERE prescribed_by IS NOT NULL AND prescribed_by NOT IN (SELECT id FROM users);
UPDATE prescription_items SET item_id = NULL    WHERE item_id IS NOT NULL AND item_id NOT IN (SELECT id FROM inventory_items);
UPDATE activity_logs  SET user_id = NULL        WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users);
UPDATE invoices          SET patient_id = NULL      WHERE patient_id IS NOT NULL AND patient_id NOT IN (SELECT id FROM patients);
UPDATE consultations     SET appointment_id = NULL  WHERE appointment_id IS NOT NULL AND appointment_id NOT IN (SELECT id FROM appointments);
UPDATE lab_requests      SET consultation_id = NULL WHERE consultation_id IS NOT NULL AND consultation_id NOT IN (SELECT id FROM consultations);
UPDATE lab_requests      SET ward_id = NULL         WHERE ward_id IS NOT NULL AND ward_id NOT IN (SELECT id FROM wards);
UPDATE prescriptions     SET consultation_id = NULL WHERE consultation_id IS NOT NULL AND consultation_id NOT IN (SELECT id FROM consultations);
UPDATE users             SET patient_id = NULL      WHERE patient_id IS NOT NULL AND patient_id NOT IN (SELECT id FROM patients);

DELETE FROM appointments    WHERE patient_id NOT IN (SELECT id FROM patients);
DELETE FROM consultations   WHERE patient_id NOT IN (SELECT id FROM patients);
DELETE FROM admissions      WHERE patient_id NOT IN (SELECT id FROM patients)
                                OR ward_id NOT IN (SELECT id FROM wards);
DELETE FROM prescriptions   WHERE patient_id NOT IN (SELECT id FROM patients);
DELETE FROM lab_requests    WHERE patient_id NOT IN (SELECT id FROM patients);
DELETE FROM stock_movements WHERE item_id NOT IN (SELECT id FROM inventory_items);
DELETE FROM prescription_items WHERE prescription_id NOT IN (SELECT id FROM prescriptions);
DELETE FROM lab_request_tests  WHERE lab_request_id NOT IN (SELECT id FROM lab_requests)
                                  OR test_id NOT IN (SELECT id FROM lab_tests);
DELETE FROM invoice_items   WHERE invoice_id NOT IN (SELECT id FROM invoices);
DELETE FROM payments        WHERE invoice_id NOT IN (SELECT id FROM invoices);

ALTER TABLE appointments
  ADD CONSTRAINT fk_appt_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_appt_doctor  FOREIGN KEY (doctor_id)  REFERENCES users(id)     ON DELETE RESTRICT;

ALTER TABLE consultations
  ADD CONSTRAINT fk_consult_patient FOREIGN KEY (patient_id)     REFERENCES patients(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_consult_doctor  FOREIGN KEY (doctor_id)      REFERENCES users(id)     ON DELETE RESTRICT,
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

ALTER TABLE users
  ADD CONSTRAINT fk_user_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT;

SET FOREIGN_KEY_CHECKS = 1;