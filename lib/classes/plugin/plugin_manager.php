<?php
namespace core\plugin;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Plugin Manager
 *
 * Manages plugin discovery, installation, upgrade, and status tracking.
 * Similar to Moodle's core_plugin_manager class.
 *
 * @package    core\plugin
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */
class plugin_manager {

    /** @var plugin_manager Singleton instance */
    private static $instance = null;

    /** @var array Cached list of installed plugins from database */
    private $installedplugins = null;

    /** @var array Cached list of present plugins from filesystem */
    private $presentplugins = null;

    /** @var array Plugin types and their locations */
    private $plugintypes = null;

    // Plugin status constants
    const STATUS_NODB = 'nodb';           // No info in database
    const STATUS_UPTODATE = 'uptodate';   // Up to date
    const STATUS_NEW = 'new';             // New, ready to install
    const STATUS_UPGRADE = 'upgrade';     // Ready to upgrade
    const STATUS_DELETE = 'delete';       // Ready to delete
    const STATUS_DOWNGRADE = 'downgrade'; // Disk version < DB version
    const STATUS_MISSING = 'missing';     // In DB but not on disk

    /**
     * Get the singleton instance
     *
     * @return plugin_manager
     */
    public static function instance(): plugin_manager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Reset all caches
     *
     * @param bool $phpunitreset PHPUnit reset mode
     * @return void
     */
    public static function reset_caches(bool $phpunitreset = false): void {
        if (self::$instance !== null) {
            self::$instance->installedplugins = null;
            self::$instance->presentplugins = null;
            self::$instance->plugintypes = null;
        }
        if ($phpunitreset) {
            self::$instance = null;
        }
    }

    /**
     * Constructor (private for singleton)
     */
    private function __construct() {
        // Initialize on first use
    }

    /**
     * Get plugin types and their directories
     *
     * @return array ['type' => 'path', ...]
     */
    public function get_plugin_types(): array {
        global $CFG;

        if ($this->plugintypes !== null) {
            return $this->plugintypes;
        }

        $this->plugintypes = [
            'auth' => $CFG->dirroot . '/auth',
            'block' => $CFG->dirroot . '/blocks',
            'local' => $CFG->dirroot . '/local',
            'theme' => $CFG->dirroot . '/theme',
            'tool' => $CFG->dirroot . '/admin/tool',
            'report' => $CFG->dirroot . '/report',
        ];

        return $this->plugintypes;
    }

    /**
     * Get all plugins of a specific type
     *
     * @param string $type Plugin type (auth, block, etc.)
     * @return array ['pluginname' => 'path', ...]
     */
    public function get_plugin_list(string $type): array {
        $types = $this->get_plugin_types();

        if (!isset($types[$type])) {
            return [];
        }

        $plugindir = $types[$type];
        if (!is_dir($plugindir)) {
            return [];
        }

        $plugins = [];
        $items = scandir($plugindir);

        foreach ($items as $item) {
            if ($item[0] === '.') {
                continue;
            }

            $fullpath = $plugindir . '/' . $item;
            if (!is_dir($fullpath)) {
                continue;
            }

            // Check if it has a version.php file
            if (file_exists($fullpath . '/version.php')) {
                $plugins[$item] = $fullpath;
            }
        }

        return $plugins;
    }

    /**
     * Get all installed plugins from database
     *
     * @return array ['type' => ['plugin' => version, ...], ...]
     */
    public function get_installed_plugins(): array {
        global $DB;

        if ($this->installedplugins !== null) {
            return $this->installedplugins;
        }

        $this->installedplugins = [];

        // Get all plugin versions from config_plugins table or config table
        try {
            // First try config_plugins table
            if ($DB->get_manager()->table_exists('config_plugins')) {
                $records = $DB->get_records('config_plugins', ['name' => 'version']);
                foreach ($records as $record) {
                    // Parse component name (e.g., auth_manual -> auth, manual)
                    $parts = explode('_', $record->plugin, 2);
                    if (count($parts) === 2) {
                        $type = $parts[0];
                        $name = $parts[1];
                        if (!isset($this->installedplugins[$type])) {
                            $this->installedplugins[$type] = [];
                        }
                        $this->installedplugins[$type][$name] = (int)$record->value;
                    }
                }
            }

            // Also check config table for component versions
            $records = $DB->get_records_sql(
                "SELECT * FROM {config} WHERE component != 'core' AND name = 'version'"
            );
            foreach ($records as $record) {
                $parts = explode('_', $record->component, 2);
                if (count($parts) === 2) {
                    $type = $parts[0];
                    $name = $parts[1];
                    if (!isset($this->installedplugins[$type])) {
                        $this->installedplugins[$type] = [];
                    }
                    $this->installedplugins[$type][$name] = (int)$record->value;
                }
            }
        } catch (\Exception $e) {
            debugging('Error loading installed plugins: ' . $e->getMessage());
        }

        return $this->installedplugins;
    }

    /**
     * Get all present plugins from filesystem
     *
     * @return array ['type' => ['plugin' => info, ...], ...]
     */
    public function get_present_plugins(): array {
        if ($this->presentplugins !== null) {
            return $this->presentplugins;
        }

        $this->presentplugins = [];
        $types = $this->get_plugin_types();

        foreach ($types as $type => $typedir) {
            if (!is_dir($typedir)) {
                continue;
            }

            $plugins = $this->get_plugin_list($type);
            foreach ($plugins as $pluginname => $plugindir) {
                $info = $this->load_plugin_info($type, $pluginname, $plugindir);
                if ($info) {
                    if (!isset($this->presentplugins[$type])) {
                        $this->presentplugins[$type] = [];
                    }
                    $this->presentplugins[$type][$pluginname] = $info;
                }
            }
        }

        return $this->presentplugins;
    }

    /**
     * Load plugin information from version.php
     *
     * @param string $type Plugin type
     * @param string $name Plugin name
     * @param string $dir Plugin directory
     * @return object|null Plugin info or null
     */
    private function load_plugin_info(string $type, string $name, string $dir): ?object {
        $versionfile = $dir . '/version.php';

        if (!file_exists($versionfile)) {
            return null;
        }

        $plugin = new \stdClass();
        try {
            include($versionfile);
        } catch (\Exception $e) {
            debugging("Error loading version.php for {$type}_{$name}: " . $e->getMessage());
            return null;
        }

        // Set component name if not set
        if (empty($plugin->component)) {
            $plugin->component = $type . '_' . $name;
        }

        // Set defaults
        $plugin->type = $type;
        $plugin->name = $name;
        $plugin->rootdir = $dir;
        $plugin->version = $plugin->version ?? 0;
        $plugin->requires = $plugin->requires ?? 0;
        $plugin->maturity = $plugin->maturity ?? MATURITY_STABLE;
        $plugin->release = $plugin->release ?? '';
        $plugin->dependencies = $plugin->dependencies ?? [];

        return $plugin;
    }

    /**
     * Get plugin status
     *
     * @param string $type Plugin type
     * @param string $name Plugin name
     * @return string Status constant
     */
    public function get_plugin_status(string $type, string $name): string {
        $installed = $this->get_installed_plugins();
        $present = $this->get_present_plugins();

        $installedversion = $installed[$type][$name] ?? null;
        $presentinfo = $present[$type][$name] ?? null;

        // Plugin not on disk
        if ($presentinfo === null) {
            if ($installedversion !== null) {
                return self::STATUS_MISSING;
            }
            return self::STATUS_NODB;
        }

        // Plugin not in database
        if ($installedversion === null) {
            return self::STATUS_NEW;
        }

        // Compare versions
        $diskversion = $presentinfo->version;

        if ($diskversion > $installedversion) {
            return self::STATUS_UPGRADE;
        } elseif ($diskversion < $installedversion) {
            return self::STATUS_DOWNGRADE;
        }

        return self::STATUS_UPTODATE;
    }

    /**
     * Get plugin information
     *
     * @param string $component Component name (e.g., 'auth_manual')
     * @return object|null Plugin info or null
     */
    public function get_plugin_info(string $component): ?object {
        $parts = explode('_', $component, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $type = $parts[0];
        $name = $parts[1];

        $present = $this->get_present_plugins();
        return $present[$type][$name] ?? null;
    }

    /**
     * Check if all plugins are OK (no upgrades needed)
     *
     * @param int $version Core version
     * @param array &$failed Failed plugins (output)
     * @return bool True if all OK
     */
    public function all_plugins_ok(int $version, array &$failed = []): bool {
        $failed = [];
        $present = $this->get_present_plugins();

        foreach ($present as $type => $plugins) {
            foreach ($plugins as $name => $info) {
                $status = $this->get_plugin_status($type, $name);

                if ($status === self::STATUS_NEW || $status === self::STATUS_UPGRADE) {
                    $failed[] = [
                        'component' => $info->component,
                        'status' => $status,
                        'current' => $this->get_installed_version($type, $name),
                        'required' => $info->version
                    ];
                } elseif ($status === self::STATUS_DOWNGRADE) {
                    $failed[] = [
                        'component' => $info->component,
                        'status' => $status,
                        'current' => $this->get_installed_version($type, $name),
                        'required' => $info->version,
                        'error' => 'Downgrade not allowed'
                    ];
                }

                // Check core version requirement
                if ($info->requires > $version) {
                    $failed[] = [
                        'component' => $info->component,
                        'status' => 'requiresupgrade',
                        'error' => "Requires NexoSupport version {$info->requires}"
                    ];
                }

                // Check dependencies
                foreach ($info->dependencies as $dep => $depversion) {
                    $depinfo = $this->get_plugin_info($dep);
                    if ($depinfo === null) {
                        $failed[] = [
                            'component' => $info->component,
                            'status' => 'missingdependency',
                            'error' => "Missing dependency: $dep"
                        ];
                    } elseif ($depversion !== ANY_VERSION && $depinfo->version < $depversion) {
                        $failed[] = [
                            'component' => $info->component,
                            'status' => 'baddependency',
                            'error' => "Dependency $dep version {$depinfo->version} < required {$depversion}"
                        ];
                    }
                }
            }
        }

        return empty($failed);
    }

    /**
     * Get installed version of a plugin
     *
     * @param string $type Plugin type
     * @param string $name Plugin name
     * @return int|null Version or null if not installed
     */
    public function get_installed_version(string $type, string $name): ?int {
        $installed = $this->get_installed_plugins();
        return $installed[$type][$name] ?? null;
    }

    /**
     * Install a plugin
     *
     * @param string $type Plugin type
     * @param string $name Plugin name
     * @return bool Success
     */
    public function install_plugin(string $type, string $name): bool {
        global $CFG, $DB;

        $present = $this->get_present_plugins();
        $info = $present[$type][$name] ?? null;

        if (!$info) {
            debugging("Plugin not found: {$type}_{$name}");
            return false;
        }

        try {
            // 1. Install schema if exists
            $installxml = $info->rootdir . '/db/install.xml';
            if (file_exists($installxml)) {
                $DB->get_manager()->install_from_xmldb_file($installxml);
                debugging("Installed schema for {$type}_{$name}", DEBUG_DEVELOPER);
            }

            // 2. Run install.php if exists
            $installphp = $info->rootdir . '/db/install.php';
            if (file_exists($installphp)) {
                require_once($installphp);
                $function = "xmldb_{$type}_{$name}_install";
                if (function_exists($function)) {
                    $function();
                    debugging("Executed install function for {$type}_{$name}", DEBUG_DEVELOPER);
                }
            }

            // 3. Install capabilities if exists
            $this->install_plugin_capabilities($type, $name, $info->rootdir);

            // 4. Save version
            $this->save_plugin_version($type, $name, $info->version);

            // Reset caches
            self::reset_caches();

            debugging("Plugin {$type}_{$name} installed successfully", DEBUG_DEVELOPER);
            return true;

        } catch (\Exception $e) {
            debugging("Error installing {$type}_{$name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upgrade a plugin
     *
     * @param string $type Plugin type
     * @param string $name Plugin name
     * @return bool Success
     */
    public function upgrade_plugin(string $type, string $name): bool {
        global $DB;

        $present = $this->get_present_plugins();
        $info = $present[$type][$name] ?? null;

        if (!$info) {
            debugging("Plugin not found: {$type}_{$name}");
            return false;
        }

        $installedversion = $this->get_installed_version($type, $name);
        if ($installedversion === null) {
            // Not installed, should use install_plugin instead
            return $this->install_plugin($type, $name);
        }

        if ($info->version <= $installedversion) {
            // No upgrade needed
            return true;
        }

        try {
            // Run upgrade.php if exists
            $upgradefile = $info->rootdir . '/db/upgrade.php';
            if (file_exists($upgradefile)) {
                require_once($upgradefile);
                $function = "xmldb_{$type}_{$name}_upgrade";
                if (function_exists($function)) {
                    $result = $function($installedversion);
                    if (!$result) {
                        throw new \Exception("Upgrade function returned false");
                    }
                    debugging("Executed upgrade function for {$type}_{$name}", DEBUG_DEVELOPER);
                }
            }

            // Update capabilities
            $this->install_plugin_capabilities($type, $name, $info->rootdir);

            // Save new version
            $this->save_plugin_version($type, $name, $info->version);

            // Reset caches
            self::reset_caches();

            debugging("Plugin {$type}_{$name} upgraded to {$info->version}", DEBUG_DEVELOPER);
            return true;

        } catch (\Exception $e) {
            debugging("Error upgrading {$type}_{$name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Install plugin capabilities
     *
     * @param string $type Plugin type
     * @param string $name Plugin name
     * @param string $dir Plugin directory
     * @return void
     */
    private function install_plugin_capabilities(string $type, string $name, string $dir): void {
        global $DB;

        $accessfile = $dir . '/db/access.php';
        if (!file_exists($accessfile)) {
            return;
        }

        $capabilities = [];
        require($accessfile);

        foreach ($capabilities as $capname => $capdef) {
            // Check if capability exists
            $existing = $DB->get_record('capabilities', ['name' => $capname]);

            $record = new \stdClass();
            $record->name = $capname;
            $record->captype = $capdef['captype'] ?? 'write';
            $record->contextlevel = $capdef['contextlevel'] ?? CONTEXT_SYSTEM;
            $record->component = "{$type}_{$name}";
            $record->riskbitmask = $capdef['riskbitmask'] ?? 0;

            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('capabilities', $record);
            } else {
                $DB->insert_record('capabilities', $record);
            }
        }
    }

    /**
     * Save plugin version to database
     *
     * @param string $type Plugin type
     * @param string $name Plugin name
     * @param int $version Version number
     * @return void
     */
    private function save_plugin_version(string $type, string $name, int $version): void {
        global $DB;

        $component = "{$type}_{$name}";

        // Try config_plugins table first
        if ($DB->get_manager()->table_exists('config_plugins')) {
            $existing = $DB->get_record('config_plugins', [
                'plugin' => $component,
                'name' => 'version'
            ]);

            if ($existing) {
                $DB->update_record('config_plugins', [
                    'id' => $existing->id,
                    'value' => $version
                ]);
            } else {
                $DB->insert_record('config_plugins', [
                    'plugin' => $component,
                    'name' => 'version',
                    'value' => $version
                ]);
            }
        }

        // Also save to config table
        set_config('version', $version, $component);
    }

    /**
     * Get plugins that need installation or upgrade
     *
     * @return array Array of plugin info objects needing action
     */
    public function get_plugins_to_update(): array {
        $updates = [];
        $present = $this->get_present_plugins();

        foreach ($present as $type => $plugins) {
            foreach ($plugins as $name => $info) {
                $status = $this->get_plugin_status($type, $name);

                if ($status === self::STATUS_NEW || $status === self::STATUS_UPGRADE) {
                    $info->status = $status;
                    $info->installedversion = $this->get_installed_version($type, $name);
                    $updates[] = $info;
                }
            }
        }

        return $updates;
    }

    /**
     * Install or upgrade all plugins
     *
     * @param callable|null $callback Progress callback
     * @return bool True if all successful
     */
    public function process_all_plugins(?callable $callback = null): bool {
        $updates = $this->get_plugins_to_update();
        $success = true;

        foreach ($updates as $info) {
            if ($callback) {
                $callback($info->component, $info->status);
            }

            if ($info->status === self::STATUS_NEW) {
                $result = $this->install_plugin($info->type, $info->name);
            } else {
                $result = $this->upgrade_plugin($info->type, $info->name);
            }

            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }
}

// Define ANY_VERSION constant if not defined
if (!defined('ANY_VERSION')) {
    define('ANY_VERSION', -1);
}
