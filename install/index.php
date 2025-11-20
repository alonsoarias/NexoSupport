<?php
/**
 * Instalador de NexoSupport - Refactorizado
 *
 * Front controller del instalador que usa la clase Installer
 * para toda la lógica de negocio.
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

// Cargar clase Installer
require_once(BASE_DIR . '/lib/classes/install/environment_checker.php');
require_once(BASE_DIR . '/lib/classes/install/installer.php');

use core\install\installer;

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
                $action_result = ['success' => false, 'error' => 'Error de conexión: ' . $connection['error']];
                break;
            }

            // Guardar configuración
            $save = $installer->save_database_config($dbconfig);
            if (!$save['success']) {
                $action_result = ['success' => false, 'error' => 'Error guardando configuración: ' . $save['error']];
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
                $action_result = ['success' => false, 'error' => 'Las contraseñas no coinciden'];
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

// Cargar template del stage actual
$stage_file = BASE_DIR . '/install/stages/' . $current_stage . '.php';

if (!file_exists($stage_file)) {
    die('Stage file not found: ' . $current_stage);
}

// Header HTML común
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexoSupport Installation</title>

    <!-- Font Awesome 6 - Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 700px;
            width: 100%;
            padding: 40px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #667eea;
            font-size: 32px;
            margin: 0;
        }

        .logo p {
            color: #666;
            margin-top: 5px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        h1 .icon {
            margin-right: 10px;
            color: #667eea;
        }

        h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: normal;
        }

        .progress {
            background: #f0f0f0;
            border-radius: 20px;
            height: 8px;
            margin: 20px 0;
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            transition: width 0.3s ease;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }

        .alert-info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group label .icon {
            margin-right: 8px;
            color: #667eea;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }

        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn .icon {
            margin-right: 8px;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .stage-indicator {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .stage-indicator .icon {
            font-size: 32px;
            color: #667eea;
            margin-right: 15px;
        }

        .stage-indicator .text {
            flex: 1;
        }

        .stage-indicator .step-number {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .requirements-list {
            list-style: none;
            padding: 0;
        }

        .requirements-list li {
            padding: 12px;
            margin: 8px 0;
            background: #f8f9fa;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .requirements-list .status {
            font-weight: bold;
        }

        .requirements-list .status.ok {
            color: #28a745;
        }

        .requirements-list .status.error {
            color: #dc3545;
        }

        .log-output {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin: 20px 0;
        }

        .log-output div {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1><i class="fas fa-graduation-cap"></i> NexoSupport</h1>
            <p>Sistema de Gestión con Arquitectura Frankenstyle</p>
        </div>

        <?php include($stage_file); ?>
    </div>
</body>
</html>
