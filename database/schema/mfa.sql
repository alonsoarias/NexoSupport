-- ISER Authentication System - MFA Schema
-- Tablas de autenticación multifactor

-- Tabla de configuración MFA
CREATE TABLE IF NOT EXISTS iser_user_mfa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    method ENUM('totp', 'sms', 'email', 'backup_codes') NOT NULL,
    secret VARCHAR(255),
    enabled BOOLEAN DEFAULT FALSE,
    verified BOOLEAN DEFAULT FALSE,
    backup_codes JSON,
    phone VARCHAR(20),
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES iser_users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_method (user_id, method),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de intentos MFA
CREATE TABLE IF NOT EXISTS iser_mfa_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    method ENUM('totp', 'sms', 'email', 'backup_codes') NOT NULL,
    success BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES iser_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de dispositivos confiables
CREATE TABLE IF NOT EXISTS iser_trusted_devices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    device_id VARCHAR(64) NOT NULL,
    device_name VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    last_used_at INT UNSIGNED,
    expires_at INT UNSIGNED,
    created_at INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES iser_users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_device (user_id, device_id),
    INDEX idx_user_id (user_id),
    INDEX idx_device_id (device_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
