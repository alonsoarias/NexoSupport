<?php
/**
 * ISER Admin Panel - Database Installation Schema
 *
 * @package    ISER\Modules\Admin
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    6.0.0
 * @since      Phase 6
 */

defined('ISER_BASE_DIR') or die('Direct access not allowed');

use ISER\Core\Database\Database;

/**
 * Install admin panel database tables
 *
 * @param Database $db Database instance
 * @return bool True on success
 */
function install_admin_db(Database $db): bool
{
    $prefix = $db->getPrefix();

    // Config table - System configurations
    $sql_config = "CREATE TABLE IF NOT EXISTS {$prefix}config (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        plugin VARCHAR(100) NOT NULL DEFAULT 'core',
        name VARCHAR(100) NOT NULL,
        value TEXT,
        timecreated BIGINT NOT NULL,
        timemodified BIGINT NOT NULL,
        UNIQUE KEY unique_plugin_name (plugin, name),
        INDEX idx_plugin (plugin),
        INDEX idx_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Config plugins table - Plugin metadata
    $sql_config_plugins = "CREATE TABLE IF NOT EXISTS {$prefix}config_plugins (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        plugin VARCHAR(100) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        version VARCHAR(50) NOT NULL,
        enabled TINYINT DEFAULT 1,
        visible TINYINT DEFAULT 1,
        sortorder INT NOT NULL DEFAULT 0,
        timecreated BIGINT NOT NULL,
        timemodified BIGINT NOT NULL,
        INDEX idx_plugin (plugin),
        INDEX idx_enabled (enabled),
        INDEX idx_visible (visible)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Admin log table - Audit log for admin actions
    $sql_admin_log = "CREATE TABLE IF NOT EXISTS {$prefix}admin_log (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userid BIGINT UNSIGNED NOT NULL,
        action VARCHAR(100) NOT NULL,
        component VARCHAR(100) NOT NULL,
        objectid BIGINT UNSIGNED DEFAULT NULL,
        objecttable VARCHAR(100) DEFAULT NULL,
        data TEXT,
        ip VARCHAR(45),
        timecreated BIGINT NOT NULL,
        INDEX idx_userid (userid),
        INDEX idx_action (action),
        INDEX idx_component (component),
        INDEX idx_timecreated (timecreated),
        FOREIGN KEY (userid) REFERENCES {$prefix}users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Admin tasks table - Scheduled admin tasks
    $sql_admin_tasks = "CREATE TABLE IF NOT EXISTS {$prefix}admin_tasks (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        component VARCHAR(100) NOT NULL,
        classname VARCHAR(255) NOT NULL,
        nextruntime BIGINT NOT NULL,
        lastruntime BIGINT DEFAULT 0,
        faildelay INT DEFAULT 0,
        enabled TINYINT DEFAULT 1,
        INDEX idx_component (component),
        INDEX idx_nextruntime (nextruntime),
        INDEX idx_enabled (enabled)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // System settings cache
    $sql_cache = "CREATE TABLE IF NOT EXISTS {$prefix}cache (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        cachekey VARCHAR(255) NOT NULL UNIQUE,
        value LONGTEXT,
        expires BIGINT NOT NULL,
        timecreated BIGINT NOT NULL,
        INDEX idx_cachekey (cachekey),
        INDEX idx_expires (expires)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    try {
        // Create config table
        $db->execute($sql_config);

        // Create config_plugins table
        $db->execute($sql_config_plugins);

        // Create admin_log table
        $db->execute($sql_admin_log);

        // Create admin_tasks table
        $db->execute($sql_admin_tasks);

        // Create cache table
        $db->execute($sql_cache);

        // Insert default configurations
        install_default_configs($db);

        // Register core plugins
        install_core_plugins($db);

        return true;
    } catch (\Exception $e) {
        error_log('Admin database installation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Install default system configurations
 *
 * @param Database $db Database instance
 * @return bool True on success
 */
function install_default_configs(Database $db): bool
{
    $now = time();
    $configs = [
        // General settings
        ['plugin' => 'core', 'name' => 'sitename', 'value' => 'ISER Authentication System'],
        ['plugin' => 'core', 'name' => 'siteurl', 'value' => ''],
        ['plugin' => 'core', 'name' => 'sitedescription', 'value' => 'Sistema de autenticación seguro'],
        ['plugin' => 'core', 'name' => 'defaultlanguage', 'value' => 'es'],
        ['plugin' => 'core', 'name' => 'timezone', 'value' => 'America/Mexico_City'],

        // Email settings
        ['plugin' => 'core', 'name' => 'smtphosts', 'value' => ''],
        ['plugin' => 'core', 'name' => 'smtpport', 'value' => '587'],
        ['plugin' => 'core', 'name' => 'smtpuser', 'value' => ''],
        ['plugin' => 'core', 'name' => 'smtppass', 'value' => ''],
        ['plugin' => 'core', 'name' => 'smtpsecure', 'value' => 'tls'],
        ['plugin' => 'core', 'name' => 'noreplyaddress', 'value' => 'noreply@localhost'],
        ['plugin' => 'core', 'name' => 'emailfromname', 'value' => 'ISER System'],

        // Auth settings
        ['plugin' => 'auth_manual', 'name' => 'enabled', 'value' => '1'],
        ['plugin' => 'auth_manual', 'name' => 'recaptcha_enabled', 'value' => '0'],
        ['plugin' => 'auth_manual', 'name' => 'recaptcha_sitekey', 'value' => ''],
        ['plugin' => 'auth_manual', 'name' => 'recaptcha_secret', 'value' => ''],

        // MFA settings
        ['plugin' => 'tool_mfa', 'name' => 'enabled', 'value' => '0'],
        ['plugin' => 'tool_mfa', 'name' => 'required_for_admin', 'value' => '0'],
        ['plugin' => 'tool_mfa', 'name' => 'grace_period', 'value' => '7'],
        ['plugin' => 'tool_mfa', 'name' => 'totp_enabled', 'value' => '1'],
        ['plugin' => 'tool_mfa', 'name' => 'email_enabled', 'value' => '1'],
        ['plugin' => 'tool_mfa', 'name' => 'backup_enabled', 'value' => '1'],

        // Site policies
        ['plugin' => 'core', 'name' => 'privacy_policy', 'value' => ''],
        ['plugin' => 'core', 'name' => 'terms_of_service', 'value' => ''],
        ['plugin' => 'core', 'name' => 'minimum_age', 'value' => '13'],

        // Theme settings
        ['plugin' => 'theme_iser', 'name' => 'logo', 'value' => ''],
        ['plugin' => 'theme_iser', 'name' => 'primary_color', 'value' => '#667eea'],
        ['plugin' => 'theme_iser', 'name' => 'secondary_color', 'value' => '#764ba2'],
        ['plugin' => 'theme_iser', 'name' => 'footer_text', 'value' => 'ISER Authentication System'],
    ];

    foreach ($configs as $config) {
        try {
            $db->insert('config', [
                'plugin' => $config['plugin'],
                'name' => $config['name'],
                'value' => $config['value'],
                'timecreated' => $now,
                'timemodified' => $now
            ]);
        } catch (\Exception $e) {
            // Config might already exist, continue
            error_log('Config insertion: ' . $e->getMessage());
        }
    }

    return true;
}

/**
 * Register core plugins
 *
 * @param Database $db Database instance
 * @return bool True on success
 */
function install_core_plugins(Database $db): bool
{
    $now = time();
    $plugins = [
        ['plugin' => 'auth_manual', 'name' => 'Manual Authentication', 'version' => '2.0.0', 'sortorder' => 1],
        ['plugin' => 'user', 'name' => 'User Management', 'version' => '3.0.0', 'sortorder' => 2],
        ['plugin' => 'roles', 'name' => 'Roles and Permissions', 'version' => '4.0.0', 'sortorder' => 3],
        ['plugin' => 'tool_mfa', 'name' => 'Multi-Factor Authentication', 'version' => '5.0.0', 'sortorder' => 4],
        ['plugin' => 'admin', 'name' => 'Administration Panel', 'version' => '6.0.0', 'sortorder' => 5],
    ];

    foreach ($plugins as $plugin) {
        try {
            $db->insert('config_plugins', [
                'plugin' => $plugin['plugin'],
                'name' => $plugin['name'],
                'version' => $plugin['version'],
                'enabled' => 1,
                'visible' => 1,
                'sortorder' => $plugin['sortorder'],
                'timecreated' => $now,
                'timemodified' => $now
            ]);
        } catch (\Exception $e) {
            // Plugin might already exist, continue
            error_log('Plugin registration: ' . $e->getMessage());
        }
    }

    return true;
}

/**
 * Upgrade admin database schema
 *
 * @param Database $db Database instance
 * @param int $oldversion Previous version number
 * @return bool True on success
 */
function upgrade_admin_db(Database $db, int $oldversion): bool
{
    // Future upgrades will be handled here
    return true;
}
