<?php
/**
 * ISER Authentication System - Main Entry Point
 * @package Core
 * @author ISER Desarrollo
 * @license Propietario
 */

// Define base directory
define('BASE_DIR', dirname(__DIR__));
define('INSTALL_LOCK', BASE_DIR . '/.installed');
define('ENV_FILE', BASE_DIR . '/.env');

// Verificar si el sistema está instalado
if (!file_exists(INSTALL_LOCK)) {
    // Redirigir al instalador
    header('Location: install/index.php');
    exit;
}

// Verificar que existe el archivo .env
if (!file_exists(ENV_FILE)) {
    http_response_code(500);
    die('<h1>Configuration Error</h1><p>El archivo .env no fue encontrado. Por favor, ejecute el instalador.</p>');
}

// Cargar autoloader
if (!file_exists(BASE_DIR . '/vendor/autoload.php')) {
    http_response_code(500);
    die('<h1>Dependency Error</h1><p>Composer dependencies not installed. Run: composer install</p>');
}

require_once BASE_DIR . '/vendor/autoload.php';

// Iniciar sesión
session_start();

use ISER\Core\Bootstrap;

// Inicializar la aplicación
try {
    $app = new Bootstrap(BASE_DIR);
    $app->init();
} catch (Exception $e) {
    error_log('Bootstrap Error: ' . $e->getMessage());
    http_response_code(500);
    die('<h1>System Error</h1><p>Failed to initialize the application. Check logs for details.</p>');
}

// Si el usuario está autenticado, redirigir al dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['authenticated'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISER Authentication System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="hero-card">
                    <div class="row g-0">
                        <!-- Left Side - Hero Content -->
                        <div class="col-md-6 p-5 bg-light">
                            <div class="mb-5">
                                <h1 class="display-4 fw-bold mb-3">
                                    <i class="bi bi-shield-check text-primary"></i>
                                    ISER Auth
                                </h1>
                                <p class="lead text-muted">
                                    Sistema de Autenticación y Autorización Empresarial
                                </p>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="feature-icon">
                                        <i class="bi bi-lock-fill"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h5 class="fw-bold">Seguridad Robusta</h5>
                                        <p class="text-muted mb-0">
                                            Autenticación multifactor, encriptación avanzada y protección contra ataques
                                        </p>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start mb-3">
                                    <div class="feature-icon">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h5 class="fw-bold">Gestión de Usuarios</h5>
                                        <p class="text-muted mb-0">
                                            Control completo de usuarios, roles y permisos granulares
                                        </p>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start">
                                    <div class="feature-icon">
                                        <i class="bi bi-graph-up"></i>
                                    </div>
                                    <div class="ms-3">
                                        <h5 class="fw-bold">Reportes y Auditoría</h5>
                                        <p class="text-muted mb-0">
                                            Monitoreo en tiempo real y registros detallados de actividad
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Side - Actions -->
                        <div class="col-md-6 p-5 d-flex flex-column justify-content-center">
                            <div class="text-center mb-4">
                                <h3 class="mb-3">Bienvenido</h3>
                                <p class="text-muted">
                                    Acceda a su cuenta o cree una nueva
                                </p>
                            </div>

                            <div class="d-grid gap-3">
                                <a href="login.php" class="btn btn-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Iniciar Sesión
                                </a>

                                <a href="register.php" class="btn btn-outline-primary btn-lg">
                                    <i class="bi bi-person-plus me-2"></i>
                                    Registrarse
                                </a>

                                <div class="text-center mt-3">
                                    <small class="text-muted">
                                        <a href="forgot-password.php" class="text-decoration-none">
                                            ¿Olvidó su contraseña?
                                        </a>
                                    </small>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="text-center">
                                <small class="text-muted d-block mb-2">Enlaces Útiles</small>
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="api/v1/docs" class="text-decoration-none">
                                        <i class="bi bi-code-square"></i> API Docs
                                    </a>
                                    <a href="report/" class="text-decoration-none">
                                        <i class="bi bi-graph-up"></i> Reportes
                                    </a>
                                    <a href="admin/" class="text-decoration-none">
                                        <i class="bi bi-gear"></i> Admin
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-4 text-white">
                    <small>
                        ISER Authentication System v1.0 &copy; <?= date('Y') ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
