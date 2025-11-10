-- ISER Authentication System - Core Schema
-- Tabla de configuración del sistema

CREATE TABLE IF NOT EXISTS iser_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    config_type ENUM('string', 'int', 'bool', 'json') DEFAULT 'string',
    description VARCHAR(255),
    is_public BOOLEAN DEFAULT FALSE,
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuraciones por defecto
INSERT INTO iser_config (config_key, config_value, config_type, description, is_public, created_at, updated_at) VALUES
('app.name', 'ISER Authentication System', 'string', 'Nombre de la aplicación', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('app.version', '1.0.0', 'string', 'Versión de la aplicación', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('security.password_min_length', '8', 'int', 'Longitud mínima de contraseña', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('security.max_login_attempts', '5', 'int', 'Intentos máximos de login', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('security.lockout_duration', '900', 'int', 'Duración de bloqueo en segundos', 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
