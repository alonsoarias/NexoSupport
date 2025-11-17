<?php
/**
 * Logout
 *
 * @package NexoSupport
 */

defined('NEXOSUPPORT_INTERNAL') || die();

global $USER;

// Limpiar sesión
if (isset($_SESSION['USER'])) {
    unset($_SESSION['USER']);
}

session_destroy();

// Redirigir al login
redirect('/login');
