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

$pluginman = \core\plugin\plugin_manager::instance();

$action = optional_param('action', '', PARAM_ALPHA);
$type = optional_param('type', '', PARAM_ALPHANUMEXT);
$name = optional_param('name', '', PARAM_ALPHANUMEXT);

$message = '';
$messagetype = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    switch ($action) {
        case 'uninstall':
            if ($type && $name) {
                if ($pluginman->can_uninstall($type, $name)) {
                    $result = $pluginman->uninstall_plugin($type, $name);
                    if ($result['success']) {
                        $message = get_string('pluginuninstalled', 'admin', $name);
                        $messagetype = 'success';
                    } else {
                        $message = $result['error'] ?? get_string('pluginuninstallfailed', 'admin');
                        $messagetype = 'error';
                    }
                } else {
                    $message = get_string('plugincannotuninstall', 'admin');
                    $messagetype = 'error';
                }
            }
            break;

        case 'install':
            if (isset($_FILES['pluginzip']) && $_FILES['pluginzip']['error'] === UPLOAD_ERR_OK) {
                $targettype = required_param('plugintype', PARAM_ALPHANUMEXT);
                $result = $pluginman->install_from_zip($_FILES['pluginzip']['tmp_name'], $targettype);
                if ($result['success']) {
                    $message = get_string('plugininstalled', 'admin', $result['name'] ?? '');
                    $messagetype = 'success';
                } else {
                    $message = $result['error'] ?? get_string('plugininstallfailed', 'admin');
                    $messagetype = 'error';
                }
            } else {
                $message = get_string('pluginnofileuploaded', 'admin');
                $messagetype = 'error';
            }
            break;

        case 'upgrade':
            if ($type && $name) {
                $plugininfo = $pluginman->get_plugin_info($type, $name);
                if ($plugininfo && $plugininfo['needs_upgrade']) {
                    $result = $pluginman->upgrade_plugin($type, $name);
                    if ($result) {
                        $message = get_string('pluginupgraded', 'admin', $name);
                        $messagetype = 'success';
                    } else {
                        $message = get_string('pluginupgradefailed', 'admin');
                        $messagetype = 'error';
                    }
                }
            }
            break;
    }
}

// Get all plugins grouped by type
$allplugins = $pluginman->get_present_plugins();

// Prepare plugin types for display
$plugintypes = [];
$typeorder = ['report', 'tool', 'theme', 'auth', 'block'];

foreach ($typeorder as $ptype) {
    if (!isset($allplugins[$ptype])) {
        continue;
    }

    $plugins = [];
    foreach ($allplugins[$ptype] as $pname => $pinfo) {
        $installedversion = $pluginman->get_installed_version($ptype, $pname);
        $needsupgrade = $installedversion && $pinfo['version'] > $installedversion;
        $canuninstall = $pluginman->can_uninstall($ptype, $pname);

        $plugins[] = [
            'name' => $pname,
            'displayname' => $pinfo['name'] ?? ucfirst($pname),
            'version' => $pinfo['version'],
            'installedversion' => $installedversion,
            'description' => $pinfo['component'] ?? '',
            'requires' => $pinfo['requires'] ?? '',
            'installed' => (bool)$installedversion,
            'needsupgrade' => $needsupgrade,
            'canuninstall' => $canuninstall,
            'type' => $ptype,
        ];
    }

    if (!empty($plugins)) {
        $plugintypes[] = [
            'type' => $ptype,
            'typename' => $pluginman->get_type_display_name($ptype),
            'plugins' => $plugins,
            'hasplugins' => true,
            'count' => count($plugins),
        ];
    }
}

// Prepare context
$context = [
    'pagetitle' => get_string('plugins', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'plugintypes' => $plugintypes,
    'hastypes' => !empty($plugintypes),
    'sesskey' => sesskey(),
    'message' => $message,
    'messagetype' => $messagetype,
    'hasmessage' => !empty($message),
    'issuccess' => $messagetype === 'success',
    'iserror' => $messagetype === 'error',
    'availabletypes' => [
        ['type' => 'report', 'typename' => get_string('reports', 'admin')],
        ['type' => 'tool', 'typename' => get_string('tools', 'admin')],
        ['type' => 'theme', 'typename' => get_string('themes', 'admin')],
    ],
];

echo render_template('admin/plugins', $context);
