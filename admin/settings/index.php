<?php
/**
 * Settings Index - Overview of all settings pages
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

// Define settings categories
$categories = [
    [
        'name' => get_string('generalsettings', 'admin'),
        'description' => get_string('generalsettings_desc', 'admin'),
        'url' => '/admin/settings/general',
        'icon' => 'cog',
    ],
    [
        'name' => get_string('security', 'admin'),
        'description' => get_string('security_desc', 'admin'),
        'url' => '/admin/settings/security',
        'icon' => 'shield',
    ],
    [
        'name' => get_string('server', 'admin'),
        'description' => get_string('server_desc', 'admin'),
        'url' => '/admin/settings/server',
        'icon' => 'server',
    ],
    [
        'name' => get_string('debugging', 'admin'),
        'description' => get_string('debugging_desc', 'admin'),
        'url' => '/admin/settings/debugging',
        'icon' => 'bug',
    ],
    [
        'name' => get_string('sessionhandling', 'admin'),
        'description' => get_string('sessionhandling_desc', 'admin'),
        'url' => '/admin/settings/sessionhandling',
        'icon' => 'clock',
    ],
    [
        'name' => get_string('maintenancemode', 'admin'),
        'description' => get_string('maintenancemode_desc', 'admin'),
        'url' => '/admin/settings/maintenancemode',
        'icon' => 'wrench',
    ],
];

// Prepare context
$context = [
    'pagetitle' => get_string('settings', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'categories' => $categories,
];

echo render_template('admin/settings_index', $context);
