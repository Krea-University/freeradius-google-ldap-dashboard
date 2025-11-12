-- Admin Users Table for Dashboard
-- This table stores administrative users for the dashboard

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `first_login` tinyint(1) DEFAULT 1,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123, bcrypt cost 12)
-- Password will be reset on first login
INSERT INTO `admin_users` (`username`, `password_hash`, `email`, `first_login`) 
VALUES (
  'admin',
  '$2y$12$RtJyHPcekAXoG2wV5jc39uZQ2PqeBCObutxW/MIet5hVjRh2hXUBO',
  'admin@yourdomain.com',
  1
) ON DUPLICATE KEY UPDATE 
  `username` = VALUES(`username`);
