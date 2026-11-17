-- ============================================================
--  Estavia Risk & Security Solutions Ltd — Database Schema
--  Engine: MySQL 5.7+ / MariaDB 10.3+
--  Create/select the estavia_db database first, then import this file
--  through phpMyAdmin. The script creates the tables and seed data.
-- ============================================================

-- ------------------------------------------------------------
-- 1. Admin users (for the admin panel)
-- ------------------------------------------------------------
CREATE TABLE admin_users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(100) NOT NULL DEFAULT 'Administrator',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login    TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default login:  username = admin   password = Estavia@2026
-- !!! CHANGE THIS PASSWORD IMMEDIATELY AFTER FIRST LOGIN !!!
INSERT INTO admin_users (username, password_hash, full_name) VALUES
('admin', '$2y$10$hIRZFt2vnUD8P/zOJdU8uOoiL2zUp1jY6kZVMwjqZPkQovTrikbri', 'Site Administrator');

-- ------------------------------------------------------------
-- 2. Services (powers the Services grid on the homepage)
-- ------------------------------------------------------------
CREATE TABLE services (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  icon_key    VARCHAR(40)  NOT NULL DEFAULT 'shield',
  name        VARCHAR(120) NOT NULL,
  description VARCHAR(255) NOT NULL,
  display_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO services (icon_key, name, description, display_order) VALUES
('clipboard', 'Risk Assessments', 'Identify, assess and mitigate risks for a safer tomorrow.', 1),
('search',    'Private Investigations', 'Discreet, professional and reliable investigative services.', 2),
('audit',     'Security Audits', 'Evaluate, improve and strengthen your security posture.', 3),
('truck',     'Cargo Escort (Local & Transit)', 'Safe and secure movement of your valuable cargo.', 4),
('map',       'Route Survey', 'Assess routes, identify hazards, and plan safer journeys for cargo and personnel.', 5),
('crowd',     'Event / Functions Security Management', 'Professional security for your special events and functions.', 6),
('monitor',   'Control Room Management', '24/7 monitoring, rapid response and incident coordination.', 7),
('install',   'Security Equipment Installation', 'CCTV, access control, alarms, fencing and more.', 8),
('wrench',    'Installation & Maintenance', 'Of safety management & equipment — keep your systems running, always.', 9),
('guard',     'Guarding Services', 'Well-trained, disciplined and professional security personnel.', 10),
('training',  'Security Training Programs', 'Build skills. Enhance awareness. Strengthen your team.', 11),
('port',      'Port Operations Security', 'Secure operations at the port and surrounding areas.', 12),
('warehouse', 'Loss Control (Warehouse, Retail & Logistics)', 'Reduce losses, protect your inventory and assets.', 13),
('badge',     'Background Checks & Vetting', 'Trusted people for a safer environment.', 14),
('car',       'Secure Transport & Guest Transfers', 'Safe, reliable and professional transportation services.', 15),
('chat',      'Security Consultancy', 'Tailored solutions for your unique needs.', 16),
('drone',     'Drone Surveillance & Inspection', 'Advanced monitoring for greater security.', 17),
('fire',      'Fire Safety & Emergency Response', 'Prepared today. Safer tomorrow.', 18),
('clock',     '24/7 Security Operations & Support', 'Always on. Always alert.', 19);

-- ------------------------------------------------------------
-- 3. Quote requests (submitted through the website form)
--    status: new -> contacted -> quoted -> closed
-- ------------------------------------------------------------
CREATE TABLE quote_requests (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  phone      VARCHAR(30)  NOT NULL,
  email      VARCHAR(120) NULL,
  service    VARCHAR(120) NOT NULL,
  message    TEXT NULL,
  status     ENUM('new','contacted','quoted','closed') NOT NULL DEFAULT 'new',
  ip_address VARCHAR(45)  NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample records so the admin dashboard is not empty on first login
INSERT INTO quote_requests (name, phone, email, service, message, status) VALUES
('David Otieno', '0712 345 678', 'd.otieno@example.com', 'Guarding Services',
 'We need 4 guards for a warehouse in Shimanzi, day and night shift.', 'contacted'),
('Sarah Mwangi', '0733 456 789', 's.mwangi@example.com', 'Event / Functions Security Management',
 'Security team needed for a corporate gala at Sarova Whitesands, approx. 300 guests.', 'new'),
('Hassan Ali',  '0720 111 222', NULL, 'Cargo Escort (Local & Transit)',
 'Monthly escort of container trucks from Mombasa port to Nairobi.', 'new');

-- ------------------------------------------------------------
-- 4. Login attempts (rate limiting against brute-force attacks)
-- ------------------------------------------------------------
CREATE TABLE login_attempts (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(50) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  success    TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip (ip_address),
  INDEX idx_user (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
