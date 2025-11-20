<?php
/**
 * Instalador de NexoSupport - Usando Mustache e i18n
 *
 * Front controller del instalador completamente refactorizado
 * para usar templates Mustache e internacionalización.
 *
 * @package NexoSupport
 */

// Ya está definido NEXOSUPPORT_INTERNAL desde public_html/index.php
if (!defined('NEXOSUPPORT_INTERNAL')) {
    define('NEXOSUPPORT_INTERNAL', true);
    define('BASE_DIR', dirname(__DIR__));
}

// Cargar Composer autoloader
require_once(BASE_DIR . '/vendor/autoload.php');

// Cargar funciones globales
require_once(BASE_DIR . '/lib/functions.php');

// Cargar clase Installer
require_once(BASE_DIR . '/lib/classes/install/environment_checker.php');
require_once(BASE_DIR . '/lib/classes/install/installer.php');
require_once(BASE_DIR . '/lib/classes/string_manager.php');
require_once(BASE_DIR . '/lib/classes/output/mustache_engine.php');

use core\install\installer;
use core\string_manager;
use core\output\mustache_engine;

// Configurar idioma
string_manager::set_language('es'); // TODO: Detectar del navegador o configuración

// Instanciar instalador
$installer = new installer();

// Obtener stage solicitado
$requested_stage = $_GET['stage'] ?? $installer->get_current_stage();

// Manejar acciones POST
$action_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'check_requirements':
            // Ya se ejecuta automáticamente, solo avanzar
            $installer->set_stage('database');
            header('Location: /install?stage=database');
            exit;

        case 'save_database':
            $dbconfig = [
                'driver' => $_POST['dbdriver'] ?? 'mysql',
                'host' => $_POST['dbhost'] ?? '',
                'database' => $_POST['dbname'] ?? '',
                'username' => $_POST['dbuser'] ?? '',
                'password' => $_POST['dbpass'] ?? '',
                'prefix' => $_POST['dbprefix'] ?? 'nxs_'
            ];

            // Validar configuración
            $validation = $installer->validate_database_config($dbconfig);
            if (!$validation['success']) {
                $action_result = ['success' => false, 'error' => $validation['error']];
                break;
            }

            // Probar conexión
            $connection = $installer->test_database_connection($dbconfig);
            if (!$connection['success']) {
                $action_result = ['success' => false, 'error' => get_string('error', 'core') . ': ' . $connection['error']];
                break;
            }

            // Guardar configuración
            $save = $installer->save_database_config($dbconfig);
            if (!$save['success']) {
                $action_result = ['success' => false, 'error' => get_string('error', 'core') . ': ' . $save['error']];
                break;
            }

            // Avanzar a instalación de BD
            $installer->set_stage('install_db');
            header('Location: /install?stage=install_db');
            exit;

        case 'install_schema':
            $result = $installer->install_database_schema();
            if ($result['success']) {
                $installer->set_stage('admin');
                header('Location: /install?stage=admin');
                exit;
            } else {
                $action_result = ['success' => false, 'error' => $result['error'], 'log' => $result['log']];
            }
            break;

        case 'create_admin':
            $userdata = [
                'username' => $_POST['username'] ?? '',
                'password' => $_POST['password'] ?? '',
                'email' => $_POST['email'] ?? '',
                'firstname' => $_POST['firstname'] ?? '',
                'lastname' => $_POST['lastname'] ?? ''
            ];

            // Verificar contraseñas coinciden
            if ($userdata['password'] !== ($_POST['password2'] ?? '')) {
                $action_result = ['success' => false, 'error' => get_string('admin_password_mismatch', 'install')];
                break;
            }

            $result = $installer->create_admin_user($userdata);
            if ($result['success']) {
                $installer->set_stage('finish');
                header('Location: /install?stage=finish');
                exit;
            } else {
                $action_result = ['success' => false, 'error' => $result['error']];
            }
            break;

        case 'finalize':
            $result = $installer->finalize_installation();
            if ($result['success']) {
                // Redirigir al sistema
                header('Location: /');
                exit;
            } else {
                $action_result = ['success' => false, 'error' => $result['error'], 'log' => $result['log']];
            }
            break;
    }
}

// Establecer stage actual
$installer->set_stage($requested_stage);
$current_stage = $installer->get_current_stage();

// Preparar contexto para el template según el stage
$context = [
    'currentlang' => string_manager::get_language(),
    'progress' => 0
];

switch ($current_stage) {
    case 'welcome':
        $context['progress'] = 0;
        $context['version'] = '1.1.9';
        $template = 'install/welcome';
        break;

    case 'requirements':
        $context['progress'] = 16;
        $req_result = $installer->check_requirements();
        $context['all_ok'] = $req_result['success'];
        $context['requirements'] = array_values($req_result['requirements']);
        $template = 'install/requirements';
        break;

    case 'database':
        $context['progress'] = 33;
        $context['dbdriver'] = $_POST['dbdriver'] ?? 'mysql';
        $context['dbhost'] = $_POST['dbhost'] ?? 'localhost';
        $context['dbname'] = $_POST['dbname'] ?? 'nexosupport';
        $context['dbuser'] = $_POST['dbuser'] ?? 'root';
        $context['dbpass'] = $_POST['dbpass'] ?? '';
        $context['dbprefix'] = $_POST['dbprefix'] ?? 'nxs_';
        $context['dbdriver_is_mysql'] = ($context['dbdriver'] === 'mysql');
        $context['dbdriver_is_pgsql'] = ($context['dbdriver'] === 'pgsql');
        if ($action_result && !$action_result['success']) {
            $context['error'] = $action_result['error'];
        }
        $template = 'install/database';
        break;

    case 'install_db':
        $context['progress'] = 50;
        $context['is_processing'] = ($_SERVER['REQUEST_METHOD'] === 'POST');
        if ($action_result) {
            $context['success'] = $action_result['success'] ?? false;
            $context['error'] = $action_result['error'] ?? '';
            $context['log'] = $action_result['log'] ?? [];
            $context['has_log'] = !empty($context['log']);
        }
        $template = 'install/install_db';
        break;

    case 'admin':
        $context['progress'] = 66;
        $context['username'] = $_POST['username'] ?? 'admin';
        $context['email'] = $_POST['email'] ?? '';
        $context['firstname'] = $_POST['firstname'] ?? '';
        $context['lastname'] = $_POST['lastname'] ?? '';
        if ($action_result && !$action_result['success']) {
            $context['error'] = $action_result['error'];
        }
        $template = 'install/admin';
        break;

    case 'finish':
        $context['progress'] = 83;
        $context['is_processing'] = ($_SERVER['REQUEST_METHOD'] === 'POST');
        if ($action_result) {
            $context['success'] = $action_result['success'] ?? false;
            $context['error'] = $action_result['error'] ?? '';
            $context['log'] = $action_result['log'] ?? [];
            $context['has_log'] = !empty($context['log']);
        }
        $template = 'install/finish';
        break;

    default:
        die('Invalid stage: ' . $current_stage);
}

// Renderizar template del stage
$mustache = new mustache_engine();
$stage_content = $mustache->render($template, $context);

// Renderizar layout con el contenido del stage
$layout_context = [
    'currentlang' => $context['currentlang'],
    'progress' => $context['progress'],
    'content' => $stage_content
];

echo $mustache->render('install/layout', $layout_context);
