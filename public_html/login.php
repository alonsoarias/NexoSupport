<?php

/**
 * ISER Authentication System - Login Page
 *
 * Entry point for user authentication.
 *
 * @package    ISER
 * @category   Auth
 * @author     ISER Development Team
 * @copyright  2024 ISER
 * @license    Proprietary
 * @version    1.0.0
 * @since      Phase 1
 */

// Define base directory
define('ISER_BASE_DIR', dirname(__DIR__));

// Load Composer autoloader
require_once ISER_BASE_DIR . '/vendor/autoload.php';

use ISER\Core\Bootstrap;

// Initialize the system
$app = new Bootstrap(ISER_BASE_DIR);
$app->init();

// TODO: Phase 2 - Implement actual login functionality
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Sistema ISER</title>
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

        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
            padding: 40px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
            text-align: center;
        }

        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
        }

        .checkbox-group label {
            margin: 0;
            font-weight: normal;
            font-size: 14px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #5568d3;
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .links {
            margin-top: 20px;
            text-align: center;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            margin: 0 10px;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 12px;
        }

        .info-text {
            font-size: 12px;
            color: #666;
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#667eea"/>
                <path d="M30 10L45 25L30 40L15 25L30 10Z" fill="white"/>
                <circle cx="30" cy="30" r="8" fill="white"/>
            </svg>
        </div>

        <h1>Iniciar sesión</h1>
        <p class="subtitle">Sistema de Autenticación ISER</p>

        <div class="warning">
            <strong>⚠️ Funcionalidad en desarrollo</strong><br>
            El sistema de autenticación será implementado en la Fase 2.
            Esta página es solo una interfaz de demostración.
        </div>

        <form id="loginForm" onsubmit="return false;">
            <div class="form-group">
                <label for="username">Usuario o Email</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Ingrese su usuario o email"
                    disabled
                >
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Ingrese su contraseña"
                    disabled
                >
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="remember" name="remember" disabled>
                <label for="remember">Recordarme en este dispositivo</label>
            </div>

            <button type="submit" class="btn" disabled>Iniciar sesión</button>

            <p class="info-text">
                La autenticación será habilitada en la Fase 2 del desarrollo
            </p>
        </form>

        <div class="links">
            <a href="#" onclick="alert('Disponible en Fase 2'); return false;">¿Olvidaste tu contraseña?</a>
            <span style="color: #ddd;">|</span>
            <a href="/" >Volver al inicio</a>
        </div>

        <div class="footer">
            <p>ISER Authentication System &copy; 2024</p>
            <p>Fase 1: Núcleo del Sistema</p>
        </div>
    </div>

    <script>
        // Placeholder for future JavaScript functionality
        console.log('ISER Auth System - Phase 1');
        console.log('Login functionality will be implemented in Phase 2');
    </script>
</body>
</html>
