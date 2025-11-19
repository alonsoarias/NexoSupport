<?php
namespace core\navigation;

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Plugin Navigation - v1.1.10
 *
 * Permite a los plugins registrar sus propias opciones de navegación
 * en contextos específicos, similar al sistema de Moodle.
 *
 * Los plugins pueden añadir sus opciones de configuración y administración
 * en las categorías apropiadas de la navegación del sitio.
 *
 * @package core\navigation
 */
class plugin_navigation {

    /** @var array Registered plugin navigation nodes */
    private static array $plugin_nodes = [];

    /** @var array Context types for plugin navigation */
    const CONTEXT_SYSTEM = 'system';
    const CONTEXT_USER = 'user';
    const CONTEXT_COURSE = 'course';
    const CONTEXT_CATEGORY = 'category';
    const CONTEXT_MODULE = 'module';

    /**
     * Register a plugin navigation node
     *
     * Allows plugins to add their own navigation items in specific contexts.
     *
     * @param string $component Plugin component name (e.g., 'tool_example')
     * @param string $context Context type (system, user, course, etc.)
     * @param string $parent_key Parent navigation key
     * @param array $node_config Node configuration
     * @return void
     */
    public static function register_node(
        string $component,
        string $context,
        string $parent_key,
        array $node_config
    ): void {
        // Generate unique key for this plugin node
        $node_key = self::generate_node_key($component, $node_config['key'] ?? '');

        // Store plugin node registration
        self::$plugin_nodes[] = [
            'component' => $component,
            'context' => $context,
            'parent_key' => $parent_key,
            'node_key' => $node_key,
            'config' => $node_config,
        ];
    }

    /**
     * Register plugin settings page
     *
     * Shortcut to register a plugin's main settings page in Site Administration.
     *
     * @param string $component Plugin component name
     * @param string $plugin_type Plugin type (auth, tool, theme, etc.)
     * @param array $config Additional configuration
     * @return void
     */
    public static function register_settings(
        string $component,
        string $plugin_type,
        array $config = []
    ): void {
        // Extract plugin name from component
        list($type, $name) = explode('_', $component, 2);

        $default_config = [
            'key' => 'settings',
            'text' => $config['text'] ?? get_string('pluginname', $component),
            'url' => $config['url'] ?? "/admin/plugins/{$plugin_type}/{$name}/settings",
            'icon' => $config['icon'] ?? 'fa-cog',
            'capability' => $config['capability'] ?? 'moodle/site:config',
        ];

        // Merge with provided config
        $node_config = array_merge($default_config, $config);

        // Determine parent based on plugin type
        $parent_key = self::get_plugin_parent_key($plugin_type);

        self::register_node(
            $component,
            self::CONTEXT_SYSTEM,
            $parent_key,
            $node_config
        );
    }

    /**
     * Register plugin report page
     *
     * Shortcut to register a plugin's report page.
     *
     * @param string $component Plugin component name
     * @param array $config Report configuration
     * @return void
     */
    public static function register_report(string $component, array $config): void {
        list($type, $name) = explode('_', $component, 2);

        $default_config = [
            'key' => $name,
            'text' => $config['text'] ?? get_string('pluginname', $component),
            'url' => $config['url'] ?? "/admin/reports/{$name}",
            'icon' => $config['icon'] ?? 'fa-chart-bar',
            'capability' => $config['capability'] ?? 'moodle/site:viewreports',
        ];

        $node_config = array_merge($default_config, $config);

        self::register_node(
            $component,
            self::CONTEXT_SYSTEM,
            'siteadmin_reports',
            $node_config
        );
    }

    /**
     * Register user preferences page for a plugin
     *
     * @param string $component Plugin component name
     * @param array $config Configuration
     * @return void
     */
    public static function register_user_preferences(string $component, array $config): void {
        list($type, $name) = explode('_', $component, 2);

        $default_config = [
            'key' => $name,
            'text' => $config['text'] ?? get_string('pluginname', $component),
            'url' => $config['url'] ?? "/user/preferences/{$name}",
            'icon' => $config['icon'] ?? 'fa-sliders-h',
        ];

        $node_config = array_merge($default_config, $config);

        self::register_node(
            $component,
            self::CONTEXT_USER,
            'user_preferences',
            $node_config
        );
    }

    /**
     * Get all registered plugin nodes for a context
     *
     * @param string $context Context type
     * @return array Plugin nodes
     */
    public static function get_nodes_for_context(string $context): array {
        $nodes = [];

        foreach (self::$plugin_nodes as $registration) {
            if ($registration['context'] === $context) {
                $nodes[] = $registration;
            }
        }

        return $nodes;
    }

    /**
     * Apply plugin navigation to a builder
     *
     * Called by nav_manager to add all registered plugin nodes to the navigation.
     *
     * @param navigation_builder $builder Navigation builder
     * @param string $context Context type
     * @return void
     */
    public static function apply_to_builder(navigation_builder $builder, string $context): void {
        $nodes = self::get_nodes_for_context($context);

        foreach ($nodes as $registration) {
            $config = $registration['config'];
            $config['parent'] = $registration['parent_key'];

            // Determine node type
            $type = $config['type'] ?? 'item';

            // Add to builder based on type
            if ($type === 'category') {
                $builder->add_category($registration['node_key'], $config);
            } else if ($type === 'separator') {
                $builder->add_separator($registration['node_key'], $config);
            } else {
                $builder->add_item($registration['node_key'], $config);
            }
        }
    }

    /**
     * Load plugin navigation from all installed plugins
     *
     * Calls each plugin's lib.php navigation function if it exists.
     *
     * @return void
     */
    public static function load_all_plugin_navigation(): void {
        global $CFG;

        // Get all installed plugins
        $plugin_manager = \core\plugin\manager::get_all_plugins();

        foreach ($plugin_manager as $type => $plugins) {
            foreach ($plugins as $name => $plugin) {
                // Check if plugin has navigation callback
                $component = "{$type}_{$name}";
                $plugin_dir = $plugin->get_dir();

                if (empty($plugin_dir) || !file_exists("{$plugin_dir}/lib.php")) {
                    continue;
                }

                // Include plugin's lib.php
                require_once("{$plugin_dir}/lib.php");

                // Call navigation function if exists
                $nav_function = "{$component}_extend_navigation";

                if (function_exists($nav_function)) {
                    try {
                        $nav_function();
                    } catch (\Exception $e) {
                        debugging("Error loading navigation for {$component}: " . $e->getMessage());
                    }
                }

                // Also check for admin settings navigation
                $admin_nav_function = "{$component}_extend_admin_navigation";

                if (function_exists($admin_nav_function)) {
                    try {
                        $admin_nav_function();
                    } catch (\Exception $e) {
                        debugging("Error loading admin navigation for {$component}: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Generate unique node key for a plugin
     *
     * @param string $component Plugin component
     * @param string $suffix Key suffix
     * @return string Unique node key
     */
    private static function generate_node_key(string $component, string $suffix = ''): string {
        $key = str_replace('_', '', $component);

        if (!empty($suffix)) {
            $key .= '_' . $suffix;
        }

        return $key;
    }

    /**
     * Get parent navigation key based on plugin type
     *
     * Maps plugin types to their appropriate parent category in Site Administration.
     *
     * @param string $plugin_type Plugin type
     * @return string Parent navigation key
     */
    private static function get_plugin_parent_key(string $plugin_type): string {
        $type_map = [
            'auth' => 'siteadmin_plugins_auth',
            'tool' => 'siteadmin_plugins_tool',
            'theme' => 'siteadmin_appearance_themes',
            'report' => 'siteadmin_reports',
            'factor' => 'siteadmin_plugins_factor',
            'local' => 'siteadmin_plugins_local',
            'block' => 'siteadmin_plugins_block',
        ];

        return $type_map[$plugin_type] ?? 'siteadmin_plugins';
    }

    /**
     * Reset plugin navigation (for testing)
     *
     * @return void
     */
    public static function reset(): void {
        self::$plugin_nodes = [];
    }

    /**
     * Get all registered plugin nodes (for debugging)
     *
     * @return array All registered nodes
     */
    public static function get_all_registered_nodes(): array {
        return self::$plugin_nodes;
    }
}
