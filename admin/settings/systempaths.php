<?php
/**
 * System Paths Settings
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

// System paths are read-only from .env
$paths = [
    ['name' => 'dirroot', 'value' => $CFG->dirroot ?? BASE_DIR],
    ['name' => 'dataroot', 'value' => $CFG->dataroot ?? BASE_DIR . '/var'],
    ['name' => 'cachedir', 'value' => $CFG->cachedir ?? BASE_DIR . '/var/cache'],
    ['name' => 'tempdir', 'value' => $CFG->tempdir ?? BASE_DIR . '/var/temp'],
    ['name' => 'sessionsdir', 'value' => $CFG->sessionsdir ?? BASE_DIR . '/var/sessions'],
];

// Prepare context
$context = [
    'pagetitle' => get_string('systempaths', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'paths' => $paths,
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_systempaths', $context);
