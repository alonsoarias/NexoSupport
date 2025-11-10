-- ISER Authentication System - Logs Schema
-- Tablas de logs y auditoría

-- Tabla de logs de sistema
CREATE TABLE IF NOT EXISTS iser_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(20) NOT NULL,
    channel VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    user_id INT UNSIGNED,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at INT UNSIGNED NOT NULL,
    INDEX idx_level (level),
    INDEX idx_channel (channel),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES iser_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de auditoría
CREATE TABLE IF NOT EXISTS iser_audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100),
    entity_id INT UNSIGNED,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at INT UNSIGNED NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES iser_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de logs de autenticación
CREATE TABLE IF NOT EXISTS iser_auth_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    username VARCHAR(50),
    event VARCHAR(50) NOT NULL,
    success BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    metadata JSON,
    created_at INT UNSIGNED NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_event (event),
    INDEX idx_success (success),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES iser_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
