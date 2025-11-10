-- ISER Authentication System - Reports Schema
-- Tablas de reportes

-- Tabla de configuración de reportes
CREATE TABLE IF NOT EXISTS iser_report_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_name VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT,
    config_type ENUM('string', 'int', 'bool', 'json') DEFAULT 'string',
    description VARCHAR(255),
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    INDEX idx_config_name (config_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración por defecto de reportes
INSERT INTO iser_report_config (config_name, config_value, config_type, description, created_at, updated_at) VALUES
('report.retention_days', '90', 'int', 'Días de retención de logs', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('report.max_export_rows', '10000', 'int', 'Máximo de filas para exportación', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('report.default_format', 'pdf', 'string', 'Formato de reporte por defecto', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Tabla de reportes generados
CREATE TABLE IF NOT EXISTS iser_generated_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    report_type VARCHAR(50) NOT NULL,
    report_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255),
    file_format VARCHAR(20),
    file_size INT UNSIGNED,
    parameters JSON,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT,
    generated_at INT UNSIGNED,
    expires_at INT UNSIGNED,
    created_at INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES iser_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_report_type (report_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de métricas del sistema
CREATE TABLE IF NOT EXISTS iser_system_metrics (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(15, 4),
    metric_type ENUM('counter', 'gauge', 'histogram') DEFAULT 'gauge',
    tags JSON,
    recorded_at INT UNSIGNED NOT NULL,
    INDEX idx_metric_name (metric_name),
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
