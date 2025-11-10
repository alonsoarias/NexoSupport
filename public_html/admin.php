<?php

/**
 * ISER Authentication System - Administration Panel
 *
 * Entry point for administration interface.
 *
 * @package    ISER
 * @category   Admin
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

// Prevent direct access - Only accessible through router
if (!defined('BASE_DIR')) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>Direct access to this file is not allowed.</p>');
}

// Define base directory
if (!defined('ISER_BASE_DIR')) {
    define('ISER_BASE_DIR', dirname(__DIR__));
}

// Session check - ensure user is authenticated
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: /login');
    exit;
}

// Load Composer autoloader
require_once ISER_BASE_DIR . '/vendor/autoload.php';

use ISER\Core\Bootstrap;
use ISER\Core\Utils\Helpers;

// Initialize the system
$app = new Bootstrap(ISER_BASE_DIR);
$app->init();

$systemInfo = $app->getSystemInfo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - Sistema ISER</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
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
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 32px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 18px;
        }

        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 14px;
        }

        .label {
            font-weight: 600;
            color: #333;
        }

        .value {
            color: #666;
            font-family: 'Courier New', monospace;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .feature-list li:before {
            content: "⏳ ";
            margin-right: 10px;
        }

        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            margin: 5px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #5568d3;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Panel de Administración</h1>
        <p class="subtitle">Sistema ISER - Fase 1 Completada</p>

        <div class="warning">
            <strong>⚠️ En desarrollo</strong><br>
            La funcionalidad completa de administración será implementada en la Fase 2.
            Actualmente el sistema muestra información de estado únicamente.
        </div>

        <div class="info-box">
            <h3 style="margin-bottom: 15px;">Estado del Sistema</h3>
            <div class="info-item">
                <span class="label">Versión:</span>
                <span class="value"><?php echo htmlspecialchars($systemInfo['version']); ?></span>
            </div>
            <div class="info-item">
                <span class="label">Entorno:</span>
                <span class="value"><?php echo htmlspecialchars($systemInfo['environment']); ?></span>
            </div>
            <div class="info-item">
                <span class="label">Debug Mode:</span>
                <span class="value"><?php echo $systemInfo['debug_mode'] ? 'Activado' : 'Desactivado'; ?></span>
            </div>
            <div class="info-item">
                <span class="label">PHP Version:</span>
                <span class="value"><?php echo htmlspecialchars($systemInfo['php_version']); ?></span>
            </div>
            <div class="info-item">
                <span class="label">Módulos:</span>
                <span class="value"><?php echo $systemInfo['modules_count']; ?></span>
            </div>
        </div>

        <div class="info-box">
            <h3 style="margin-bottom: 15px;">Funcionalidades Pendientes (Fase 2)</h3>
            <ul class="feature-list">
                <li>Gestión de usuarios y permisos</li>
                <li>Autenticación manual completa</li>
                <li>Panel de control con estadísticas</li>
                <li>Logs del sistema visuales</li>
                <li>Configuración de módulos</li>
                <li>Gestión de sesiones activas</li>
                <li>Auditoría de eventos</li>
                <li>MFA (Multi-Factor Authentication)</li>
            </ul>
        </div>

        <div style="margin-top: 30px;">
            <a href="/" class="btn">← Volver al inicio</a>
            <a href="/login.php" class="btn">Iniciar sesión</a>
        </div>

        <div class="footer">
            <p>ISER Authentication System &copy; 2024</p>
            <p>Administración - Fase 1</p>
        </div>
    </div>
</body>
</html>
