<?php
/**
 * System Upgrade Page
 *
 * Detecta y ejecuta actualizaciones del sistema usando la clase Upgrader.
 *
 * @package NexoSupport
 */

require_once(__DIR__ . '/../config.php');
require_once(BASE_DIR . '/lib/classes/install/upgrader.php');

require_login();

use core\install\upgrader;

// Verificar que el usuario sea site administrator
global $USER;

if (!is_siteadmin($USER->id)) {
    print_error('upgrademinrequired', 'core', '/', null,
        get_string('upgrade_requirements_failed', 'admin'));
}

// Instanciar upgrader
$upgrader = new upgrader();

// Verificar requisitos
$requirements = $upgrader->check_requirements();

// Obtener información del upgrade
$upgrade_info = $upgrader->get_upgrade_info();

// Procesar solicitud de upgrade
$upgrade_executed = false;
$upgrade_result = null;

if (isset($_POST['upgrade']) && $_POST['upgrade'] === 'true') {
    // Verificar sesskey para seguridad
    if (!isset($_POST['sesskey']) || $_POST['sesskey'] !== sesskey()) {
        print_error('invalidtoken');
    }

    // Ejecutar upgrade
    $upgrade_executed = true;
    $upgrade_result = $upgrader->execute();
}

// Construir contexto para Mustache
$context = [
    'upgrade_executed' => $upgrade_executed,
    'sesskey' => sesskey(),
    'db_version' => $upgrade_info['db_version'] ?? get_string('unknown', 'core'),
    'code_version' => $upgrade_info['code_version'] ?? '',
    'release' => $upgrade_info['release'] ?? '',
    'version_diff' => $upgrade_info['version_diff'] ?? 0,
    'needs_upgrade' => false,
    'requirements_ok' => false,
    'requirement_issues' => [],
    'has_upgrades' => false,
    'upgrades_to_execute' => [],
    'success' => false,
    'has_log' => false,
    'log' => [],
    'has_errors' => false,
    'errors' => []
];

if ($upgrade_executed) {
    // Resultado del upgrade
    $context['success'] = $upgrade_result['success'];
    $context['has_log'] = !empty($upgrade_result['log']);
    $context['log'] = $upgrade_result['log'] ?? [];
    $context['has_errors'] = !empty($upgrade_result['errors']);
    $context['errors'] = $upgrade_result['errors'] ?? [];
} else {
    // Información del upgrade
    $context['needs_upgrade'] = $upgrader->needs_upgrade();
    $context['requirements_ok'] = $requirements['success'];
    $context['requirement_issues'] = $requirements['issues'] ?? [];

    if (isset($upgrade_info['upgrades_to_execute']) && !empty($upgrade_info['upgrades_to_execute'])) {
        $context['has_upgrades'] = true;
        $context['upgrades_to_execute'] = $upgrade_info['upgrades_to_execute'];
    }
}

// Add navigation and template variables
$context['pagetitle'] = get_string('upgrade_title', 'admin');
$context['showadmin'] = true;
$context['has_navigation'] = true;
$context['navigation_html'] = get_navigation_html();

// Render template
echo render_template('admin/upgrade', $context);
