CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('owner','manager','porter') NOT NULL DEFAULT 'porter',
  phone VARCHAR(40) NULL,
  pay_rate_cents INT UNSIGNED NOT NULL DEFAULT 1500,
  availability TEXT NULL,
  totp_secret TEXT NULL,
  totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recovery_codes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  code_hash CHAR(64) NOT NULL,
  used_at DATETIME NULL,
  CONSTRAINT fk_recovery_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip VARCHAR(64) NOT NULL,
  success TINYINT(1) NOT NULL,
  attempted_at DATETIME NOT NULL,
  KEY idx_login_email (email, attempted_at),
  KEY idx_login_ip (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_sessions (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  ip VARCHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  last_activity DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  UNIQUE KEY uq_session_token (token_hash),
  CONSTRAINT fk_session_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  business_name VARCHAR(120) NOT NULL,
  owner_name VARCHAR(120) NOT NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(190) NULL,
  address TEXT NULL,
  service_area VARCHAR(255) NULL,
  insurance_text TEXT NULL,
  opt_in_price_cents INT UNSIGNED NOT NULL DEFAULT 3500,
  default_property_share_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  minutes_per_door DECIMAL(4,2) NOT NULL DEFAULT 1.00,
  minutes_per_site INT UNSIGNED NOT NULL DEFAULT 45,
  pickup_window_minutes INT UNSIGNED NOT NULL DEFAULT 120,
  setout_start TIME NOT NULL DEFAULT '18:00:00',
  setout_end TIME NOT NULL DEFAULT '20:00:00',
  pickup_start TIME NOT NULL DEFAULT '20:00:00',
  pickup_end TIME NOT NULL DEFAULT '22:00:00',
  max_bags_per_night TINYINT UNSIGNED NOT NULL DEFAULT 2,
  holiday_bags TINYINT UNSIGNED NOT NULL DEFAULT 4,
  max_bag_weight_lb TINYINT UNSIGNED NOT NULL DEFAULT 25,
  default_wage_cents INT UNSIGNED NOT NULL DEFAULT 1500,
  payroll_multiplier DECIMAL(4,2) NOT NULL DEFAULT 1.10,
  per_site_supplies_cents INT UNSIGNED NOT NULL DEFAULT 4000,
  per_site_gas_cents INT UNSIGNED NOT NULL DEFAULT 9000,
  per_site_vehicle_reserve_cents INT UNSIGNED NOT NULL DEFAULT 2500,
  monthly_fixed_costs_cents INT UNSIGNED NOT NULL DEFAULT 34900,
  remittance_due_day TINYINT UNSIGNED NOT NULL DEFAULT 10,
  session_timeout_minutes INT UNSIGNED NOT NULL DEFAULT 30,
  missed_report_by VARCHAR(40) NOT NULL DEFAULT '10:00 AM',
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (
  id, business_name, owner_name, phone, email, address, service_area, insurance_text,
  opt_in_price_cents, default_property_share_percent, minutes_per_door, minutes_per_site,
  pickup_window_minutes, setout_start, setout_end, pickup_start, pickup_end,
  max_bags_per_night, holiday_bags, max_bag_weight_lb, default_wage_cents, payroll_multiplier,
  per_site_supplies_cents, per_site_gas_cents, per_site_vehicle_reserve_cents, monthly_fixed_costs_cents,
  remittance_due_day, session_timeout_minutes, missed_report_by, updated_at
) VALUES (
  1, 'Loaded Valet Trash', 'Anthony Jaamal Jones', NULL, NULL, NULL,
  'Valdosta, Lowndes County, Hahira, and Remerton, Georgia', NULL,
  3500, 10.00, 1.00, 45,
  120, '18:00:00', '20:00:00', '20:00:00', '22:00:00',
  2, 4, 25, 1500, 1.10,
  4000, 9000, 2500, 34900,
  10, 30, '10:00 AM', '2026-10-22 00:00:00'
);

CREATE TABLE holidays (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  observed_on DATE NOT NULL,
  service_off TINYINT(1) NOT NULL DEFAULT 1,
  instructions VARCHAR(255) NOT NULL,
  UNIQUE KEY uq_holiday_date (observed_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO holidays (name, observed_on, service_off, instructions) VALUES
('Thanksgiving Day', '2026-11-26', 1, 'No service. Resume Fri 11/27. Up to 4 bags that night.'),
('Christmas Day', '2026-12-25', 1, 'No service. Resume Mon 12/28. Up to 4 bags.'),
('New Year''s Day', '2027-01-01', 1, 'No service. Resume Mon 1/4. Up to 4 bags.'),
('Martin Luther King Jr. Day', '2027-01-18', 0, 'Normal service.'),
('Memorial Day', '2027-05-31', 1, 'No service. Resume Tue 6/1. Up to 4 bags.'),
('Independence Day (observed)', '2027-07-05', 1, 'No service. Resume Tue 7/6. Up to 4 bags.'),
('Labor Day', '2027-09-06', 1, 'No service. Resume Tue 9/7. Up to 4 bags.'),
('Thanksgiving Day', '2027-11-25', 1, 'No service. Resume Fri 11/26. Up to 4 bags that night.'),
('Christmas Eve (Christmas observed)', '2027-12-24', 1, 'No service. Resume Mon 12/27. Up to 4 bags.');

CREATE TABLE management_companies (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_company_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE properties (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  management_company_id INT UNSIGNED NULL,
  name VARCHAR(180) NOT NULL,
  address VARCHAR(255) NULL,
  city VARCHAR(80) NULL,
  state CHAR(2) NOT NULL DEFAULT 'GA',
  postal_code VARCHAR(20) NULL,
  units_count INT UNSIGNED NOT NULL DEFAULT 0,
  planned_doors INT UNSIGNED NULL,
  website VARCHAR(255) NULL,
  has_valet_trash ENUM('unknown','yes','no') NOT NULL DEFAULT 'unknown',
  stage ENUM('lead','contacted','walkthrough','proposal','signed','live','lost') NOT NULL DEFAULT 'lead',
  lost_reason VARCHAR(255) NULL,
  pricing_model ENUM('opt_in','per_door','flat') NOT NULL DEFAULT 'opt_in',
  opt_in_price_cents INT UNSIGNED NULL,
  property_share_percent DECIMAL(5,2) NULL,
  per_door_rate_cents INT UNSIGNED NULL,
  flat_rate_cents INT UNSIGNED NULL,
  service_days VARCHAR(64) NOT NULL DEFAULT 'mon,tue,wed,thu,fri',
  setout_start TIME NULL,
  setout_end TIME NULL,
  pickup_start TIME NULL,
  pickup_end TIME NULL,
  minutes_per_door DECIMAL(4,2) NULL,
  minutes_fixed INT UNSIGNED NULL,
  compactor_location VARCHAR(255) NULL,
  gate_access_notes TEXT NULL,
  contract_start DATE NULL,
  contract_end DATE NULL,
  trial_start DATE NULL,
  trial_end DATE NULL,
  follow_up_on DATE NULL,
  supplies_cents INT UNSIGNED NULL,
  gas_cents INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_prop_company FOREIGN KEY (management_company_id) REFERENCES management_companies(id) ON DELETE SET NULL,
  KEY idx_prop_stage (stage),
  KEY idx_prop_follow (follow_up_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE property_contacts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  property_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  role_title VARCHAR(80) NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(190) NULL,
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_contact_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE property_notes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  property_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_note_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_note_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE buildings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  property_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  floors TINYINT UNSIGNED NULL,
  unit_count INT UNSIGNED NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  hazards_notes VARCHAR(255) NULL,
  compactor_trip_after TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_bldg_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  KEY idx_bldg_order (property_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE residents (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  property_id INT UNSIGNED NOT NULL,
  building_id INT UNSIGNED NULL,
  unit_number VARCHAR(40) NOT NULL,
  name VARCHAR(120) NULL,
  start_date DATE NULL,
  cancel_date DATE NULL,
  status ENUM('pending','active','cancelled') NOT NULL DEFAULT 'active',
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_res_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_res_bldg FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE SET NULL,
  KEY idx_res_prop_status (property_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE route_templates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  porter_id INT UNSIGNED NULL,
  day_of_week TINYINT UNSIGNED NOT NULL,
  start_time TIME NOT NULL DEFAULT '20:00:00',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_tpl_porter FOREIGN KEY (porter_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE route_template_stops (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  template_id INT UNSIGNED NOT NULL,
  property_id INT UNSIGNED NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_tplstop_tpl FOREIGN KEY (template_id) REFERENCES route_templates(id) ON DELETE CASCADE,
  CONSTRAINT fk_tplstop_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE route_runs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  template_id INT UNSIGNED NULL,
  service_date DATE NOT NULL,
  porter_id INT UNSIGNED NULL,
  start_time TIME NOT NULL DEFAULT '20:00:00',
  status ENUM('scheduled','in_progress','complete','cancelled') NOT NULL DEFAULT 'scheduled',
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_run_tpl FOREIGN KEY (template_id) REFERENCES route_templates(id) ON DELETE SET NULL,
  CONSTRAINT fk_run_porter FOREIGN KEY (porter_id) REFERENCES users(id) ON DELETE SET NULL,
  KEY idx_run_date (service_date),
  KEY idx_run_porter_date (porter_id, service_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE route_stops (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  route_run_id INT UNSIGNED NOT NULL,
  property_id INT UNSIGNED NOT NULL,
  building_id INT UNSIGNED NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('pending','done','missed','skipped') NOT NULL DEFAULT 'pending',
  completed_at DATETIME NULL,
  notes VARCHAR(255) NULL,
  CONSTRAINT fk_stop_run FOREIGN KEY (route_run_id) REFERENCES route_runs(id) ON DELETE CASCADE,
  CONSTRAINT fk_stop_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_stop_bldg FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE door_checks (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  route_stop_id INT UNSIGNED NOT NULL,
  resident_id INT UNSIGNED NULL,
  unit_label VARCHAR(40) NOT NULL,
  status ENUM('pending','picked_up','missed','violation') NOT NULL DEFAULT 'pending',
  notes VARCHAR(255) NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_door_stop FOREIGN KEY (route_stop_id) REFERENCES route_stops(id) ON DELETE CASCADE,
  CONSTRAINT fk_door_res FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE time_entries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  route_run_id INT UNSIGNED NULL,
  clock_in DATETIME NOT NULL,
  clock_out DATETIME NULL,
  notes VARCHAR(255) NULL,
  CONSTRAINT fk_time_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_time_run FOREIGN KEY (route_run_id) REFERENCES route_runs(id) ON DELETE SET NULL,
  KEY idx_time_user (user_id, clock_in)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE issues (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  property_id INT UNSIGNED NULL,
  resident_id INT UNSIGNED NULL,
  route_stop_id INT UNSIGNED NULL,
  unit_label VARCHAR(40) NULL,
  external_property_name VARCHAR(180) NULL,
  type ENUM('missed_pickup','violation','other') NOT NULL,
  violation_kind VARCHAR(40) NULL,
  source ENUM('public','porter','staff') NOT NULL,
  status ENUM('open','scheduled_return','resolved') NOT NULL DEFAULT 'open',
  reporter_name VARCHAR(120) NULL,
  reporter_contact VARCHAR(190) NULL,
  reporter_role VARCHAR(80) NULL,
  description TEXT NULL,
  photo_path VARCHAR(255) NULL,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  resolved_at DATETIME NULL,
  CONSTRAINT fk_issue_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
  CONSTRAINT fk_issue_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  KEY idx_issue_status (status),
  KEY idx_issue_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  reviewer_name VARCHAR(120) NOT NULL,
  property_name VARCHAR(180) NOT NULL,
  role_title VARCHAR(80) NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  moderated_by INT UNSIGNED NULL,
  moderated_at DATETIME NULL,
  KEY idx_review_status (status),
  CONSTRAINT fk_review_user FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inquiries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kind ENUM('proposal','walkthrough','other') NOT NULL DEFAULT 'proposal',
  property_name VARCHAR(180) NOT NULL,
  units_count INT UNSIGNED NULL,
  contact_name VARCHAR(120) NOT NULL,
  contact_email VARCHAR(190) NULL,
  contact_phone VARCHAR(40) NULL,
  message TEXT NULL,
  status ENUM('new','contacted','converted','closed') NOT NULL DEFAULT 'new',
  property_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_inq_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
  KEY idx_inq_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoices (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  property_id INT UNSIGNED NOT NULL,
  period_year SMALLINT UNSIGNED NOT NULL,
  period_month TINYINT UNSIGNED NOT NULL,
  pricing_model ENUM('opt_in','per_door','flat') NOT NULL,
  door_count INT UNSIGNED NOT NULL DEFAULT 0,
  rate_cents INT UNSIGNED NOT NULL DEFAULT 0,
  gross_cents INT UNSIGNED NOT NULL DEFAULT 0,
  share_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
  share_cents INT UNSIGNED NOT NULL DEFAULT 0,
  due_cents INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('draft','sent','paid','void') NOT NULL DEFAULT 'draft',
  due_on DATE NOT NULL,
  paid_at DATETIME NULL,
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_invoice_period (property_id, period_year, period_month),
  CONSTRAINT fk_inv_prop FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(40) NULL,
  entity_id INT UNSIGNED NULL,
  details VARCHAR(500) NULL,
  ip VARCHAR(64) NULL,
  created_at DATETIME NOT NULL,
  KEY idx_audit_created (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
