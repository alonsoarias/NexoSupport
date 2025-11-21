<?php
/**
 * Post-installation script for NexoSupport core
 *
 * This script is executed after the database schema has been created
 * from install.xml. It initializes the data necessary for the system
 * to function.
 *
 * Similar to Moodle's lib/db/install.php
 *
 * @package    core
 * @copyright  NexoSupport
 * @license    Proprietary - NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

/**
 * Main post-installation function
 *
 * Called after creating tables from install.xml
 * Creates initial data: system context, roles, capabilities, etc.
 *
 * @return bool True on success
 */
function xmldb_main_install() {
    global $CFG, $DB;

    $result = true;

    try {
        // 1. Create system context (ID=1, contextlevel=10, instanceid=0)
        install_system_context();

        // 2. Install RBAC system (roles, capabilities)
        install_rbac_data();

        // 3. Set default configuration values
        install_default_config();

        // 4. Create upgrade log entry
        $DB->insert_record('logstore_standard_log', [
            'eventname' => '\\core\\event\\system_installed',
            'component' => 'core',
            'action' => 'installed',
            'target' => 'system',
            'crud' => 'c',
            'edulevel' => 0,
            'contextid' => 1,
            'contextlevel' => CONTEXT_SYSTEM,
            'contextinstanceid' => 0,
            'userid' => 0,
            'anonymous' => 0,
            'other' => json_encode(['version' => $CFG->version ?? 0]),
            'timecreated' => time(),
            'origin' => 'cli',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);

        debugging('Post-installation completed successfully', DEBUG_DEVELOPER);

    } catch (Exception $e) {
        debugging('Post-installation error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        $result = false;
    }

    return $result;
}

/**
 * Create the system context
 *
 * The system context is the root of the context hierarchy.
 * It has contextlevel=10 (CONTEXT_SYSTEM), instanceid=0, path=/1
 *
 * @return void
 */
function install_system_context() {
    global $DB;

    // Check if system context already exists
    $syscontext = $DB->get_record('contexts', [
        'contextlevel' => CONTEXT_SYSTEM,
        'instanceid' => 0
    ]);

    if (!$syscontext) {
        $context = new stdClass();
        $context->contextlevel = CONTEXT_SYSTEM;
        $context->instanceid = 0;
        $context->depth = 1;
        $context->path = '/1';

        $id = $DB->insert_record('contexts', $context);
        $context->id = $id;

        // Update path with actual ID
        $DB->update_record('contexts', [
            'id' => $id,
            'path' => '/' . $id
        ]);

        debugging('System context created with ID ' . $id, DEBUG_DEVELOPER);
    } else {
        debugging('System context already exists with ID ' . $syscontext->id, DEBUG_DEVELOPER);
    }
}

/**
 * Install RBAC data (roles, capabilities, assignments)
 *
 * @return void
 */
function install_rbac_data() {
    global $DB;

    // Load the RBAC installation helper
    require_once(__DIR__ . '/../install_rbac.php');

    // Check if RBAC data already exists
    $rolecount = $DB->count_records('roles');

    if ($rolecount == 0) {
        // Install RBAC system
        if (!install_rbac_system()) {
            throw new Exception('Failed to install RBAC system');
        }
        debugging('RBAC system installed', DEBUG_DEVELOPER);
    } else {
        debugging('RBAC data already exists (' . $rolecount . ' roles)', DEBUG_DEVELOPER);
    }
}

/**
 * Set default configuration values
 *
 * @return void
 */
function install_default_config() {
    global $CFG, $DB;

    // Default configuration values
    $defaults = [
        // Site settings
        'sitename' => 'NexoSupport',
        'lang' => 'es',
        'timezone' => 'America/Mexico_City',

        // Security settings
        'passwordpolicy' => 1,
        'minpasswordlength' => 8,
        'lockoutthreshold' => 5,
        'lockoutwindow' => 1800,      // 30 minutes
        'lockoutduration' => 1800,    // 30 minutes

        // Session settings
        'sessiontimeout' => 7200,     // 2 hours
        'sessioncookiepath' => '/',

        // Authentication
        'auth' => 'manual',           // Default auth method
        'guestloginbutton' => 0,      // No guest login by default

        // Debug settings
        'debug' => 0,                 // Production mode
        'debugdisplay' => 0,

        // Password history
        'passwordreuselimit' => 3,    // Remember last 3 passwords

        // Installed flag
        'installed' => 1,
    ];

    foreach ($defaults as $name => $value) {
        // Check if config already exists
        $existing = $DB->get_record('config', [
            'component' => 'core',
            'name' => $name
        ]);

        if (!$existing) {
            $DB->insert_record('config', [
                'component' => 'core',
                'name' => $name,
                'value' => $value
            ]);
            debugging("Config set: {$name} = {$value}", DEBUG_DEVELOPER);
        }
    }

    debugging('Default configuration installed', DEBUG_DEVELOPER);
}

/**
 * Get the list of core capabilities
 *
 * Returns all capabilities that should be installed during
 * initial setup. This complements the capabilities defined
 * in install_rbac.php for extensibility.
 *
 * @return array Array of capability definitions
 */
function get_core_capabilities() {
    return [
        // System capabilities
        [
            'name' => 'core/site:config',
            'captype' => 'write',
            'contextlevel' => CONTEXT_SYSTEM,
            'component' => 'core',
            'riskbitmask' => RISK_CONFIG | RISK_DATALOSS
        ],
        [
            'name' => 'core/site:viewparticipants',
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'component' => 'core',
            'riskbitmask' => 0
        ],
        [
            'name' => 'core/site:accessallgroups',
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'component' => 'core',
            'riskbitmask' => 0
        ],

        // User management capabilities
        [
            'name' => 'core/user:viewalldetails',
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'component' => 'core',
            'riskbitmask' => RISK_PERSONAL
        ],
        [
            'name' => 'core/user:viewhiddendetails',
            'captype' => 'read',
            'contextlevel' => CONTEXT_SYSTEM,
            'component' => 'core',
            'riskbitmask' => RISK_PERSONAL
        ],
    ];
}

/**
 * Create config_plugins table if not exists
 *
 * This table stores plugin-specific configuration and version info.
 * It's essential for the plugin system.
 *
 * @return void
 */
function install_config_plugins_table() {
    global $DB;

    $dbman = $DB->get_manager();

    // Check if table exists
    if (!$dbman->table_exists('config_plugins')) {
        // Create table using XMLDB
        $table = new \core\db\xmldb_table('config_plugins');

        $table->add_field('id', \core\db\xmldb_field::TYPE_INT, 10, true, true);
        $table->add_field('plugin', \core\db\xmldb_field::TYPE_CHAR, 100, true);
        $table->add_field('name', \core\db\xmldb_field::TYPE_CHAR, 100, true);
        $table->add_field('value', \core\db\xmldb_field::TYPE_TEXT);

        $table->add_key('primary', \core\db\xmldb_key::TYPE_PRIMARY, ['id']);
        $table->add_index('plugin_name', true, ['plugin', 'name']);

        $dbman->create_table($table);

        debugging('Created config_plugins table', DEBUG_DEVELOPER);
    }
}
