<?php
/**
 * System Paths Settings - NexoSupport
 *
 * Configuration page for system directory paths.
 *
 * @package core
 * @subpackage admin
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG;

$success = null;
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $dataroot = required_param('dataroot', PARAM_PATH);
    $tempdir = optional_param('tempdir', '', PARAM_PATH);
    $cachedir = optional_param('cachedir', '', PARAM_PATH);

    // Validate paths
    if (!empty($dataroot) && !is_dir($dataroot)) {
        $errors[] = get_string('invalidpath', 'core') . ': dataroot';
    }
    if (!empty($tempdir) && !is_dir($tempdir)) {
        $errors[] = get_string('invalidpath', 'core') . ': tempdir';
    }
    if (!empty($cachedir) && !is_dir($cachedir)) {
        $errors[] = get_string('invalidpath', 'core') . ': cachedir';
    }

    if (empty($errors)) {
        set_config('dataroot', $dataroot, 'core');
        if (!empty($tempdir)) {
            set_config('tempdir', $tempdir, 'core');
        }
        if (!empty($cachedir)) {
            set_config('cachedir', $cachedir, 'core');
        }

        $success = get_string('configsaved', 'core');
        redirect('/admin/settings/systempaths', $success);
    }
}

// Get current settings
$current_dataroot = $CFG->dataroot ?? '';
$current_tempdir = $CFG->tempdir ?? ($current_dataroot . '/temp');
$current_cachedir = $CFG->cachedir ?? ($current_dataroot . '/cache');

// Prepare context for template
$context = [
    'sesskey' => sesskey(),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'dataroot' => htmlspecialchars($current_dataroot),
    'tempdir' => htmlspecialchars($current_tempdir),
    'cachedir' => htmlspecialchars($current_cachedir),
    'dataroot_exists' => is_dir($current_dataroot),
    'dataroot_writable' => is_writable($current_dataroot),
    'tempdir_exists' => is_dir($current_tempdir),
    'tempdir_writable' => is_writable($current_tempdir),
    'cachedir_exists' => is_dir($current_cachedir),
    'cachedir_writable' => is_writable($current_cachedir),
    'pagetitle' => get_string('systempaths', 'admin'),
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
];

echo render_template('admin/settings_systempaths', $context);
