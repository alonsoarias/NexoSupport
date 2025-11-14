--
-- Theme System Tables Migration
--
-- Creates tables for theme configuration and asset management
--
-- @version 1.0.0
-- @date 2025-11-14
-- @week Week 5-6 - Theme System Implementation
--

-- ============================================================================
-- Theme Settings Table
-- ============================================================================
-- Stores theme configuration key-value pairs with type information

CREATE TABLE IF NOT EXISTS `theme_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Setting identifier (supports dot notation)',
    `setting_value` TEXT NULL COMMENT 'Setting value (serialized if complex)',
    `setting_type` ENUM('color', 'font', 'size', 'url', 'boolean', 'text') DEFAULT 'text' COMMENT 'Data type for validation',
    `category` VARCHAR(50) DEFAULT 'general' COMMENT 'Setting category (colors, typography, layout, etc.)',
    `created_at` INT UNSIGNED NOT NULL COMMENT 'Unix timestamp of creation',
    `updated_at` INT UNSIGNED NOT NULL COMMENT 'Unix timestamp of last update',
    INDEX `idx_category` (`category`),
    INDEX `idx_type` (`setting_type`),
    INDEX `idx_updated` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Theme configuration settings';

-- ============================================================================
-- Theme Assets Table
-- ============================================================================
-- Stores uploaded theme assets (logos, favicons, backgrounds, etc.)

CREATE TABLE IF NOT EXISTS `theme_assets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `asset_type` ENUM('logo', 'favicon', 'background', 'icon', 'image') NOT NULL COMMENT 'Type of asset',
    `file_path` VARCHAR(255) NOT NULL COMMENT 'Relative path to asset file',
    `file_name` VARCHAR(255) NOT NULL COMMENT 'Original filename',
    `mime_type` VARCHAR(100) NULL COMMENT 'MIME type (image/png, image/jpeg, etc.)',
    `file_size` INT UNSIGNED NULL COMMENT 'File size in bytes',
    `width` INT UNSIGNED NULL COMMENT 'Image width in pixels',
    `height` INT UNSIGNED NULL COMMENT 'Image height in pixels',
    `is_active` BOOLEAN DEFAULT 0 COMMENT 'Whether this is the active asset of its type',
    `uploaded_at` INT UNSIGNED NOT NULL COMMENT 'Unix timestamp of upload',
    `uploaded_by` INT UNSIGNED NULL COMMENT 'User ID who uploaded',
    INDEX `idx_type` (`asset_type`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_uploaded` (`uploaded_at`),
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Theme asset files (logos, icons, images)';

-- ============================================================================
-- Insert Default Theme Settings
-- ============================================================================
-- Populate with default theme configuration

-- Colors
INSERT INTO `theme_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `created_at`, `updated_at`) VALUES
('colors.primary', '#667eea', 'color', 'colors', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('colors.secondary', '#764ba2', 'color', 'colors', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('colors.success', '#10b981', 'color', 'colors', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('colors.warning', '#f59e0b', 'color', 'colors', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('colors.danger', '#ef4444', 'color', 'colors', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('colors.info', '#3b82f6', 'color', 'colors', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('colors.light', '#f8f9fa', 'color', 'colors', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('colors.dark', '#212529', 'color', 'colors', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('colors.body_bg', '#ffffff', 'color', 'colors', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('colors.body_text', '#212529', 'color', 'colors', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('colors.link', '#667eea', 'color', 'colors', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('colors.border', '#dee2e6', 'color', 'colors', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Typography
INSERT INTO `theme_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `created_at`, `updated_at`) VALUES
('typography.font_family_base', 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif', 'font', 'typography', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('typography.font_family_heading', 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif', 'font', 'typography', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('typography.font_family_mono', '"JetBrains Mono", "Fira Code", Consolas, monospace', 'font', 'typography', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('typography.font_size_base', '16px', 'size', 'typography', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('typography.font_size_sm', '14px', 'size', 'typography', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('typography.font_size_lg', '18px', 'size', 'typography', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('typography.line_height_base', '1.5', 'text', 'typography', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('typography.headings.h1', '2.5rem', 'size', 'typography', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('typography.headings.h2', '2rem', 'size', 'typography', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('typography.headings.h3', '1.75rem', 'size', 'typography', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('typography.headings.h4', '1.5rem', 'size', 'typography', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('typography.headings.h5', '1.25rem', 'size', 'typography', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('typography.headings.h6', '1rem', 'size', 'typography', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Layout
INSERT INTO `theme_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `created_at`, `updated_at`) VALUES
('layout.sidebar_position', 'left', 'text', 'layout', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('layout.sidebar_width', '280px', 'size', 'layout', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('layout.content_max_width', '1400px', 'size', 'layout', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('layout.container_padding', '20px', 'size', 'layout', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('layout.border_radius', '8px', 'size', 'layout', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('layout.box_shadow', '0 1px 3px rgba(0,0,0,0.12)', 'text', 'layout', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Branding
INSERT INTO `theme_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `created_at`, `updated_at`) VALUES
('branding.logo_url', '/assets/images/logo.png', 'url', 'branding', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('branding.favicon_url', '/assets/images/favicon.ico', 'url', 'branding', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('branding.app_name', 'NexoSupport', 'text', 'branding', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('branding.tagline', 'Professional Support System', 'text', 'branding', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Dark Mode
INSERT INTO `theme_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `created_at`, `updated_at`) VALUES
('dark_mode.enabled', '1', 'boolean', 'dark_mode', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('dark_mode.auto_switch', '0', 'boolean', 'dark_mode', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('dark_mode.switch_time_start', '18:00', 'text', 'dark_mode', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('dark_mode.switch_time_end', '06:00', 'text', 'dark_mode', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ============================================================================
-- Rollback / Cleanup
-- ============================================================================
-- To rollback this migration:
-- DROP TABLE IF EXISTS `theme_assets`;
-- DROP TABLE IF EXISTS `theme_settings`;
