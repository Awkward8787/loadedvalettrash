-- Public careers applications. Add-only and safe to run more than once.

CREATE TABLE IF NOT EXISTS job_applications (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  role ENUM('laborer','supervisor') NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NOT NULL,
  email VARCHAR(190) NULL,
  city VARCHAR(120) NOT NULL,
  is_adult TINYINT(1) NOT NULL,
  has_transportation TINYINT(1) NOT NULL,
  days_available VARCHAR(40) NOT NULL,
  can_lift TINYINT(1) NOT NULL,
  experience TEXT NULL,
  start_date DATE NOT NULL,
  heard_about VARCHAR(190) NOT NULL,
  consent TINYINT(1) NOT NULL,
  status ENUM('new','reviewing','interview','hired','declined') NOT NULL DEFAULT 'new',
  notes TEXT NULL,
  ip VARCHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_job_app_status (status),
  KEY idx_job_app_role (role),
  KEY idx_job_app_ip (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
