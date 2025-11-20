<?php
/**
 * User profile view
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../config.php');

require_login();

global $USER;

// Preparar contexto para el template
$context = [
    'username' => htmlspecialchars($USER->username),
    'fullname' => htmlspecialchars($USER->firstname . ' ' . $USER->lastname),
    'email' => htmlspecialchars($USER->email)
];

// Renderizar template
echo render_template('user/profile', $context);
