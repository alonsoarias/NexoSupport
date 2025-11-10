<?php
/**
 * ISER Authentication System - Web Installer (Replanteado)
 *
 * Instalador web completo con 7 pasos optimizados para aprovechar
 * el sistema de XML Parser y Schema Installer
 *
 * @package Installer
 * @author ISER Desarrollo
 * @version 2.0
 * @license Propietario
 */

session_start();

// Define constants
define('INSTALL_DIR', __DIR__);
define('BASE_DIR', dirname(__DIR__));
define('ENV_FILE', BASE_DIR . '/.env');
define('INSTALL_LOCK', BASE_DIR . '/.installed');
define('SCHEMA_FILE', BASE_DIR . '/database/schema/schema.xml');

// Check if already installed
if (file_exists(INSTALL_LOCK) && !isset($_GET['reinstall'])) {
    header('Location: /');
    exit('Sistema ya instalado. Si necesita reinstalar, elimine el archivo .installed');
}

// Load Composer autoloader
if (!file_exists(BASE_DIR . '/vendor/autoload.php')) {
    die('Error: Composer dependencies not installed. Run: composer install');
}

require_once BASE_DIR . '/vendor/autoload.php';

// Import required classes
use ISER\Core\Database\SchemaInstaller;
use ISER\Core\Utils\XMLParser;

// Installation steps
$steps = [
    1 => [
        'title' => 'Verificación de Requisitos',
        'icon' => 'clipboard-check',
        'description' => 'Verificar requisitos del sistema'
    ],
    2 => [
        'title' => 'Configuración de Base de Datos',
        'icon' => 'database',
        'description' => 'Configurar conexión a MySQL'
    ],
    3 => [
        'title' => 'Análisis de Schema',
        'icon' => 'file-earmark-code',
        'description' => 'Revisar estructura de la base de datos'
    ],
    4 => [
        'title' => 'Instalación de Base de Datos',
        'icon' => 'download',
        'description' => 'Crear tablas e insertar datos iniciales'
    ],
    5 => [
        'title' => 'Configuración del Sistema',
        'icon' => 'gear',
        'description' => 'Configurar parámetros del sistema'
    ],
    6 => [
        'title' => 'Usuario Administrador',
        'icon' => 'person-badge',
        'description' => 'Crear cuenta de administrador'
    ],
    7 => [
        'title' => 'Finalización',
        'icon' => 'check-circle',
        'description' => 'Completar instalación'
    ]
];

// Get current step
$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($currentStep < 1 || $currentStep > 7) {
    $currentStep = 1;
}

// Process form submissions
$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($currentStep) {
        case 2:
            // Process database configuration
            $result = processDatabaseConfig($_POST);
            if ($result['success']) {
                $_SESSION['db_config'] = $_POST;
                $_SESSION['step_2_completed'] = true;
                header('Location: ?step=3');
                exit;
            } else {
                $errors = $result['errors'];
            }
            break;

        case 3:
            // Validate schema and proceed
            if (!isset($_SESSION['db_config'])) {
                $errors[] = 'Debe configurar la base de datos primero';
            } else {
                $_SESSION['step_3_completed'] = true;
                header('Location: ?step=4');
                exit;
            }
            break;

        case 4:
            // Database installation (handled via AJAX)
            if (!isset($_SESSION['db_config'])) {
                $errors[] = 'Configuración de base de datos no encontrada';
            }
            break;

        case 5:
            // Process system configuration
            $result = processSystemConfig($_POST);
            if ($result['success']) {
                $_SESSION['system_config'] = $_POST;
                if (createEnvFile()) {
                    $_SESSION['step_5_completed'] = true;
                    header('Location: ?step=6');
                    exit;
                } else {
                    $errors[] = 'No se pudo crear el archivo .env';
                }
            } else {
                $errors = $result['errors'];
            }
            break;

        case 6:
            // Create admin user
            $result = createAdminUser($_POST);
            if ($result['success']) {
                $_SESSION['admin_created'] = true;
                $_SESSION['admin_username'] = $_POST['username'];
                $_SESSION['admin_email'] = $_POST['email'];
                header('Location: ?step=7');
                exit;
            } else {
                $errors = $result['errors'];
            }
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador ISER v2.0 - Paso <?= $currentStep ?> de 7</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .installer-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .installer-header {
            background: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .installer-header h1 {
            color: var(--primary-color);
            font-weight: 700;
            margin: 0;
        }

        .installer-header .version {
            color: var(--secondary-color);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .progress-steps {
            background: #f8f9fa;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            overflow-x: auto;
        }

        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
            padding: 0.5rem;
        }

        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 25px;
            right: -50%;
            width: 100%;
            height: 2px;
            background: #dee2e6;
            z-index: 0;
        }

        .step-item.completed:not(:last-child)::after {
            background: var(--success-color);
        }

        .step-item.active:not(:last-child)::after {
            background: linear-gradient(to right, var(--success-color) 50%, #dee2e6 50%);
        }

        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: white;
            border: 3px solid #dee2e6;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .step-item.completed .step-icon {
            background: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }

        .step-item.active .step-icon {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
            color: white;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.2);
        }

        .step-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .step-item.active .step-title {
            color: var(--secondary-color);
        }

        .step-item.completed .step-title {
            color: var(--success-color);
        }

        .installer-body {
            background: white;
            padding: 2rem;
            min-height: 400px;
        }

        .installer-footer {
            background: #f8f9fa;
            padding: 1.5rem 2rem;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .card-step {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .card-step-header {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 10px 10px 0 0;
            font-weight: 600;
        }

        .btn-installer {
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-installer:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .progress-bar-striped {
            background-image: linear-gradient(
                45deg,
                rgba(255,255,255,.15) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255,255,255,.15) 50%,
                rgba(255,255,255,.15) 75%,
                transparent 75%,
                transparent
            );
            background-size: 1rem 1rem;
        }

        @keyframes progress-bar-stripes {
            0% { background-position: 1rem 0; }
            100% { background-position: 0 0; }
        }

        .progress-bar-animated {
            animation: progress-bar-stripes 1s linear infinite;
        }

        .alert-custom {
            border-left: 4px solid;
            border-radius: 8px;
        }

        .table-requirements {
            font-size: 0.9rem;
        }

        .table-requirements td, .table-requirements th {
            padding: 0.75rem;
            vertical-align: middle;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
</head>
<body>
    <div class="installer-container">
        <!-- Header -->
        <div class="installer-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-shield-check me-2"></i>ISER Authentication System</h1>
                    <p class="version mb-0">Asistente de Instalación v2.0</p>
                </div>
                <div class="text-end">
                    <div class="badge bg-secondary px-3 py-2">
                        Paso <?= $currentStep ?> de 7
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="progress-steps">
            <?php foreach ($steps as $num => $step): ?>
                <div class="step-item <?= $num < $currentStep ? 'completed' : ($num === $currentStep ? 'active' : '') ?>">
                    <div class="step-icon">
                        <?php if ($num < $currentStep): ?>
                            <i class="bi bi-check-lg fs-4"></i>
                        <?php else: ?>
                            <i class="bi bi-<?= $step['icon'] ?> fs-5"></i>
                        <?php endif; ?>
                    </div>
                    <div class="step-title"><?= $step['title'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Body -->
        <div class="installer-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Se encontraron errores:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php foreach ($success as $msg): ?>
                        <div><?= htmlspecialchars($msg) ?></div>
                    <?php endforeach; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Current Step Content -->
            <div class="card-step">
                <div class="card-step-header">
                    <i class="bi bi-<?= $steps[$currentStep]['icon'] ?> me-2"></i>
                    <?= $steps[$currentStep]['title'] ?>
                </div>
                <div class="card-body">
                    <?php
                    $stepFile = INSTALL_DIR . "/steps/step{$currentStep}.php";
                    if (file_exists($stepFile)) {
                        include $stepFile;
                    } else {
                        echo "<div class='alert alert-warning'>Archivo de paso no encontrado: step{$currentStep}.php</div>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="installer-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    <small>
                        <i class="bi bi-info-circle me-1"></i>
                        ISER Authentication System &copy; <?= date('Y') ?>
                    </small>
                </div>
                <div>
                    <?php if ($currentStep > 1 && $currentStep < 7): ?>
                        <a href="?step=<?= $currentStep - 1 ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Anterior
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global installer JavaScript
        const InstallerApp = {
            currentStep: <?= $currentStep ?>,
            baseUrl: '<?= dirname($_SERVER['PHP_SELF']) ?>',

            init() {
                console.log('ISER Installer v2.0 - Step', this.currentStep);
            },

            showError(message) {
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Error:</strong> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.installer-body').insertBefore(
                    alert,
                    document.querySelector('.card-step')
                );
            },

            showSuccess(message) {
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="bi bi-check-circle-fill me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.installer-body').insertBefore(
                    alert,
                    document.querySelector('.card-step')
                );
            }
        };

        document.addEventListener('DOMContentLoaded', () => InstallerApp.init());
    </script>
</body>
</html>

<?php

/**
 * ========================================
 * HELPER FUNCTIONS
 * ========================================
 */

/**
 * Process database configuration
 */
function processDatabaseConfig(array $data): array
{
    $errors = [];

    // Validate required fields
    $required = ['db_host', 'db_port', 'db_name', 'db_user'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[] = "El campo {$field} es requerido";
        }
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Test connection
    try {
        $dsn = "mysql:host={$data['db_host']};port={$data['db_port']}";
        $pdo = new PDO($dsn, $data['db_user'], $data['db_pass'] ?? '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check MySQL version
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        if (version_compare($version, '5.7.0', '<')) {
            return ['success' => false, 'errors' => ["MySQL version {$version} no soportada. Se requiere >= 5.7"]];
        }

        // Create database if not exists
        // Sanitize database name (alphanumeric, underscore, hyphen only)
        $dbName = preg_replace('/[^a-zA-Z0-9_-]/', '', $data['db_name']);
        if (empty($dbName)) {
            return ['success' => false, 'errors' => ['Nombre de base de datos inválido']];
        }
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        return ['success' => true, 'mysql_version' => $version];
    } catch (PDOException $e) {
        return ['success' => false, 'errors' => ["Error de conexión: " . $e->getMessage()]];
    }
}

/**
 * Process system configuration
 */
function processSystemConfig(array $data): array
{
    $errors = [];

    $required = ['app_name', 'app_url', 'app_timezone'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[] = "El campo {$field} es requerido";
        }
    }

    // Validate URL format
    if (!empty($data['app_url']) && !filter_var($data['app_url'], FILTER_VALIDATE_URL)) {
        $errors[] = "La URL de la aplicación no es válida";
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    return ['success' => true];
}

/**
 * Create .env file
 */
function createEnvFile(): bool
{
    if (!isset($_SESSION['db_config']) || !isset($_SESSION['system_config'])) {
        return false;
    }

    $db = $_SESSION['db_config'];
    $sys = $_SESSION['system_config'];

    // Generate JWT secret
    $jwtSecret = bin2hex(random_bytes(32));

    $envContent = <<<ENV
# ISER Authentication System - Environment Configuration
# Generated: {DATE}

# Application
APP_ENV={$sys['app_env']}
APP_DEBUG={$sys['app_debug']}
APP_NAME="{$sys['app_name']}"
APP_URL={$sys['app_url']}
APP_TIMEZONE={$sys['app_timezone']}
APP_LOCALE={$sys['app_locale']}

# Database
DB_CONNECTION=mysql
DB_HOST={$db['db_host']}
DB_PORT={$db['db_port']}
DB_DATABASE={$db['db_name']}
DB_USERNAME={$db['db_user']}
DB_PASSWORD={$db['db_pass']}
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX={$db['db_prefix']}

# JWT Configuration
JWT_SECRET={$jwtSecret}
JWT_ALGORITHM=HS256
JWT_EXPIRATION=3600
JWT_REFRESH_EXPIRATION=604800

# Session
SESSION_LIFETIME=120
SESSION_SECURE={$sys['session_secure']}
SESSION_HTTPONLY=true
SESSION_SAMESITE=Lax

# Security
SECURITY_PASSWORD_MIN_LENGTH=8
SECURITY_PASSWORD_REQUIRE_UPPERCASE=true
SECURITY_PASSWORD_REQUIRE_LOWERCASE=true
SECURITY_PASSWORD_REQUIRE_NUMBERS=true
SECURITY_PASSWORD_REQUIRE_SPECIAL=true
SECURITY_MAX_LOGIN_ATTEMPTS=5
SECURITY_LOCKOUT_DURATION=900

# Logging
LOG_LEVEL=info
LOG_CHANNEL=daily
LOG_MAX_FILES=30

# Email
MAIL_DRIVER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="{$sys['app_name']}"

# MFA
MFA_ENABLED=true
MFA_ISSUER="{$sys['app_name']}"

ENV;

    $envContent = str_replace('{DATE}', date('Y-m-d H:i:s'), $envContent);

    return file_put_contents(ENV_FILE, $envContent) !== false;
}

/**
 * Create admin user
 */
function createAdminUser(array $data): array
{
    $errors = [];

    // Validate fields
    $required = ['username', 'email', 'password', 'password_confirm'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[] = "El campo {$field} es requerido";
        }
    }

    if ($data['password'] !== $data['password_confirm']) {
        $errors[] = "Las contraseñas no coinciden";
    }

    // Password strength validation
    if (strlen($data['password']) < 8) {
        $errors[] = "La contraseña debe tener al menos 8 caracteres";
    }
    if (!preg_match('/[A-Z]/', $data['password'])) {
        $errors[] = "La contraseña debe contener al menos una mayúscula";
    }
    if (!preg_match('/[a-z]/', $data['password'])) {
        $errors[] = "La contraseña debe contener al menos una minúscula";
    }
    if (!preg_match('/[0-9]/', $data['password'])) {
        $errors[] = "La contraseña debe contener al menos un número";
    }

    // Email validation
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El email no es válido";
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    try {
        $db = $_SESSION['db_config'];
        $dsn = "mysql:host={$db['db_host']};port={$db['db_port']};dbname={$db['db_name']}";
        $pdo = new PDO($dsn, $db['db_user'], $db['db_pass'] ?? '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $prefix = $db['db_prefix'] ?? '';

        // Hash password with Argon2id
        $passwordHash = password_hash($data['password'], PASSWORD_ARGON2ID);
        $now = time();

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO {$prefix}iser_users
            (username, email, password, first_name, last_name, status, email_verified, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'active', 1, ?, ?)
        ");

        $stmt->execute([
            $data['username'],
            $data['email'],
            $passwordHash,
            $data['first_name'] ?? 'Admin',
            $data['last_name'] ?? 'User',
            $now,
            $now
        ]);

        $userId = $pdo->lastInsertId();

        // Assign admin role (role_id = 1)
        $stmt = $pdo->prepare("
            INSERT INTO {$prefix}iser_user_roles (user_id, role_id, assigned_at)
            VALUES (?, 1, ?)
        ");
        $stmt->execute([$userId, $now]);

        // Create user profile
        $stmt = $pdo->prepare("
            INSERT INTO {$prefix}iser_user_profiles (user_id, timezone, locale, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $_SESSION['system_config']['app_timezone'] ?? 'America/Mexico_City',
            $_SESSION['system_config']['app_locale'] ?? 'es',
            $now,
            $now
        ]);

        // Create .installed file
        file_put_contents(INSTALL_LOCK, json_encode([
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'admin_user' => $data['username']
        ]));

        return ['success' => true, 'user_id' => $userId];
    } catch (Exception $e) {
        return ['success' => false, 'errors' => [$e->getMessage()]];
    }
}
