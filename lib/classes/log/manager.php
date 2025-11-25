<?php
namespace core\log;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Log Manager
 *
 * Manages logging across the system, including:
 * - Event logging to logstore_standard_log
 * - Legacy log support
 * - Log reader access
 *
 * Similar to Moodle's log manager system.
 *
 * @package    core\log
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class manager {

    /** @var manager|null Singleton instance */
    private static ?manager $instance = null;

    /** @var array Registered log stores */
    private array $stores = [];

    /** @var bool If logging is enabled */
    private bool $enabled = true;

    /**
     * Get the singleton instance
     *
     * @return manager
     */
    public static function instance(): manager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor for singleton
     */
    private function __construct() {
        $this->init_stores();
    }

    /**
     * Initialize available log stores
     *
     * @return void
     */
    private function init_stores(): void {
        // Standard log store (database)
        $this->stores['logstore_standard'] = new stores\store_standard();
    }

    /**
     * Get available log stores
     *
     * @return array Store instances
     */
    public function get_stores(): array {
        return $this->stores;
    }

    /**
     * Get a specific log store
     *
     * @param string $name Store name
     * @return store_interface|null
     */
    public function get_store(string $name): ?store_interface {
        return $this->stores[$name] ?? null;
    }

    /**
     * Get readers that support a specific interface
     *
     * @param string $interface Reader interface
     * @return array Reader instances
     */
    public function get_readers_by_capability(string $interface): array {
        $readers = [];

        foreach ($this->stores as $name => $store) {
            if ($store instanceof $interface) {
                $readers[$name] = $store;
            }
        }

        return $readers;
    }

    /**
     * Get all log readers
     *
     * @return array Reader instances
     */
    public function get_readers(): array {
        return $this->get_readers_by_capability(sql_reader::class);
    }

    /**
     * Dispose of the manager and its stores
     *
     * @return void
     */
    public function dispose(): void {
        foreach ($this->stores as $store) {
            if (method_exists($store, 'dispose')) {
                $store->dispose();
            }
        }
        $this->stores = [];
        self::$instance = null;
    }

    /**
     * Enable or disable logging
     *
     * @param bool $enabled
     * @return void
     */
    public function set_enabled(bool $enabled): void {
        $this->enabled = $enabled;
    }

    /**
     * Check if logging is enabled
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return $this->enabled;
    }

    /**
     * Write a log entry directly (legacy support)
     *
     * @param string $action Action name
     * @param array $data Additional data
     * @return bool Success
     */
    public function write_log(string $action, array $data = []): bool {
        if (!$this->enabled) {
            return false;
        }

        global $USER;

        $record = array_merge([
            'eventname' => '\\core\\event\\' . $action,
            'component' => $data['component'] ?? 'core',
            'action' => $action,
            'target' => $data['target'] ?? 'system',
            'objecttable' => $data['objecttable'] ?? null,
            'objectid' => $data['objectid'] ?? null,
            'crud' => $data['crud'] ?? 'r',
            'edulevel' => $data['edulevel'] ?? 0,
            'contextid' => $data['contextid'] ?? 1,
            'contextlevel' => $data['contextlevel'] ?? CONTEXT_SYSTEM,
            'contextinstanceid' => $data['contextinstanceid'] ?? 0,
            'userid' => $data['userid'] ?? ($USER->id ?? 0),
            'relateduserid' => $data['relateduserid'] ?? null,
            'anonymous' => $data['anonymous'] ?? 0,
            'other' => $data['other'] ?? null,
            'timecreated' => time(),
            'origin' => $data['origin'] ?? 'web',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'realuserid' => $data['realuserid'] ?? null,
        ], $data);

        // Write to all enabled stores
        foreach ($this->stores as $store) {
            if ($store instanceof store_interface) {
                $store->write($record);
            }
        }

        return true;
    }
}
