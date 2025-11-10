<?php
/**
 * NexoSupport - Installation Wrapper
 *
 * Este archivo actúa como punto de acceso web al instalador.
 * Solo es accesible si el sistema NO está instalado.
 *
 * Lógica similar a Moodle/WordPress pero adaptada a nuestra estructura.
 *
 * @package NexoSupport
 */

// Define base directory
define('BASE_DIR', dirname(__DIR__));
define('ENV_FILE', BASE_DIR . '/.env');

/**
 * Verificar si ya está instalado
 * Misma lógica que public_html/index.php
 */
function isInstalled(): bool {
    if (!file_exists(ENV_FILE)) {
        return false;
    }

    $envContent = @file_get_contents(ENV_FILE);
    if ($envContent === false) {
        return false;
    }

    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        if (strpos($line, 'INSTALLED=') === 0) {
            $value = trim(str_replace('INSTALLED=', '', $line));
            return ($value === 'true');
        }
    }

    return false;
}

// Si ya está instalado, redirigir al home
if (isInstalled() && !isset($_GET['reinstall'])) {
    header('Location: /');
    exit;
}

// Incluir el instalador real (fuera de public_html)
require_once BASE_DIR . '/install/index.php';
