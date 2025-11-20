<?php
/**
 * Admin Dashboard
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../config.php');

require_login();
require_capability('nexosupport/admin:viewdashboard');

global $USER, $DB;

// Get system statistics
$total_users = $DB->count_records('users', ['deleted' => 0]);
$total_roles = $DB->count_records('roles');

// Prepare context for template
$context = [
    'pagetitle' => get_string('administration', 'admin'),
    'showadmin' => true,
    'has_navigation' => true,
    'navigation_html' => get_navigation_html(),

    // Statistics
    'total_users' => $total_users,
    'total_roles' => $total_roles,
];

// Render and output
echo render_template('admin/dashboard', $context);
