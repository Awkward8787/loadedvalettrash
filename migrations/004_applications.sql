-- Porter applications and the public-form attempt log.
-- Each statement is safe to run again. A half-applied file can be re-run
-- without dropping data and without a foreign key that cascade-deletes.

CREATE TABLE IF NOT EXISTS applications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(190) NOT NULL,
  nights VARCHAR(40) NOT NULL,
  has_transportation TINYINT(1) NOT NULL,
  is_adult TINYINT(1) NOT NULL,
  experience TEXT NULL,
  notes TEXT NULL,
  status ENUM('new', 'contacted', 'hired', 'declined') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY applications_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS form_attempts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  form_key VARCHAR(40) NOT NULL,
  email VARCHAR(190) NOT NULL DEFAULT '',
  ip VARCHAR(64) NOT NULL,
  attempted_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY form_attempts_email (form_key, email, attempted_at),
  KEY form_attempts_ip (form_key, ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE settings ADD COLUMN IF NOT EXISTS porter_job_details TEXT NULL;
