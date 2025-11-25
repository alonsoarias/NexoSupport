<?php
/**
 * HTTP Settings
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('nexosupport/admin:manageconfig');

global $USER, $CFG, $DB, $PAGE, $OUTPUT;

$success = null;
$errors = [];

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $wwwroot = required_param('wwwroot', PARAM_URL);
    $sslproxy = optional_param('sslproxy', 0, PARAM_INT);
    $proxyhost = optional_param('proxyhost', '', PARAM_HOST);
    $proxyport = optional_param('proxyport', '', PARAM_INT);
    $proxytype = optional_param('proxytype', 'HTTP', PARAM_ALPHA);
    $proxyuser = optional_param('proxyuser', '', PARAM_RAW);
    $proxypassword = optional_param('proxypassword', '', PARAM_RAW);
    $proxybypass = optional_param('proxybypass', '', PARAM_RAW);

    set_config('wwwroot', $wwwroot);
    set_config('sslproxy', $sslproxy);
    set_config('proxyhost', $proxyhost);
    set_config('proxyport', $proxyport);
    set_config('proxytype', $proxytype);
    set_config('proxyuser', $proxyuser);
    if (!empty($proxypassword)) {
        set_config('proxypassword', $proxypassword);
    }
    set_config('proxybypass', $proxybypass);

    $success = get_string('changessaved', 'admin');
}

// Get current settings
$wwwroot = get_config('core', 'wwwroot') ?? $CFG->wwwroot;
$sslproxy = (int)(get_config('core', 'sslproxy') ?? 0);
$proxyhost = get_config('core', 'proxyhost') ?? '';
$proxyport = get_config('core', 'proxyport') ?? '';
$proxytype = get_config('core', 'proxytype') ?? 'HTTP';
$proxyuser = get_config('core', 'proxyuser') ?? '';
$proxybypass = get_config('core', 'proxybypass') ?? '';

// Server info
$server_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
$server_host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Prepare context
$context = [
    'pagetitle' => get_string('http', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
    'wwwroot' => htmlspecialchars($wwwroot),
    'sslproxy_checked' => $sslproxy == 1,
    'server_https' => $server_https,
    'server_host' => $server_host,
    'proxyhost' => htmlspecialchars($proxyhost),
    'proxyport' => $proxyport,
    'proxytype_http' => $proxytype === 'HTTP',
    'proxytype_socks5' => $proxytype === 'SOCKS5',
    'proxyuser' => htmlspecialchars($proxyuser),
    'proxybypass' => htmlspecialchars($proxybypass),
    'success' => $success,
    'errors' => $errors,
    'haserrors' => !empty($errors),
    'sesskey' => sesskey(),
];

echo render_template('admin/settings_http', $context);
