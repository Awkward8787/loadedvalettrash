-- Public contact messages from property managers, residents, and others.
-- Separate from proposal inquiries, which still create pipeline leads.

CREATE TABLE IF NOT EXISTS contact_inquiries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(40) NULL,
  role ENUM('property_manager','resident','other') NOT NULL,
  organization VARCHAR(180) NULL,
  service_area ENUM('valdosta_lowndes','atlanta','tampa','other') NOT NULL,
  need ENUM('valet_trash','junk_pickup','other') NOT NULL,
  message TEXT NOT NULL,
  status ENUM('new','contacted','closed') NOT NULL DEFAULT 'new',
  notes TEXT NULL,
  ip VARCHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_contact_inq_status (status),
  KEY idx_contact_inq_created (created_at),
  KEY idx_contact_inq_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
