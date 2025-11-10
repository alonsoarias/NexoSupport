-- ISER Authentication System - Auth Schema
-- Tablas de usuarios y autenticación

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS iser_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(64),
    email_verification_expires INT UNSIGNED,
    password_reset_token VARCHAR(64),
    password_reset_expires INT UNSIGNED,
    last_login_at INT UNSIGNED,
    last_login_ip VARCHAR(45),
    failed_login_attempts INT UNSIGNED DEFAULT 0,
    locked_until INT UNSIGNED,
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    deleted_at INT UNSIGNED,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_email_verification_token (email_verification_token),
    INDEX idx_password_reset_token (password_reset_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de perfiles de usuario
CREATE TABLE IF NOT EXISTS iser_user_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    phone VARCHAR(20),
    mobile VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(100),
    postal_code VARCHAR(20),
    timezone VARCHAR(50) DEFAULT 'America/Mexico_City',
    locale VARCHAR(10) DEFAULT 'es',
    avatar_url VARCHAR(255),
    bio TEXT,
    metadata JSON,
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES iser_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de historial de contraseñas
CREATE TABLE IF NOT EXISTS iser_password_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES iser_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
