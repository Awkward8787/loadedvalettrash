-- Junk pickup rates start empty. A null rate is not a price.
-- Estimates store the items the visitor chose and the range shown at submit time, if every chosen line had a rate.

CREATE TABLE IF NOT EXISTS junk_rates (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  couch_low_cents INT UNSIGNED NULL,
  couch_high_cents INT UNSIGNED NULL,
  mattress_low_cents INT UNSIGNED NULL,
  mattress_high_cents INT UNSIGNED NULL,
  appliance_low_cents INT UNSIGNED NULL,
  appliance_high_cents INT UNSIGNED NULL,
  table_low_cents INT UNSIGNED NULL,
  table_high_cents INT UNSIGNED NULL,
  bags_low_cents INT UNSIGNED NULL,
  bags_high_cents INT UNSIGNED NULL,
  quarter_low_cents INT UNSIGNED NULL,
  quarter_high_cents INT UNSIGNED NULL,
  half_low_cents INT UNSIGNED NULL,
  half_high_cents INT UNSIGNED NULL,
  full_low_cents INT UNSIGNED NULL,
  full_high_cents INT UNSIGNED NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO junk_rates (id, updated_at)
SELECT 1, '2026-11-01 00:00:00' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM junk_rates WHERE id = 1);

CREATE TABLE IF NOT EXISTS junk_estimates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NULL,
  address VARCHAR(255) NOT NULL,
  service_area ENUM('valdosta_lowndes','atlanta','tampa','other') NOT NULL,
  preferred_on DATE NOT NULL,
  mode ENUM('items','load') NOT NULL,
  load_size ENUM('quarter','half','full') NULL,
  items_json TEXT NULL,
  note TEXT NULL,
  estimate_low_cents INT UNSIGNED NULL,
  estimate_high_cents INT UNSIGNED NULL,
  photo_path VARCHAR(255) NULL,
  status ENUM('new','quoted','booked','closed') NOT NULL DEFAULT 'new',
  notes TEXT NULL,
  ip VARCHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_junk_est_status (status),
  KEY idx_junk_est_created (created_at),
  KEY idx_junk_est_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
