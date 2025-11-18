<?php
/**
 * Instalador de NexoSupport
 *
 * Maneja la instalación inicial del sistema.
 *
 * @package NexoSupport
 */

// Ya está definido NEXOSUPPORT_INTERNAL desde public_html/index.php
if (!defined('NEXOSUPPORT_INTERNAL')) {
    define('NEXOSUPPORT_INTERNAL', true);
    define('BASE_DIR', dirname(__DIR__));
}

// Determinar stage actual
$stage = $_GET['stage'] ?? 'welcome';

// Validar stage
$valid_stages = ['welcome', 'requirements', 'database', 'install_db', 'admin', 'finish'];

if (!in_array($stage, $valid_stages)) {
    $stage = 'welcome';
}

// Cargar stage
$stagefile = BASE_DIR . '/install/stages/' . $stage . '.php';

if (!file_exists($stagefile)) {
    die('Stage file not found: ' . $stage);
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

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: normal;
        }

        .progress {
            background: #f0f0f0;
            height: 8px;
            border-radius: 4px;
            margin-bottom: 30px;
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            transition: width 0.3s;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"],
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-info {
            background: #e3f2fd;
            color: #0277bd;
            border-left: 4px solid #0277bd;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        .requirement {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
        }

        .requirement:last-child {
            border-bottom: none;
        }

        .requirement .status {
            font-weight: bold;
        }

        .requirement .status.ok {
            color: #2e7d32;
        }

        .requirement .status.error {
            color: #c62828;
        }

        .actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        /* Font Awesome Icons */
        .icon {
            margin-right: 8px;
        }

        h1 .icon, h2 .icon {
            color: #667eea;
        }

        .requirement .icon {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }

        .stage-indicator {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .stage-indicator .icon {
            font-size: 24px;
            color: #667eea;
        }

        .stage-indicator .text {
            flex: 1;
        }

        .stage-indicator .step-number {
            font-weight: bold;
            color: #667eea;
            font-size: 14px;
        }

        .feature-list {
            list-style: none;
            margin: 20px 0;
            padding: 0;
        }

        .feature-list li {
            padding: 10px 0;
            padding-left: 35px;
            position: relative;
        }

        .feature-list li::before {
            content: "\f00c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            left: 0;
            color: #2e7d32;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php require($stagefile); ?>
    </div>
</body>
</html>
