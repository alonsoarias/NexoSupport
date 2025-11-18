<?php
/**
 * Admin panel
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../config.php');

require_login();

global $USER;

// Prepare context for template
$context = [
    'user' => $USER,
    'showadmin' => true,
    'firstname' => htmlspecialchars($USER->firstname),
];

// Render and output
echo render_template('admin/dashboard', $context);
