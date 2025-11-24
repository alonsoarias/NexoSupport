<?php
defined('NEXOSUPPORT_INTERNAL') || die();
global $USER, $DB, $CFG, $PAGE, $OUTPUT;
require_once(__DIR__ . '/../config.php');

require_login();

// Preparar contexto para el template
$context = [
    'username' => htmlspecialchars($USER->username),
    'fullname' => htmlspecialchars($USER->firstname . ' ' . $USER->lastname),
    'email' => htmlspecialchars($USER->email)
];

// Renderizar template
echo render_template('user/profile', $context);
