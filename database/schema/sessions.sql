-- ISER Authentication System - Sessions Schema
-- Tablas de sesiones

-- Tabla de sesiones
CREATE TABLE IF NOT EXISTS iser_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT UNSIGNED,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    payload TEXT,
    last_activity INT UNSIGNED NOT NULL,
    created_at INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES iser_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de tokens JWT
CREATE TABLE IF NOT EXISTS iser_jwt_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_id VARCHAR(64) NOT NULL UNIQUE,
    token_hash VARCHAR(64) NOT NULL,
    type ENUM('access', 'refresh') DEFAULT 'access',
    expires_at INT UNSIGNED NOT NULL,
    revoked BOOLEAN DEFAULT FALSE,
    revoked_at INT UNSIGNED,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES iser_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token_id (token_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_revoked (revoked)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de refresh tokens
CREATE TABLE IF NOT EXISTS iser_refresh_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at INT UNSIGNED NOT NULL,
    revoked BOOLEAN DEFAULT FALSE,
    created_at INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES iser_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
