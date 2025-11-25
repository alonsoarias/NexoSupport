<?php
/**
 * User profile page
 *
 * @package core
 */

// Load configuration first (this defines NEXOSUPPORT_INTERNAL)
require_once(__DIR__ . '/../config.php');

global $USER, $DB, $CFG, $PAGE, $OUTPUT;

require_login();

// Preparar contexto para el template
$context = [
    'username' => htmlspecialchars($USER->username),
    'fullname' => htmlspecialchars($USER->firstname . ' ' . $USER->lastname),
    'email' => htmlspecialchars($USER->email)
];

// Renderizar template
echo render_template('user/profile', $context);
