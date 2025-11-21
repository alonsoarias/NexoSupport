<?php
/**
 * HTTP Settings - NexoSupport
 *
 * Configuration page for HTTP and proxy settings.
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
    $wwwroot = required_param('wwwroot', PARAM_URL);
    $sslproxy = optional_param('sslproxy', 0, PARAM_INT);
    $proxyhost = optional_param('proxyhost', '', PARAM_HOST);
    $proxyport = optional_param('proxyport', 0, PARAM_INT);
    $proxytype = optional_param('proxytype', 'HTTP', PARAM_ALPHA);
    $proxyuser = optional_param('proxyuser', '', PARAM_RAW);
    $proxypassword = optional_param('proxypassword', '', PARAM_RAW);
    $proxybypass = optional_param('proxybypass', '', PARAM_RAW);

    // Validate wwwroot
    if (empty($wwwroot) || !filter_var($wwwroot, FILTER_VALIDATE_URL)) {
        $errors[] = get_string('invalidwwwroot', 'admin');
    }

    if (empty($errors)) {
        set_config('wwwroot', rtrim($wwwroot, '/'), 'core');
        set_config('sslproxy', $sslproxy, 'core');

        if (!empty($proxyhost)) {
            set_config('proxyhost', $proxyhost, 'core');
            set_config('proxyport', $proxyport, 'core');
            set_config('proxytype', $proxytype, 'core');
            if (!empty($proxyuser)) {
                set_config('proxyuser', $proxyuser, 'core');
            }
            if (!empty($proxypassword)) {
                set_config('proxypassword', $proxypassword, 'core');
            }
            if (!empty($proxybypass)) {
                set_config('proxybypass', $proxybypass, 'core');
            }
        }

        $success = get_string('configsaved', 'core');
        redirect('/admin/settings/http', $success);
    }
}

// Get current settings
$current_wwwroot = $CFG->wwwroot ?? '';
$current_sslproxy = get_config('core', 'sslproxy') ?? 0;
$current_proxyhost = get_config('core', 'proxyhost') ?? '';
$current_proxyport = get_config('core', 'proxyport') ?? '';
$current_proxytype = get_config('core', 'proxytype') ?? 'HTTP';
$current_proxyuser = get_config('core', 'proxyuser') ?? '';
$current_proxybypass = get_config('core', 'proxybypass') ?? '';

// Prepare context for template
$context = [
    'sesskey' => sesskey(),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'wwwroot' => htmlspecialchars($current_wwwroot),
    'sslproxy_checked' => (bool)$current_sslproxy,
    'proxyhost' => htmlspecialchars($current_proxyhost),
    'proxyport' => (int)$current_proxyport,
    'proxytype_http' => ($current_proxytype === 'HTTP'),
    'proxytype_socks5' => ($current_proxytype === 'SOCKS5'),
    'proxyuser' => htmlspecialchars($current_proxyuser),
    'proxybypass' => htmlspecialchars($current_proxybypass),
    'server_https' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'server_host' => $_SERVER['HTTP_HOST'] ?? '',
    'pagetitle' => get_string('http', 'admin'),
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
];

echo render_template('admin/settings_http', $context);
