-- Migration: Create login_attempts table
-- Created: 2025-11-10
-- Description: Add missing login_attempts table for authentication tracking

-- Check database prefix from config (default: ndgf_)
-- Replace {prefix} with your actual prefix if different

CREATE TABLE IF NOT EXISTS `ndgf_login_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `attempted_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_username` (`username`),
  INDEX `idx_ip_address` (`ip_address`),
  INDEX `idx_attempted_at` (`attempted_at`),
  INDEX `idx_success` (`success`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
