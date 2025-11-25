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

    /**
     * Uninstall a plugin
     *
     * @param string $type Plugin type
     * @param string $name Plugin name
     * @return array Result with success status and message
     */
    public function uninstall_plugin(string $type, string $name): array {
        global $DB;

        // Check if plugin exists
        $present = $this->get_present_plugins();
        $info = $present[$type][$name] ?? null;

        if (!$info) {
            return ['success' => false, 'message' => 'Plugin not found'];
        }

        // Check if plugin can be uninstalled
        if (!$this->can_uninstall($type, $name)) {
            return ['success' => false, 'message' => 'This plugin cannot be uninstalled'];
        }

        $component = "{$type}_{$name}";
        $plugindir = $info->rootdir;

        try {
            // 1. Run uninstall.php if exists
            $uninstallphp = $plugindir . '/db/uninstall.php';
            if (file_exists($uninstallphp)) {
                require_once($uninstallphp);
                $function = "xmldb_{$type}_{$name}_uninstall";
                if (function_exists($function)) {
                    $function();
                }
            }

            // 2. Remove plugin tables if install.xml exists
            $installxml = $plugindir . '/db/install.xml';
            if (file_exists($installxml)) {
                $DB->get_manager()->uninstall_from_xmldb_file($installxml);
            }

            // 3. Remove capabilities
            $DB->delete_records('capabilities', ['component' => $component]);

            // 4. Remove role capabilities
            $DB->delete_records('role_capabilities', ['capability' => $component . '/%']);

            // 5. Remove config
            $DB->delete_records('config', ['component' => $component]);
            if ($DB->get_manager()->table_exists('config_plugins')) {
                $DB->delete_records('config_plugins', ['plugin' => $component]);
            }

            // 6. Delete plugin files
            $deleted = $this->delete_directory($plugindir);

            if (!$deleted) {
                return ['success' => false, 'message' => 'Failed to delete plugin files'];
            }

            // Reset caches
            self::reset_caches();

            return ['success' => true, 'message' => 'Plugin uninstalled successfully'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Check if a plugin can be uninstalled
     *
     * @param string $type Plugin type
     * @param string $name Plugin name
     * @return bool
     */
    public function can_uninstall(string $type, string $name): bool {
        // Core plugins that cannot be uninstalled
        $protected = [
            'theme' => ['boost'],
            'auth' => ['manual'],
        ];

        if (isset($protected[$type]) && in_array($name, $protected[$type])) {
            return false;
        }

        return true;
    }

    /**
     * Install a plugin from a ZIP file (auto-detects type from version.php)
     *
     * @param string $zippath Path to ZIP file
     * @param string|null $type Plugin type (optional - will auto-detect if not provided)
     * @return array Result with success status and message
     */
    public function install_from_zip(string $zippath, ?string $type = null): array {
        global $CFG;

        if (!file_exists($zippath)) {
            return ['success' => false, 'error' => get_string('pluginzipnotfound', 'admin')];
        }

        // Create temp directory for extraction
        $tempdir = sys_get_temp_dir() . '/nexo_plugin_' . uniqid();
        mkdir($tempdir, 0755, true);

        // Extract ZIP
        $zip = new \ZipArchive();
        if ($zip->open($zippath) !== true) {
            return ['success' => false, 'error' => get_string('pluginzipopenfailed', 'admin')];
        }

        $zip->extractTo($tempdir);
        $zip->close();

        // Find the plugin directory (could be nested or direct)
        $plugindir = $this->find_plugin_dir_in_extracted($tempdir);

        if (!$plugindir) {
            $this->delete_directory($tempdir);
            return ['success' => false, 'error' => get_string('plugininvalidstructure', 'admin')];
        }

        // Get plugin name from directory
        $pluginname = basename($plugindir);

        // Verify version.php exists
        $versionfile = $plugindir . '/version.php';
        if (!file_exists($versionfile)) {
            $this->delete_directory($tempdir);
            return ['success' => false, 'error' => get_string('pluginversionnotfound', 'admin')];
        }

        // Auto-detect plugin type from version.php
        $detected = $this->detect_plugin_type_from_version($versionfile);

        if (!$detected) {
            $this->delete_directory($tempdir);
            return ['success' => false, 'error' => get_string('plugintypenotdetected', 'admin')];
        }

        $detectedtype = $detected['type'];
        $detectedname = $detected['name'];

        // Use detected type, or validate provided type matches
        if ($type !== null && $type !== $detectedtype) {
            $this->delete_directory($tempdir);
            return ['success' => false, 'error' => get_string('plugintypemismatch', 'admin', (object)[
                'detected' => $detectedtype,
                'selected' => $type
            ])];
        }
        $type = $detectedtype;

        // Use the name from component if different from directory name
        if ($detectedname && $detectedname !== $pluginname) {
            $pluginname = $detectedname;
        }

        $types = $this->get_plugin_types();
        if (!isset($types[$type])) {
            $this->delete_directory($tempdir);
            return ['success' => false, 'error' => get_string('plugintypeinvalid', 'admin', $type)];
        }

        $targetdir = $types[$type];

        // Ensure target directory exists
        if (!is_dir($targetdir)) {
            mkdir($targetdir, 0755, true);
        }

        // Move to target directory
        $finaldir = $targetdir . '/' . $pluginname;
        if (is_dir($finaldir)) {
            $this->delete_directory($finaldir);
        }

        // Rename/move the extracted plugin to final location
        if (!rename($plugindir, $finaldir)) {
            $this->delete_directory($tempdir);
            return ['success' => false, 'error' => get_string('pluginmovefailed', 'admin')];
        }

        $this->delete_directory($tempdir);

        // Reset caches and install
        self::reset_caches();

        $result = $this->install_plugin($type, $pluginname);

        if ($result) {
            return [
                'success' => true,
                'name' => $pluginname,
                'type' => $type,
                'component' => "{$type}_{$pluginname}"
            ];
        } else {
            return ['success' => false, 'error' => get_string('plugininstallfailed', 'admin')];
        }
    }

    /**
     * Find plugin directory in extracted ZIP (handles nested structures)
     *
     * @param string $tempdir Extracted directory
     * @return string|null Path to plugin directory or null
     */
    private function find_plugin_dir_in_extracted(string $tempdir): ?string {
        $items = scandir($tempdir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullpath = $tempdir . '/' . $item;

            if (!is_dir($fullpath)) {
                continue;
            }

            // Check if this directory has version.php
            if (file_exists($fullpath . '/version.php')) {
                return $fullpath;
            }

            // Check one level deeper (for nested ZIPs like plugin-master/)
            $subitems = scandir($fullpath);
            foreach ($subitems as $subitem) {
                if ($subitem === '.' || $subitem === '..') {
                    continue;
                }
                $subpath = $fullpath . '/' . $subitem;
                if (is_dir($subpath) && file_exists($subpath . '/version.php')) {
                    return $subpath;
                }
            }
        }

        // Check if version.php is directly in tempdir
        if (file_exists($tempdir . '/version.php')) {
            return $tempdir;
        }

        return null;
    }

    /**
     * Detect plugin type and name from version.php content
     *
     * @param string $versionfile Path to version.php
     * @return array|null ['type' => ..., 'name' => ...] or null
     */
    private function detect_plugin_type_from_version(string $versionfile): ?array {
        $content = file_get_contents($versionfile);

        // Look for $plugin->component = 'type_name'
        if (preg_match('/\$plugin\s*->\s*component\s*=\s*[\'"]([a-z]+)_([a-z0-9_]+)[\'"]/i', $content, $matches)) {
            return [
                'type' => $matches[1],
                'name' => $matches[2],
                'component' => $matches[1] . '_' . $matches[2]
            ];
        }

        // Alternative: Look for component in comments or @package
        if (preg_match('/@package\s+([a-z]+)_([a-z0-9_]+)/i', $content, $matches)) {
            return [
                'type' => $matches[1],
                'name' => $matches[2],
                'component' => $matches[1] . '_' . $matches[2]
            ];
        }

        return null;
    }

    /**
     * Recursively delete a directory
     *
     * @param string $dir Directory path
     * @return bool
     */
    private function delete_directory(string $dir): bool {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->delete_directory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }

    /**
     * Get plugin type display name
     *
     * @param string $type Plugin type
     * @return string
     */
    public function get_type_display_name(string $type): string {
        $names = [
            'auth' => get_string('authentication', 'core'),
            'block' => get_string('blocks', 'core'),
            'local' => get_string('localplugins', 'core'),
            'theme' => get_string('themes', 'core'),
            'tool' => get_string('tools', 'core'),
            'report' => get_string('reports', 'core'),
        ];

        return $names[$type] ?? ucfirst($type);
    }
}

// Define ANY_VERSION constant if not defined
if (!defined('ANY_VERSION')) {
    define('ANY_VERSION', -1);
}
