-- Migrations for builds applied AFTER initial install.
-- Run this file against an existing database (e.g. via phpMyAdmin) to bring it
-- up to date with install/schema.sql.

SET NAMES utf8mb4;

-- 1) Rebuild prescriptions into a header + lines model.
ALTER TABLE prescriptions CHANGE COLUMN doctor_id prescribed_by INT UNSIGNED NULL;
ALTER TABLE prescriptions CHANGE COLUMN instructions notes TEXT NULL;
ALTER TABLE prescriptions ADD COLUMN prescribed_at DATETIME NULL AFTER notes;
UPDATE prescriptions SET prescribed_at = created_at WHERE prescribed_at IS NULL;
ALTER TABLE prescriptions DROP COLUMN drug_id;
ALTER TABLE prescriptions DROP COLUMN dosage;
ALTER TABLE prescriptions DROP COLUMN frequency;
ALTER TABLE prescriptions DROP COLUMN duration;
ALTER TABLE prescriptions DROP COLUMN quantity;

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