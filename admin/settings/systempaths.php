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

// Get system paths
$dataroot = $CFG->dataroot ?? BASE_DIR . '/var';
$tempdir = $CFG->tempdir ?? BASE_DIR . '/var/temp';
$cachedir = $CFG->cachedir ?? BASE_DIR . '/var/cache';

// Check path status
$dataroot_exists = is_dir($dataroot);
$dataroot_writable = $dataroot_exists && is_writable($dataroot);
$tempdir_exists = is_dir($tempdir);
$tempdir_writable = $tempdir_exists && is_writable($tempdir);
$cachedir_exists = is_dir($cachedir);
$cachedir_writable = $cachedir_exists && is_writable($cachedir);

// Prepare context
$context = [
    'pagetitle' => get_string('systempaths', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'dataroot' => $dataroot,
    'dataroot_exists' => $dataroot_exists,
    'dataroot_writable' => $dataroot_writable,
    'tempdir' => $tempdir,
    'tempdir_exists' => $tempdir_exists,
    'tempdir_writable' => $tempdir_writable,
    'cachedir' => $cachedir,
    'cachedir_exists' => $cachedir_exists,
    'cachedir_writable' => $cachedir_writable,
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_systempaths', $context);
