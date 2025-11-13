<?php
/**
 * Instalación del esquema de base de datos para reportes y auditoría
 * @package report_log
 * @author ISER Desarrollo
 * @license Propietario
 */

use ISER\Core\Database\Database;

function install_report_log_db(Database $db): bool
{
    try {
        // Tabla principal de logs del sistema
        $db->execute("
            CREATE TABLE IF NOT EXISTS iser_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                eventname VARCHAR(255) NOT NULL COMMENT 'Nombre del evento',
                component VARCHAR(100) NOT NULL COMMENT 'Componente que generó el log',
                action VARCHAR(100) NOT NULL COMMENT 'Acción realizada',
                target VARCHAR(100) DEFAULT NULL COMMENT 'Objetivo de la acción',
                objecttable VARCHAR(50) DEFAULT NULL COMMENT 'Tabla del objeto afectado',
                objectid BIGINT UNSIGNED DEFAULT NULL COMMENT 'ID del objeto afectado',
                crud CHAR(1) NOT NULL COMMENT 'Operación CRUD: c=crear, r=leer, u=actualizar, d=eliminar',
                userid BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ID del usuario que realizó la acción',
                relateduserid BIGINT UNSIGNED DEFAULT NULL COMMENT 'ID del usuario relacionado',
                ip_address VARCHAR(45) NOT NULL COMMENT 'Dirección IP del usuario',
                user_agent TEXT DEFAULT NULL COMMENT 'User agent del navegador',
                context TEXT DEFAULT NULL COMMENT 'Contexto adicional en JSON',
                description TEXT DEFAULT NULL COMMENT 'Descripción del evento',
                severity TINYINT NOT NULL DEFAULT 0 COMMENT '0=info, 1=warning, 2=error, 3=critical',
                timecreated BIGINT UNSIGNED NOT NULL COMMENT 'Timestamp de creación',

                INDEX idx_userid (userid),
                INDEX idx_timecreated (timecreated),
                INDEX idx_component (component),
                INDEX idx_eventname (eventname),
                INDEX idx_severity (severity),
                INDEX idx_ip_address (ip_address),
                INDEX idx_objecttable_objectid (objecttable, objectid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Registro completo de eventos del sistema'
        ");

        // Tabla de estadísticas diarias de logs
        $db->execute("
            CREATE TABLE IF NOT EXISTS iser_logs_daily (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                stat_type VARCHAR(50) NOT NULL COMMENT 'Tipo de estadística',
                stat_value INT NOT NULL DEFAULT 0 COMMENT 'Valor de la estadística',
                stat_date DATE NOT NULL COMMENT 'Fecha de la estadística',
                component VARCHAR(100) DEFAULT NULL COMMENT 'Componente relacionado',
                metadata JSON DEFAULT NULL COMMENT 'Metadata adicional',
                timecreated BIGINT UNSIGNED NOT NULL COMMENT 'Timestamp de creación',

                UNIQUE KEY unique_stat (stat_type, stat_date, component),
                INDEX idx_stat_date (stat_date),
                INDEX idx_stat_type (stat_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Estadísticas agregadas diarias de logs'
        ");

        // Tabla de alertas de seguridad
        $db->execute("
            CREATE TABLE IF NOT EXISTS iser_security_alerts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                alert_type VARCHAR(50) NOT NULL COMMENT 'Tipo de alerta',
                severity TINYINT NOT NULL DEFAULT 0 COMMENT '0=info, 1=low, 2=medium, 3=high, 4=critical',
                title VARCHAR(255) NOT NULL COMMENT 'Título de la alerta',
                description TEXT NOT NULL COMMENT 'Descripción detallada',
                details JSON DEFAULT NULL COMMENT 'Detalles en JSON',
                userid BIGINT UNSIGNED DEFAULT NULL COMMENT 'Usuario relacionado',
                ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP relacionada',
                source_component VARCHAR(100) DEFAULT NULL COMMENT 'Componente que generó la alerta',
                status ENUM('new', 'investigating', 'resolved', 'false_positive') NOT NULL DEFAULT 'new',
                resolved_by BIGINT UNSIGNED DEFAULT NULL COMMENT 'Usuario que resolvió',
                resolution_notes TEXT DEFAULT NULL COMMENT 'Notas de resolución',
                timecreated BIGINT UNSIGNED NOT NULL COMMENT 'Timestamp de creación',
                timemodified BIGINT UNSIGNED NOT NULL COMMENT 'Timestamp de modificación',

                INDEX idx_alert_type (alert_type),
                INDEX idx_severity (severity),
                INDEX idx_status (status),
                INDEX idx_timecreated (timecreated),
                INDEX idx_userid (userid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Alertas de seguridad del sistema'
        ");

        // Tabla de intentos de login fallidos (para análisis de seguridad)
        $db->execute("
            CREATE TABLE IF NOT EXISTS iser_login_attempts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL COMMENT 'Username intentado',
                ip_address VARCHAR(45) NOT NULL COMMENT 'IP del intento',
                user_agent TEXT DEFAULT NULL COMMENT 'User agent',
                success TINYINT NOT NULL DEFAULT 0 COMMENT '0=fallido, 1=exitoso',
                failure_reason VARCHAR(255) DEFAULT NULL COMMENT 'Razón del fallo',
                userid BIGINT UNSIGNED DEFAULT NULL COMMENT 'ID de usuario si se identificó',
                timecreated BIGINT UNSIGNED NOT NULL COMMENT 'Timestamp del intento',

                INDEX idx_ip_address (ip_address),
                INDEX idx_username (username),
                INDEX idx_success (success),
                INDEX idx_timecreated (timecreated),
                INDEX idx_ip_time (ip_address, timecreated)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Intentos de login para análisis de seguridad'
        ");

        // Tabla de sesiones activas (para auditoría)
        $db->execute("
            CREATE TABLE IF NOT EXISTS iser_user_sessions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                userid BIGINT UNSIGNED NOT NULL COMMENT 'ID del usuario',
                session_id VARCHAR(128) NOT NULL COMMENT 'ID de la sesión PHP',
                ip_address VARCHAR(45) NOT NULL COMMENT 'IP de la sesión',
                user_agent TEXT DEFAULT NULL COMMENT 'User agent',
                last_activity BIGINT UNSIGNED NOT NULL COMMENT 'Última actividad',
                timecreated BIGINT UNSIGNED NOT NULL COMMENT 'Timestamp de creación',

                UNIQUE KEY unique_session (session_id),
                INDEX idx_userid (userid),
                INDEX idx_last_activity (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Sesiones activas de usuarios'
        ");

        // Tabla de auditoría de accesos a datos sensibles
        $db->execute("
            CREATE TABLE IF NOT EXISTS iser_audit_trail (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                userid BIGINT UNSIGNED NOT NULL COMMENT 'Usuario que accedió',
                action VARCHAR(50) NOT NULL COMMENT 'Acción realizada',
                resource_type VARCHAR(50) NOT NULL COMMENT 'Tipo de recurso',
                resource_id BIGINT UNSIGNED NOT NULL COMMENT 'ID del recurso',
                old_value TEXT DEFAULT NULL COMMENT 'Valor anterior (para updates)',
                new_value TEXT DEFAULT NULL COMMENT 'Valor nuevo (para updates)',
                ip_address VARCHAR(45) NOT NULL COMMENT 'IP del acceso',
                timecreated BIGINT UNSIGNED NOT NULL COMMENT 'Timestamp',

                INDEX idx_userid (userid),
                INDEX idx_resource (resource_type, resource_id),
                INDEX idx_action (action),
                INDEX idx_timecreated (timecreated)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Auditoría de accesos a datos sensibles'
        ");

        // Tabla de configuraciones del sistema de reportes
        $db->execute("
            CREATE TABLE IF NOT EXISTS iser_report_config (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                config_name VARCHAR(100) NOT NULL COMMENT 'Nombre de la configuración',
                config_value TEXT NOT NULL COMMENT 'Valor de la configuración',
                config_type ENUM('string', 'int', 'bool', 'json') NOT NULL DEFAULT 'string',
                description TEXT DEFAULT NULL COMMENT 'Descripción',
                timemodified BIGINT UNSIGNED NOT NULL COMMENT 'Timestamp de modificación',

                UNIQUE KEY unique_config (config_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Configuraciones del sistema de reportes'
        ");

        // Insertar configuraciones por defecto
        $now = time();
        $db->execute("
            INSERT INTO iser_report_config (config_name, config_value, config_type, description, timemodified) VALUES
            ('log_retention_days', '90', 'int', 'Días de retención de logs', {$now}),
            ('enable_auto_cleanup', '1', 'bool', 'Habilitar limpieza automática de logs antiguos', {$now}),
            ('security_alert_email', '', 'string', 'Email para alertas de seguridad', {$now}),
            ('failed_login_threshold', '5', 'int', 'Intentos fallidos antes de alerta', {$now}),
            ('suspicious_activity_threshold', '10', 'int', 'Acciones sospechosas antes de alerta', {$now}),
            ('enable_audit_trail', '1', 'bool', 'Habilitar auditoría detallada', {$now}),
            ('export_batch_size', '1000', 'int', 'Registros por lote en exportación', {$now})
            ON DUPLICATE KEY UPDATE config_value=VALUES(config_value), timemodified=VALUES(timemodified)
        ");

        return true;
    } catch (\Exception $e) {
        error_log("Error instalando módulo report_log: " . $e->getMessage());
        return false;
    }
}

/**
 * Desinstalación del esquema
 */
function uninstall_report_log_db(Database $db): bool
{
    try {
        $tables = [
            'iser_audit_trail',
            'iser_user_sessions',
            'iser_login_attempts',
            'iser_security_alerts',
            'iser_logs_daily',
            'iser_logs',
            'iser_report_config'
        ];

        foreach ($tables as $table) {
            $db->execute("DROP TABLE IF EXISTS {$table}");
        }

        return true;
    } catch (\Exception $e) {
        error_log("Error desinstalando módulo report_log: " . $e->getMessage());
        return false;
    }
}
