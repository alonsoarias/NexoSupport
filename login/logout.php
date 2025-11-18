<?php
/**
 * Logout
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../config.php');

global $USER, $CFG;

// Terminar sesión usando el session manager
if ($CFG->installed) {
    \core\session\manager::terminate();
} else {
    // Fallback para instalador
    if (isset($_SESSION['USER'])) {
        unset($_SESSION['USER']);
    }
    session_destroy();
}

// Redirigir al login
redirect('/login');
