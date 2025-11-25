<?php
/**
 * Plugins Management
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

// Get installed plugins (placeholder for now)
$plugins = [];

// Prepare context
$context = [
    'pagetitle' => get_string('plugins', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'plugins' => $plugins,
    'hasplugins' => !empty($plugins),
    'sesskey' => sesskey(),
];

echo render_template('admin/plugins', $context);
