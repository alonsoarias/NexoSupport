<?php
/**
 * NexoSupport - Plugin Manager Class
 *
 * Discovers and manages Frankenstyle plugins
 *
 * @package    tool_pluginmanager
 * @copyright  2024 ISER
 * @license    Proprietary
 */

namespace tool_pluginmanager;

/**
 * Plugin Manager - Discover and manage plugins
 */
class plugin_manager
{
    private array $pluginTypes = [];
    private string $baseDir;

    public function __construct()
    {
        $this->baseDir = BASE_DIR;
        $this->load_plugin_types();
    }

    /**
     * Load plugin types from components.json
     */
    private function load_plugin_types(): void
    {
        $componentsFile = $this->baseDir . '/lib/components.json';

        if (file_exists($componentsFile)) {
            $components = json_decode(file_get_contents($componentsFile), true);
            if (isset($components['plugintypes'])) {
                $this->pluginTypes = $components['plugintypes'];
            }
        }

        // Add default types if not set
        if (empty($this->pluginTypes)) {
            $this->pluginTypes = [
                'auth' => 'auth',
                'tool' => 'admin/tool',
                'factor' => 'admin/tool/mfa/factor',
                'theme' => 'theme',
                'report' => 'report',
            ];
        }
    }

    /**
     * Get plugin types
     *
     * @return array Plugin types
     */
    public function get_plugin_types(): array
    {
        return [
            'auth' => 'Authentication Plugins',
            'tool' => 'Admin Tools',
            'factor' => 'MFA Factors',
            'theme' => 'Themes',
            'report' => 'Reports',
        ];
    }

    /**
     * Get all installed plugins
     *
     * @return array Plugins grouped by type
     */
    public function get_installed_plugins(): array
    {
        $plugins = [];

        foreach ($this->pluginTypes as $type => $path) {
            $typePath = $this->baseDir . '/' . $path;

            if (!is_dir($typePath)) {
                continue;
            }

            $typePlugins = $this->scan_plugin_directory($typePath, $type);

            if (!empty($typePlugins)) {
                $plugins[$type] = $typePlugins;
            }
        }

        return $plugins;
    }

    /**
     * Scan directory for plugins
     *
     * @param string $path Directory path
     * @param string $type Plugin type
     * @return array Plugins found
     */
    private function scan_plugin_directory(string $path, string $type): array
    {
        $plugins = [];

        if (!is_dir($path)) {
            return $plugins;
        }

        $items = scandir($path);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . '/' . $item;

            if (!is_dir($itemPath)) {
                continue;
            }

            // Check for version.php
            $versionFile = $itemPath . '/version.php';

            if (file_exists($versionFile)) {
                $pluginInfo = $this->load_plugin_info($versionFile, $type, $item);

                if ($pluginInfo) {
                    $plugins[] = $pluginInfo;
                }
            }
        }

        return $plugins;
    }

    /**
     * Load plugin information from version.php
     *
     * @param string $versionFile Path to version.php
     * @param string $type Plugin type
     * @param string $name Plugin name
     * @return array|null Plugin info
     */
    private function load_plugin_info(string $versionFile, string $type, string $name): ?array
    {
        try {
            // Load version.php in isolated scope
            $plugin = null;
            include $versionFile;

            if (!isset($plugin) || !is_object($plugin)) {
                return null;
            }

            return [
                'component' => $plugin->component ?? "{$type}_{$name}",
                'version' => $this->format_version($plugin->version ?? 0),
                'requires' => $this->format_version($plugin->requires ?? 0),
                'maturity' => $this->get_maturity_string($plugin->maturity ?? 0),
                'release' => $plugin->release ?? 'N/A',
                'name' => $this->format_plugin_name($name),
                'description' => $plugin->description ?? '',
                'type' => $type,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format version number
     *
     * @param int $version Version number (YYYYMMDDXX)
     * @return string Formatted version
     */
    private function format_version(int $version): string
    {
        if ($version === 0) {
            return 'N/A';
        }

        $str = (string)$version;

        if (strlen($str) === 10) {
            // Format: YYYY-MM-DD.XX
            return substr($str, 0, 4) . '-' . substr($str, 4, 2) . '-' . substr($str, 6, 2) . '.' . substr($str, 8, 2);
        }

        return $str;
    }

    /**
     * Get maturity string
     *
     * @param int $maturity Maturity constant
     * @return string Maturity name
     */
    private function get_maturity_string(int $maturity): string
    {
        $maturityLevels = [
            0 => 'alpha',
            50 => 'beta',
            100 => 'rc',
            200 => 'stable',
        ];

        return $maturityLevels[$maturity] ?? 'unknown';
    }

    /**
     * Format plugin name for display
     *
     * @param string $name Plugin name
     * @return string Formatted name
     */
    private function format_plugin_name(string $name): string
    {
        // Convert underscores to spaces and capitalize
        $name = str_replace('_', ' ', $name);
        return ucwords($name);
    }

    /**
     * Get plugin by component name
     *
     * @param string $component Component name (e.g., 'tool_uploaduser')
     * @return array|null Plugin info
     */
    public function get_plugin(string $component): ?array
    {
        $plugins = $this->get_installed_plugins();

        foreach ($plugins as $typePlugins) {
            foreach ($typePlugins as $plugin) {
                if ($plugin['component'] === $component) {
                    return $plugin;
                }
            }
        }

        return null;
    }

    /**
     * Count installed plugins
     *
     * @return int Total plugins
     */
    public function count_plugins(): int
    {
        $count = 0;
        $plugins = $this->get_installed_plugins();

        foreach ($plugins as $typePlugins) {
            $count += count($typePlugins);
        }

        return $count;
    }
}
