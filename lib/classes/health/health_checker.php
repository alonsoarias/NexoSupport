<?php
/**
 * NexoSupport - Health Checker
 *
 * @package    core
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace ISER\Core\Health;

defined('NEXOSUPPORT_INTERNAL') || die();

use ISER\Core\Database\Database;

/**
 * Health Checker
 *
 * System health monitoring and diagnostics
 */
class HealthChecker
{
    /**
     * Run all health checks
     *
     * @return array Results
     */
    public static function run_all_checks(): array
    {
        return [
            'database' => self::check_database(),
            'filesystem' => self::check_file_permissions(),
            'php' => self::check_php_extensions(),
            'disk' => self::check_disk_space(),
            'cache' => self::check_cache(),
            'themes' => self::check_themes(),
            'overall' => self::get_overall_status(),
        ];
    }

    /**
     * Check database connectivity and health
     *
     * @return array Status
     */
    public static function check_database(): array
    {
        try {
            $db = Database::getInstance();
            $pdo = $db->getConnection();

            // Test connectivity
            $pdo->query('SELECT 1');

            // Check tables exist
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            return [
                'status' => 'ok',
                'message' => 'Database connected',
                'tables_count' => count($tables),
                'icon' => '✓',
                'color' => 'green',
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'icon' => '✗',
                'color' => 'red',
            ];
        }
    }

    /**
     * Check file permissions
     *
     * @return array Status
     */
    public static function check_file_permissions(): array
    {
        $dirs_to_check = [
            __DIR__ . '/../../../cache' => 'Cache directory',
            __DIR__ . '/../../../logs' => 'Logs directory',
            __DIR__ . '/../../../uploads' => 'Uploads directory',
        ];

        $errors = [];
        $warnings = [];

        foreach ($dirs_to_check as $dir => $name) {
            if (!is_dir($dir)) {
                $warnings[] = "$name does not exist";
                continue;
            }

            if (!is_writable($dir)) {
                $errors[] = "$name is not writable";
            }
        }

        if (!empty($errors)) {
            return [
                'status' => 'error',
                'message' => implode(', ', $errors),
                'icon' => '✗',
                'color' => 'red',
            ];
        }

        if (!empty($warnings)) {
            return [
                'status' => 'warning',
                'message' => implode(', ', $warnings),
                'icon' => '⚠',
                'color' => 'yellow',
            ];
        }

        return [
            'status' => 'ok',
            'message' => 'All directories writable',
            'icon' => '✓',
            'color' => 'green',
        ];
    }

    /**
     * Check PHP extensions
     *
     * @return array Status
     */
    public static function check_php_extensions(): array
    {
        $required = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl', 'zip'];
        $missing = [];

        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        if (!empty($missing)) {
            return [
                'status' => 'error',
                'message' => 'Missing extensions: ' . implode(', ', $missing),
                'icon' => '✗',
                'color' => 'red',
            ];
        }

        return [
            'status' => 'ok',
            'message' => 'All required extensions loaded',
            'count' => count($required),
            'icon' => '✓',
            'color' => 'green',
        ];
    }

    /**
     * Check disk space
     *
     * @return array Status
     */
    public static function check_disk_space(): array
    {
        $free = disk_free_space(__DIR__);
        $total = disk_total_space(__DIR__);
        $used_percent = (($total - $free) / $total) * 100;

        $free_gb = round($free / 1024 / 1024 / 1024, 2);
        $total_gb = round($total / 1024 / 1024 / 1024, 2);

        if ($used_percent > 90) {
            $status = 'error';
            $icon = '✗';
            $color = 'red';
        } elseif ($used_percent > 75) {
            $status = 'warning';
            $icon = '⚠';
            $color = 'yellow';
        } else {
            $status = 'ok';
            $icon = '✓';
            $color = 'green';
        }

        return [
            'status' => $status,
            'message' => "{$free_gb}GB free of {$total_gb}GB",
            'used_percent' => round($used_percent, 1),
            'icon' => $icon,
            'color' => $color,
        ];
    }

    /**
     * Check cache status
     *
     * @return array Status
     */
    public static function check_cache(): array
    {
        try {
            require_once __DIR__ . '/../cache/cache_manager.php';
            $stats = \ISER\Core\Cache\CacheManager::get_stats();

            return [
                'status' => 'ok',
                'message' => "Cache operational ({$stats['file_cache_items']} items)",
                'stats' => $stats,
                'icon' => '✓',
                'color' => 'green',
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Cache not available',
                'icon' => '⚠',
                'color' => 'yellow',
            ];
        }
    }

    /**
     * Check themes
     *
     * @return array Status
     */
    public static function check_themes(): array
    {
        try {
            require_once __DIR__ . '/../theme/theme_manager.php';
            $themes = \ISER\Core\Theme\ThemeManager::get_available_themes();
            $active = \ISER\Core\Theme\ThemeManager::get_active_theme();

            return [
                'status' => 'ok',
                'message' => count($themes) . " themes available, active: $active",
                'count' => count($themes),
                'active' => $active,
                'icon' => '✓',
                'color' => 'green',
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Theme system not available',
                'icon' => '⚠',
                'color' => 'yellow',
            ];
        }
    }

    /**
     * Get overall system status
     *
     * @return array Status
     */
    public static function get_overall_status(): array
    {
        $checks = [
            self::check_database(),
            self::check_file_permissions(),
            self::check_php_extensions(),
            self::check_disk_space(),
        ];

        $has_error = false;
        $has_warning = false;

        foreach ($checks as $check) {
            if ($check['status'] === 'error') {
                $has_error = true;
            } elseif ($check['status'] === 'warning') {
                $has_warning = true;
            }
        }

        if ($has_error) {
            return [
                'status' => 'error',
                'message' => 'System has errors',
                'icon' => '✗',
                'color' => 'red',
            ];
        }

        if ($has_warning) {
            return [
                'status' => 'warning',
                'message' => 'System operational with warnings',
                'icon' => '⚠',
                'color' => 'yellow',
            ];
        }

        return [
            'status' => 'ok',
            'message' => 'All systems operational',
            'icon' => '✓',
            'color' => 'green',
        ];
    }

    /**
     * Get system info
     *
     * @return array System information
     */
    public static function get_system_info(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'operating_system' => PHP_OS,
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
    }
}
