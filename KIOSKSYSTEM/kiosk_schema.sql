-- kiosk_schema.sql
-- SQL schema for KIOSKSYSTEM PHP app

CREATE DATABASE IF NOT EXISTS `kiosksystem` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kiosksystem`;

-- Admin users table
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Window users (for window_dashboard.php authentication)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `window_label` VARCHAR(32) DEFAULT NULL,
  `role` ENUM('window','admin') NOT NULL DEFAULT 'window',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students registration table
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` VARCHAR(64) NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `department` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `facebook_id` VARCHAR(255) DEFAULT NULL,
  `login_type` ENUM('student','email','facebook') NOT NULL DEFAULT 'student',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_student_id` (`student_id`),
  UNIQUE KEY `idx_email` (`email`),
  INDEX `idx_login_type` (`login_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Students queue table
CREATE TABLE IF NOT EXISTS `students_queue` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue_number` VARCHAR(32) NOT NULL,
  `student_id` VARCHAR(64) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `department` VARCHAR(255) NOT NULL,
  `purpose` VARCHAR(255) NOT NULL,
  `window_number` INT UNSIGNED NOT NULL,
  `status` ENUM('waiting','serving','hold','done') NOT NULL DEFAULT 'waiting',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `served_at` DATETIME NULL DEFAULT NULL,
  `completed_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_window_number` (`window_number`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default window users (safe to run multiple times because of unique usernames)
INSERT INTO `users` (`username`, `password_hash`, `window_label`, `role`)
VALUES
  ('window1', '$2y$10$PEQQJVkO8H2NZo1Rv0wpLOnGWMXMZZ7gPnok.7FU6/tgzrR2rR38y', 'Window 1', 'window'),
  ('window2', '$2y$10$Z9x3CrTAVZphaH7Ejlzm8uxCYT6mO6pEE.17xnRLi5UcPR/Q/uNKq', 'Window 2', 'window'),
  ('window3', '$2y$10$Yq4YkGGAIeqLDCfQv/HGKuVNbHQzMBDVkiHAm3mrQcHdNaCoB8ljq', 'Window 3', 'window'),
  ('window4', '$2y$10$sLkmuCRFc4e40WlOSJCiZ.D1ugiDr1OLahJR9dqnzuxX1boB6OErm', 'Window 4', 'window'),
  ('window5', '$2y$10$Yq4YkGGAIeqLDCfQv/HGKuVNbHQzMBDVkiHAm3mrQcHdNaCoB8ljq', 'Window 5', 'window'),
  ('window6', '$2y$10$sLkmuCRFc4e40WlOSJCiZ.D1ugiDr1OLahJR9dqnzuxX1boB6OErm', 'Window 6', 'window')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

-- Optional: sample admin (username: admin, password: admin123)
-- To create a real admin, use the app setup page instead of inserting plaintext passwords.
-- The line below is commented out; uncomment and set a secure password hash if you want to insert via SQL.
-- INSERT INTO `admin_users` (`username`, `password_hash`) VALUES ('admin', '$2y$10$EXAMPLEHASH');

-- End of schema
