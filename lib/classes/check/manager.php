<?php
/**
 * Manager for system checks.
 *
 * @package    core
 * @subpackage check
 * @copyright  2025 NexoSupport
 * @license    Proprietary - NexoSupport
 */

namespace core\check;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Manages and retrieves system checks.
 *
 * This class provides methods to discover and retrieve checks of different types
 * (security, performance, status) from the system.
 */
class manager {

    /** @var array Valid check types */
    const TYPES = ['status', 'security', 'performance'];

    /** @var array Cached checks by type */
    protected static array $cache = [];

    /**
     * Get all checks of a specific type.
     *
     * @param string $type The type of checks (security, performance, status)
     * @return array Array of check objects
     */
    public static function get_checks(string $type): array {
        if (!in_array($type, self::TYPES)) {
            throw new \invalid_parameter_exception("Invalid check type: $type");
        }

        if (isset(self::$cache[$type])) {
            return self::$cache[$type];
        }

        $checks = [];

        // Get core checks for this type
        switch ($type) {
            case 'security':
                $checks = self::get_security_checks();
                break;
            case 'performance':
                $checks = self::get_performance_checks();
                break;
            case 'status':
                $checks = self::get_status_checks();
                break;
        }

        self::$cache[$type] = $checks;
        return $checks;
    }

    /**
     * Get all security checks.
     *
     * @return array Array of security check objects
     */
    public static function get_security_checks(): array {
        global $CFG;

        $checks = [];

        // Environment checks
        $envdir = $CFG->libdir . '/classes/check/environment';
        if (is_dir($envdir)) {
            $checks = array_merge($checks, self::load_checks_from_directory($envdir, 'core\\check\\environment'));
        }

        // Security checks
        $secdir = $CFG->libdir . '/classes/check/security';
        if (is_dir($secdir)) {
            $checks = array_merge($checks, self::load_checks_from_directory($secdir, 'core\\check\\security'));
        }

        // Access checks
        $accdir = $CFG->libdir . '/classes/check/access';
        if (is_dir($accdir)) {
            $checks = array_merge($checks, self::load_checks_from_directory($accdir, 'core\\check\\access'));
        }

        // HTTP checks
        $httpdir = $CFG->libdir . '/classes/check/http';
        if (is_dir($httpdir)) {
            $checks = array_merge($checks, self::load_checks_from_directory($httpdir, 'core\\check\\http'));
        }

        return $checks;
    }

    /**
     * Get all performance checks.
     *
     * @return array Array of performance check objects
     */
    public static function get_performance_checks(): array {
        global $CFG;

        $checks = [];

        $perfdir = $CFG->libdir . '/classes/check/performance';
        if (is_dir($perfdir)) {
            $checks = self::load_checks_from_directory($perfdir, 'core\\check\\performance');
        }

        return $checks;
    }

    /**
     * Get all status checks.
     *
     * @return array Array of status check objects
     */
    public static function get_status_checks(): array {
        // Status checks can be added here in the future
        return [];
    }

    /**
     * Load check classes from a directory.
     *
     * @param string $directory The directory to scan
     * @param string $namespace The namespace for the classes
     * @return array Array of check objects
     */
    protected static function load_checks_from_directory(string $directory, string $namespace): array {
        $checks = [];

        if (!is_dir($directory)) {
            return $checks;
        }

        $files = scandir($directory);
        foreach ($files as $file) {
            if (substr($file, -4) !== '.php') {
                continue;
            }

            $classname = substr($file, 0, -4);
            $fullclass = $namespace . '\\' . $classname;

            // Skip if the file is the base class
            if ($classname === 'check') {
                continue;
            }

            // Require the file
            require_once($directory . '/' . $file);

            if (class_exists($fullclass)) {
                $reflection = new \ReflectionClass($fullclass);
                if (!$reflection->isAbstract() && $reflection->isSubclassOf('core\\check\\check')) {
                    $checks[] = new $fullclass();
                }
            }
        }

        return $checks;
    }

    /**
     * Clear the check cache.
     */
    public static function clear_cache(): void {
        self::$cache = [];
    }

    /**
     * Get a specific check by type and ID.
     *
     * @param string $type The check type
     * @param string $id The check ID
     * @return check|null The check or null if not found
     */
    public static function get_check(string $type, string $id): ?check {
        $checks = self::get_checks($type);

        foreach ($checks as $check) {
            if ($check->get_id() === $id) {
                return $check;
            }
        }

        return null;
    }
}
