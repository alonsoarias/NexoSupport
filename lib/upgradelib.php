<?php
/**
 * Upgrade Helper Functions
 *
 * Helper functions for the upgrade process.
 * Similar to Moodle's lib/upgradelib.php
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Log upgrade message
 *
 * @param int $oldversion Old version number
 * @param int $newversion New version number
 * @param string $message Message to log
 * @param bool $error Is this an error message
 */
function upgrade_log($oldversion, $newversion, $message, $error = false) {
    $type = $error ? 'ERROR' : 'INFO';
    $timestamp = date('Y-m-d H:i:s');

    echo "<div class='upgrade-log upgrade-{$type}'>";
    echo "<span class='timestamp'>[{$timestamp}]</span> ";
    echo "<span class='version'>{$oldversion} → {$newversion}</span>: ";
    echo "<span class='message'>" . htmlspecialchars($message) . "</span>";
    echo "</div>\n";

    // Also log to debugging
    debugging("Upgrade {$oldversion} → {$newversion}: {$message}", $error ? DEBUG_DEVELOPER : DEBUG_NORMAL);
}

/**
 * Handle exceptions during upgrade
 *
 * @param Exception $e The exception
 * @param string $operation The operation being performed
 */
function upgrade_handle_exception($e, $operation = 'upgrade') {
    global $CFG;

    $message = $e->getMessage();
    $trace = $e->getTraceAsString();

    echo '<div class="upgrade-error" style="background: #fee; border: 2px solid #c33; padding: 20px; margin: 20px 0;">';
    echo '<h3 style="color: #c33; margin-top: 0;">Error During ' . htmlspecialchars($operation) . '</h3>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($message) . '</p>';

    if (debugging('', DEBUG_DEVELOPER)) {
        echo '<details>';
        echo '<summary>Stack Trace</summary>';
        echo '<pre style="background: #f5f5f5; padding: 10px; overflow: auto;">';
        echo htmlspecialchars($trace);
        echo '</pre>';
        echo '</details>';
    }

    echo '</div>';

    // Log to debugging
    debugging("Upgrade error: {$message}\n{$trace}", DEBUG_DEVELOPER);
}

/**
 * Save the current version number to the database
 *
 * @param bool $result Success status
 * @param int|float $version New version number
 * @param string $release Release name (optional)
 */
function upgrade_core_savepoint($result, $version, $release = null) {
    global $DB;

    if (!$result) {
        throw new \upgrade_exception('Upgrade failed at savepoint');
    }

    // Update version in config
    $DB->update_record('config', [
        'name' => 'version',
        'value' => $version
    ]);

    if ($release !== null) {
        $DB->update_record('config', [
            'name' => 'release',
            'value' => $release
        ]);
    }

    // Clear all caches
    if (function_exists('purge_all_caches')) {
        purge_all_caches();
    }

    debugging("Upgrade savepoint: Version {$version} saved successfully", DEBUG_DEVELOPER);
}

/**
 * Check if PHP version is acceptable
 *
 * @param string $minversion Minimum required PHP version
 * @return bool True if PHP version is acceptable
 */
function upgrade_check_php_version($minversion = '8.1.0') {
    return version_compare(PHP_VERSION, $minversion, '>=');
}

/**
 * Check if required PHP extensions are loaded
 *
 * @return array Array of missing extensions
 */
function upgrade_check_php_extensions() {
    $required = [
        'pdo',
        'pdo_sqlite',
        'json',
        'mbstring',
        'session',
        'ctype',
        'fileinfo',
    ];

    $missing = [];
    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }

    return $missing;
}

/**
 * Check if database is accessible
 *
 * @return bool True if database is accessible
 */
function upgrade_check_database() {
    global $DB;

    try {
        // Try a simple query
        $DB->get_record_sql('SELECT 1 as test');
        return true;
    } catch (Exception $e) {
        debugging('Database check failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Check if config table exists
 *
 * @return bool True if config table exists
 */
function upgrade_check_config_table() {
    global $DB;

    try {
        $DB->get_record('config', ['name' => 'version']);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get current NexoSupport version from database
 *
 * @return int|false Current version number or false if not found
 */
function upgrade_get_current_version() {
    global $DB;

    try {
        $record = $DB->get_record('config', ['name' => 'version']);
        return ($record && isset($record->value)) ? (int)$record->value : false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get target NexoSupport version from version.php
 *
 * @return int|false Target version number or false if not found
 */
function upgrade_get_target_version() {
    global $CFG;

    $versionfile = $CFG->dirroot . '/lib/version.php';

    if (!file_exists($versionfile)) {
        return false;
    }

    // Parse version.php
    $plugin = new stdClass();
    include($versionfile);

    return isset($plugin->version) ? (int)$plugin->version : false;
}

/**
 * Check if upgrade is needed
 *
 * @return bool True if upgrade is needed
 */
function upgrade_is_needed() {
    $current = upgrade_get_current_version();
    $target = upgrade_get_target_version();

    if ($current === false || $target === false) {
        return false;
    }

    return $target > $current;
}

/**
 * Display upgrade progress
 *
 * @param string $message Progress message
 * @param int $percent Percentage complete (0-100)
 */
function upgrade_progress($message, $percent = null) {
    echo '<div class="upgrade-progress">';

    if ($percent !== null) {
        $percent = max(0, min(100, $percent));
        echo '<div class="progress-bar" style="background: #e0e0e0; height: 20px; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">';
        echo '<div class="progress-fill" style="background: #667eea; height: 100%; width: ' . $percent . '%; transition: width 0.3s;"></div>';
        echo '</div>';
    }

    echo '<p class="progress-message">' . htmlspecialchars($message) . '</p>';
    echo '</div>';
    echo str_repeat(' ', 4096); // Force flush
    flush();
}

/**
 * Set up output for upgrade process
 */
function upgrade_init_output() {
    @set_time_limit(0);
    @ini_set('max_execution_time', 0);
    @ini_set('memory_limit', '256M');

    // Disable output buffering
    while (ob_get_level()) {
        ob_end_flush();
    }

    echo '<style>
        .upgrade-log { padding: 8px; margin: 4px 0; font-family: monospace; font-size: 13px; }
        .upgrade-INFO { background: #e8f5e9; border-left: 4px solid #4caf50; }
        .upgrade-ERROR { background: #ffebee; border-left: 4px solid #f44336; }
        .upgrade-log .timestamp { color: #666; }
        .upgrade-log .version { font-weight: bold; color: #1976d2; }
        .upgrade-progress { margin: 20px 0; }
        .upgrade-progress .progress-message { color: #555; margin: 10px 0; }
    </style>';

    flush();
}

/**
 * Check file permissions
 *
 * @param string $path Path to check
 * @return bool True if writable
 */
function upgrade_check_permissions($path) {
    global $CFG;

    $fullpath = $CFG->dirroot . '/' . ltrim($path, '/');

    if (!file_exists($fullpath)) {
        return false;
    }

    return is_writable($fullpath);
}

/**
 * Get list of required writeable directories
 *
 * @return array Array of directory paths
 */
function upgrade_get_writable_dirs() {
    return [
        'data',
        'data/sessions',
        'data/cache',
        'data/temp',
    ];
}

/**
 * Check all required directories are writable
 *
 * @return array Array of non-writable directories
 */
function upgrade_check_writable_dirs() {
    $dirs = upgrade_get_writable_dirs();
    $nonwritable = [];

    foreach ($dirs as $dir) {
        if (!upgrade_check_permissions($dir)) {
            $nonwritable[] = $dir;
        }
    }

    return $nonwritable;
}

/**
 * Execute upgrade from one version to another
 *
 * @param int $oldversion Old version number
 * @param int $newversion New version number
 * @param string $upgradefile Path to upgrade file
 * @return bool Success
 */
function upgrade_execute($oldversion, $newversion, $upgradefile) {
    global $DB;

    if (!file_exists($upgradefile)) {
        upgrade_log($oldversion, $newversion, "Upgrade file not found: {$upgradefile}", true);
        return false;
    }

    upgrade_progress("Executing upgrade from {$oldversion} to {$newversion}");

    try {
        // Include upgrade file
        require_once($upgradefile);

        // Check if upgrade function exists
        $functionname = 'xmldb_core_upgrade';
        if (!function_exists($functionname)) {
            upgrade_log($oldversion, $newversion, "Upgrade function {$functionname} not found", true);
            return false;
        }

        // Execute upgrade
        $result = $functionname($oldversion);

        if ($result) {
            upgrade_log($oldversion, $newversion, "Upgrade completed successfully");
        } else {
            upgrade_log($oldversion, $newversion, "Upgrade failed", true);
        }

        return $result;

    } catch (Exception $e) {
        upgrade_handle_exception($e, "upgrade from {$oldversion} to {$newversion}");
        return false;
    }
}

/**
 * Purge all caches
 */
function purge_all_caches() {
    // Clear session cache if exists
    if (class_exists('\core\cache\manager')) {
        \core\cache\manager::purge_all();
    }

    // Clear RBAC cache
    if (class_exists('\core\rbac\access')) {
        \core\rbac\access::clear_all_cache();
    }

    debugging('All caches purged', DEBUG_DEVELOPER);
}

/**
 * Print upgrade header
 *
 * @param string $version Version being upgraded to
 */
function upgrade_print_header($version) {
    echo '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; margin-bottom: 30px; border-radius: 8px;">';
    echo '<h1 style="margin: 0; font-size: 32px;">NexoSupport Upgrade</h1>';
    echo '<p style="margin: 10px 0 0 0; font-size: 18px; opacity: 0.9;">Upgrading to version ' . htmlspecialchars($version) . '</p>';
    echo '</div>';
}

/**
 * Print upgrade footer
 *
 * @param bool $success Whether upgrade was successful
 */
function upgrade_print_footer($success = true) {
    if ($success) {
        echo '<div style="background: #e8f5e9; border: 2px solid #4caf50; padding: 20px; margin: 20px 0; border-radius: 8px;">';
        echo '<h3 style="color: #2e7d32; margin-top: 0;">✓ Upgrade Complete</h3>';
        echo '<p>NexoSupport has been successfully upgraded.</p>';
        echo '<p><a href="/" style="color: #1976d2; text-decoration: none; font-weight: bold;">← Return to Home</a></p>';
        echo '</div>';
    } else {
        echo '<div style="background: #ffebee; border: 2px solid #f44336; padding: 20px; margin: 20px 0; border-radius: 8px;">';
        echo '<h3 style="color: #c62828; margin-top: 0;">✗ Upgrade Failed</h3>';
        echo '<p>The upgrade process encountered errors. Please check the log messages above.</p>';
        echo '</div>';
    }
}

// ============================================
// PLUGIN UPGRADE FUNCTIONS
// Similar to Moodle's plugin upgrade system
// ============================================

/**
 * Upgrade the core of NexoSupport
 *
 * Similar to Moodle's upgrade_core() function.
 *
 * @param float $version Target version
 * @param bool $verbose Display progress
 * @return bool Success
 */
function upgrade_core($version, $verbose = true) {
    global $CFG, $DB;

    // Raise memory limit
    if (function_exists('raise_memory_limit')) {
        raise_memory_limit(MEMORY_EXTRA);
    }

    require_once($CFG->dirroot . '/lib/db/upgrade.php');

    try {
        // 1. Purge caches
        if ($verbose) {
            upgrade_progress('Purging caches...', 5);
        }
        purge_all_caches();

        // 2. Get current version
        $oldversion = upgrade_get_current_version();

        // 3. Run pre-upgrade script if exists
        $preupgradefile = $CFG->dirroot . '/local/preupgrade.php';
        if (file_exists($preupgradefile)) {
            if ($verbose) {
                upgrade_progress('Running pre-upgrade scripts...', 10);
            }
            require($preupgradefile);
        }

        // 4. Execute core upgrade
        if ($verbose) {
            upgrade_progress('Upgrading core...', 20);
        }
        $result = xmldb_core_upgrade($oldversion);

        // 5. Save new version
        if ($version > $CFG->version) {
            upgrade_main_savepoint($result, $version);
        }

        // 6. Update component
        upgrade_component_updated('moodle');

        // 7. Purge caches again
        if ($verbose) {
            upgrade_progress('Purging caches...', 90);
        }
        purge_all_caches();

        if ($verbose) {
            upgrade_progress('Core upgrade complete!', 100);
        }

        return true;

    } catch (Exception $ex) {
        upgrade_handle_exception($ex);
        return false;
    }
}

/**
 * Upgrade all non-core components (plugins)
 *
 * Similar to Moodle's upgrade_noncore() function.
 *
 * @param bool $verbose Display progress
 * @return bool Success
 */
function upgrade_noncore($verbose = true) {
    global $CFG;

    $pluginman = \core\plugin\plugin_manager::instance();
    $types = $pluginman->get_plugin_types();

    $startcallback = function($component, $install, $verbose) {
        if ($verbose) {
            $action = $install ? 'Installing' : 'Upgrading';
            upgrade_progress("{$action} {$component}...");
        }
    };

    $endcallback = function($component, $install, $verbose) {
        if ($verbose) {
            debugging("{$component} updated successfully", DEBUG_DEVELOPER);
        }
    };

    try {
        foreach ($types as $type => $typedir) {
            if ($verbose) {
                upgrade_progress("Processing {$type} plugins...");
            }
            upgrade_plugins($type, $startcallback, $endcallback, $verbose);
        }

        // Update cache definitions
        if (class_exists('\core\cache\helper')) {
            \core\cache\helper::update_definitions(true);
        }

        return true;

    } catch (Exception $ex) {
        upgrade_handle_exception($ex);
        return false;
    }
}

/**
 * Upgrade plugins of a specific type
 *
 * Similar to Moodle's upgrade_plugins() function.
 *
 * @param string $type Plugin type
 * @param callable $startcallback Callback at start of each plugin
 * @param callable $endcallback Callback at end of each plugin
 * @param bool $verbose Display progress
 * @return void
 */
function upgrade_plugins($type, $startcallback, $endcallback, $verbose) {
    global $CFG, $DB;

    $pluginman = \core\plugin\plugin_manager::instance();
    $plugins = $pluginman->get_plugin_list($type);

    foreach ($plugins as $name => $dir) {
        $component = "{$type}_{$name}";
        $status = $pluginman->get_plugin_status($type, $name);

        if ($status === \core\plugin\plugin_manager::STATUS_UPTODATE) {
            continue;
        }

        $installedversion = $pluginman->get_installed_version($type, $name);
        $install = ($status === \core\plugin\plugin_manager::STATUS_NEW);

        // Start callback
        if ($startcallback) {
            $startcallback($component, $install, $verbose);
        }

        // Process plugin
        if ($install) {
            $result = $pluginman->install_plugin($type, $name);
        } else {
            $result = $pluginman->upgrade_plugin($type, $name);
        }

        if (!$result) {
            throw new \upgrade_exception("Failed to " . ($install ? "install" : "upgrade") . " {$component}");
        }

        // End callback
        if ($endcallback) {
            $endcallback($component, $install, $verbose);
        }
    }
}

/**
 * Save main upgrade savepoint
 *
 * Similar to Moodle's upgrade_main_savepoint()
 *
 * @param bool $result Success status
 * @param float $version New version
 * @param bool $allowabort Allow abort (ignored for now)
 * @return void
 */
function upgrade_main_savepoint($result, $version, $allowabort = true) {
    global $CFG;

    if (!$result) {
        throw new \upgrade_exception("Upgrade failed at savepoint {$version}");
    }

    // Save version
    set_config('version', $version);

    // Update CFG
    $CFG->version = $version;

    debugging("Main savepoint: {$version}", DEBUG_DEVELOPER);
}

/**
 * Save plugin upgrade savepoint
 *
 * Similar to Moodle's upgrade_plugin_savepoint()
 *
 * @param bool $result Success status
 * @param float $version New version
 * @param string $type Plugin type
 * @param string $plugin Plugin name
 * @param bool $allowabort Allow abort
 * @return void
 */
function upgrade_plugin_savepoint($result, $version, $type, $plugin, $allowabort = true) {
    if (!$result) {
        throw new \upgrade_exception("Plugin upgrade failed at savepoint: {$type}_{$plugin} v{$version}");
    }

    // Save version to config
    set_config('version', $version, "{$type}_{$plugin}");

    debugging("Plugin savepoint: {$type}_{$plugin} v{$version}", DEBUG_DEVELOPER);
}

/**
 * Save module upgrade savepoint (shorthand for mod_ plugins)
 *
 * @param bool $result Success
 * @param float $version Version
 * @param string $modname Module name
 * @param bool $allowabort Allow abort
 */
function upgrade_mod_savepoint($result, $version, $modname, $allowabort = true) {
    upgrade_plugin_savepoint($result, $version, 'mod', $modname, $allowabort);
}

/**
 * Save block upgrade savepoint (shorthand for block_ plugins)
 *
 * @param bool $result Success
 * @param float $version Version
 * @param string $blockname Block name
 * @param bool $allowabort Allow abort
 */
function upgrade_block_savepoint($result, $version, $blockname, $allowabort = true) {
    upgrade_plugin_savepoint($result, $version, 'block', $blockname, $allowabort);
}

/**
 * Save auth upgrade savepoint (shorthand for auth_ plugins)
 *
 * @param bool $result Success
 * @param float $version Version
 * @param string $authname Auth plugin name
 * @param bool $allowabort Allow abort
 */
function upgrade_auth_savepoint($result, $version, $authname, $allowabort = true) {
    upgrade_plugin_savepoint($result, $version, 'auth', $authname, $allowabort);
}

/**
 * Mark a component as updated
 *
 * @param string $component Component name
 * @return void
 */
function upgrade_component_updated($component) {
    // This can be used to trigger cache invalidation or other post-upgrade actions
    debugging("Component updated: {$component}", DEBUG_DEVELOPER);

    // Trigger event if event system is available
    if (class_exists('\core\event\component_updated')) {
        try {
            $event = \core\event\component_updated::create([
                'context' => \core\rbac\context::system(),
                'other' => ['component' => $component]
            ]);
            $event->trigger();
        } catch (Exception $e) {
            // Event system might not be fully available during upgrade
        }
    }
}

/**
 * Check if major upgrade is required
 *
 * @return bool True if major upgrade needed
 */
function is_major_upgrade_required() {
    $current = upgrade_get_current_version();
    $target = upgrade_get_target_version();

    if ($current === false || $target === false) {
        return false;
    }

    // Major upgrade if major version changes (first 6 digits: YYYYMM)
    $currentmajor = floor($current / 10000);
    $targetmajor = floor($target / 10000);

    return $targetmajor > $currentmajor;
}

/**
 * Redirect if major upgrade is required
 *
 * @return void
 */
function redirect_if_major_upgrade_required() {
    global $CFG;

    if (is_major_upgrade_required()) {
        redirect($CFG->wwwroot . '/admin/upgrade.php');
    }
}

/**
 * Check if upgrade is running
 *
 * @return bool True if upgrade is in progress
 */
function upgrade_is_running() {
    global $CFG;

    $running = get_config('core', 'upgraderunning');
    if (empty($running)) {
        return false;
    }

    // Check if the expected end time has passed
    if (time() > $running) {
        // Upgrade seems to have stalled, clear the flag
        unset_config('upgraderunning');
        return false;
    }

    return true;
}

/**
 * Mark upgrade as started
 *
 * @param int $duration Expected duration in seconds
 * @return void
 */
function upgrade_started($duration = 300) {
    set_config('upgraderunning', time() + $duration);
}

/**
 * Mark upgrade as finished
 *
 * @return void
 */
function upgrade_finished() {
    unset_config('upgraderunning');
}

/**
 * Upgrade exception class
 */
class upgrade_exception extends \Exception {
}
