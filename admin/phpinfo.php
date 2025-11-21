<?php
/**
 * PHP Info Page
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../config.php');

require_login();
require_capability('nexosupport/admin:managesettings');

// Show phpinfo in a styled container
$context = [
    'pagetitle' => 'PHP Info',
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),
];

// Get phpinfo content
ob_start();
phpinfo();
$phpinfo = ob_get_clean();

// Extract body content only
$phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);

// Add custom context
$context['phpinfo_content'] = $phpinfo;

echo render_template('admin/phpinfo', $context);
