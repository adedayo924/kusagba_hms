-- Migrations for builds applied AFTER initial install.
-- Run this file against an existing database (e.g. via phpMyAdmin) to bring it
-- up to date with install/schema.sql.

SET NAMES utf8mb4;

-- 1) Add a durable, unique receipt number to payments.
ALTER TABLE payments ADD COLUMN payment_no VARCHAR(30) NULL AFTER id;
UPDATE payments SET payment_no = CONCAT('PAY-', DATE_FORMAT(paid_at, '%y%m%d'), '-', LPAD(id, 4, '0')) WHERE payment_no IS NULL;
ALTER TABLE payments MODIFY payment_no VARCHAR(30) NOT NULL;
ALTER TABLE payments ADD UNIQUE KEY uq_payment_no (payment_no);